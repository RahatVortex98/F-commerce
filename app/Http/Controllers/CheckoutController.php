<?php

namespace App\Http\Controllers;

use App\Contracts\SmsGateway;
use App\Enums\OrderStatus;
use App\Exceptions\StockUnavailableException;
use App\Models\BlacklistedPhone;
use App\Models\Order;
use App\Models\Product;
use App\Services\BangladeshPhoneNumber;
use App\Services\StockService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    public function create(Product $product): View
    {
        abort_unless($product->active, 404);
        $product->load('productVariants');

        return view('checkout', compact('product'));
    }

    public function store(
        Request $request,
        Product $product,
        BangladeshPhoneNumber $phones,
        SmsGateway $sms,
        StockService $stock,
    ): RedirectResponse {
        if ($request->filled('website')) {
            abort(422);
        }

        $checkoutToken = $request->validate([
            'checkout_token' => ['required', 'uuid'],
        ])['checkout_token'];

        $existing = Order::query()->where('checkout_token', $checkoutToken)->first();
        if ($existing) {
            return redirect()->route($existing->phone_verified_at ? 'order.success' : 'order.verify', $existing->public_token);
        }

        abort_unless($product->active, 404);

        $variantValue = $request->input('variant_value');
        $selectedVariant = is_string($variantValue)
            ? $product->productVariants()->where('value', $variantValue)->first()
            : null;
        $maxQuantity = max(20, (int) ($selectedVariant?->stock ?? $product->stock ?? 0));

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:'.$maxQuantity,
            ],
            'variant_value' => array_merge(
                $product->variants ? ['required'] : ['nullable'],
                ['string', Rule::in(array_column($product->variants ?? [], 'value'))],
            ),
            'city_id' => [config('courierhub.couriers.pathao.enabled') ? 'required' : 'nullable', 'integer', 'min:1'],
            'zone_id' => [config('courierhub.couriers.pathao.enabled') ? 'required' : 'nullable', 'integer', 'min:1'],
            'area_id' => [config('courierhub.couriers.pathao.enabled') ? 'required' : 'nullable', 'integer', 'min:1'],
        ]);
        $validated['checkout_token'] = $checkoutToken;

        $phone = $phones->normalize($validated['phone']);
        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid Bangladesh mobile number.']);
        }

        $rateKey = 'checkout:'.$request->ip().':'.$phone['e164'];
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            throw ValidationException::withMessages(['phone' => 'Too many order attempts. Please wait before trying again.']);
        }
        RateLimiter::hit($rateKey, 600);

        $blacklisted = BlacklistedPhone::query()->whereIn('phone', [$validated['phone'], $phone['local'], $phone['e164']])->exists();
        if ($blacklisted) {
            throw ValidationException::withMessages(['phone' => 'This phone number cannot place orders.']);
        }

        $code = (string) random_int(100000, 999999);
        try {
            $order = DB::transaction(function () use ($request, $product, $validated, $phone, $code, $stock): Order {
                $variant = isset($validated['variant_value'])
                    ? $product->productVariants()->where('value', $validated['variant_value'])->first()
                    : null;
                $unitPrice = (float) ($variant?->price_override ?? $product->currentPrice());
                $quantity = (int) $validated['quantity'];
                $subtotal = $unitPrice * $quantity;

                $order = Order::query()->create([
                    'order_number' => 'BK-'.Str::upper(Str::random(10)),
                    'checkout_token' => $validated['checkout_token'],
                    'public_token' => (string) Str::uuid(),
                    'user_id' => $request->user('customer')->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'variant_value' => $validated['variant_value'] ?? null,
                    'customer_name' => $request->user('customer')->name,
                    'phone' => $phone['local'],
                    'phone_e164' => $phone['e164'],
                    'address' => $validated['address'],
                    'subtotal' => $subtotal,
                    'delivery_charge' => 0,
                    'discount_total' => 0,
                    'total' => $subtotal,
                    'status' => OrderStatus::Pending,
                    'phone_verification_hash' => Hash::make($code),
                    'phone_verification_expires_at' => now()->addMinutes(10),
                    'city_id' => $validated['city_id'] ?? null,
                    'zone_id' => $validated['zone_id'] ?? null,
                    'area_id' => $validated['area_id'] ?? null,
                ]);
                $order->statusHistories()->create([
                    'from' => null,
                    'to' => OrderStatus::Pending->value,
                    'actor_id' => $request->user('customer')->id,
                    'source' => 'customer',
                    'note' => 'Order placed.',
                    'created_at' => now(),
                ]);
                $order->items()->create([
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_value' => $validated['variant_value'] ?? null,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $subtotal,
                ]);
                $stock->reserve($order);

                return $order;
            }, attempts: 3);
        } catch (UniqueConstraintViolationException $exception) {
            $order = Order::query()->where('checkout_token', $validated['checkout_token'])->first();
            if (! $order) {
                throw $exception;
            }

            return redirect()->route($order->phone_verified_at ? 'order.success' : 'order.verify', $order->public_token);
        } catch (StockUnavailableException) {
            throw ValidationException::withMessages([
                'quantity' => 'The product does not have enough stock for that quantity.',
            ]);
        }

        try {
            $sms->send($phone['e164'], "Your Bikrify verification code is {$code}. It expires in 10 minutes.");
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('order.verify', $order->public_token)
                ->withErrors(['code' => 'The order is saved, but the SMS could not be sent. Use resend to try again.']);
        }

        $response = redirect()->route('order.verify', $order->public_token)->with('status', 'Enter the code we sent to your phone.');
        if (app()->environment(['local', 'testing']) && config('sms.driver') === 'log') {
            $response->with('local_verification_code', $code);
        }

        return $response;
    }
}
