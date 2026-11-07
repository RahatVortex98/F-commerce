<?php

namespace App\Http\Controllers;

use App\Contracts\SmsGateway;
use App\Models\Order;
use App\Models\User;
use Combindma\FacebookPixel\Facades\MetaPixel;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

class PhoneVerificationController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $order = Order::query()->where('public_token', $token)->firstOrFail();

        if ($order->phone_verified_at) {
            return redirect()->route('order.success', $token);
        }

        return view('verify-phone', compact('order'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $isAlreadyVerified = Order::query()->where('public_token', $token)->value('phone_verified_at');
        if ($isAlreadyVerified) {
            return redirect()->route('order.success', $token);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $result = DB::transaction(function () use ($token, $validated): array {
            $order = Order::query()
                ->where('public_token', $token)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->phone_verified_at) {
                return ['order' => $order, 'newly_verified' => false];
            }

            if ($order->phone_verification_expires_at?->isPast()) {
                return ['order' => null, 'newly_verified' => false];
            }

            if ($order->phone_verification_attempts >= 5 || ! $order->phone_verification_hash) {
                return ['order' => null, 'newly_verified' => false];
            }

            if (! Hash::check($validated['code'], $order->phone_verification_hash)) {
                $order->increment('phone_verification_attempts');

                return ['order' => null, 'newly_verified' => false];
            }

            $order->forceFill([
                'phone_verified_at' => now(),
                'phone_verification_hash' => null,
                'phone_verification_expires_at' => null,
                'phone_verification_attempts' => 0,
                'courier_booking_status' => 'awaiting_owner_confirmation',
            ])->save();

            return ['order' => $order, 'newly_verified' => true];
        });

        $order = $result['order'];

        if (! $order || ! $order->phone_verified_at) {
            return back()->withErrors(['code' => 'That code is invalid or expired. Request a new code and try again.']);
        }

        if (! $result['newly_verified']) {
            return redirect()->route('order.success', $token);
        }

        $itemDescription = $order->items
            ->map(fn ($item): string => $item->product_name.($item->variant_value ? " ({$item->variant_value})" : '')." × {$item->quantity}")
            ->implode(', ');
        $notification = Notification::make()
            ->title('New order awaits confirmation')
            ->body("{$order->order_number}: {$itemDescription}. Review and confirm in Orders before dispatch.")
            ->icon('heroicon-o-shopping-bag')
            ->toDatabase();

        User::query()->where('is_admin', true)
            ->each(fn (User $user) => $user->notify($notification->onConnection('sync')));

        try {
            MetaPixel::track('Purchase', [
                'currency' => 'BDT',
                'value' => $order->total,
            ], $order->id);
        } catch (Throwable $exception) {
            Log::warning('Meta Pixel purchase tracking failed after verified order.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
            ]);
        }

        return redirect()->route('order.success', $token);
    }

    public function resend(string $token, SmsGateway $smsGateway): RedirectResponse
    {
        $key = 'otp-resend:'.$token;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['code' => 'Too many code requests. Wait a few minutes and try again.']);
        }

        RateLimiter::hit($key, 600);

        $order = Order::query()->where('public_token', $token)->firstOrFail();

        if ($order->phone_verified_at) {
            return redirect()->route('order.success', $token);
        }

        $code = (string) random_int(100000, 999999);

        $order->forceFill([
            'phone_verification_hash' => Hash::make($code),
            'phone_verification_expires_at' => now()->addMinutes(10),
            'phone_verification_attempts' => 0,
        ])->save();

        try {
            $smsGateway->send($order->phone_e164, "Your F-commerce verification code is {$code}. It expires in 10 minutes.");
        } catch (Throwable $exception) {
            Log::warning('Phone verification SMS could not be sent.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
            ]);

            return back()->withErrors(['code' => 'We could not send a new text right now. Please try again shortly.']);
        }

        $response = redirect()->route('order.verify', $token)->with('status', 'A new verification code has been sent.');
        if (app()->environment(['local', 'testing']) && config('sms.driver') === 'log') {
            $response->with('local_verification_code', $code);
        }

        return $response;
    }

    public function success(string $token): View|RedirectResponse
    {
        $order = Order::query()->with('items')->where('public_token', $token)->firstOrFail();

        if (! $order->phone_verified_at) {
            return redirect()->route('order.verify', $token);
        }

        return view('order-success', compact('order'));
    }
}
