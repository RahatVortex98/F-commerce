<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use CourierHub\DTOs\OrderData;
use CourierHub\Facades\Courier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreatePathaoShipment implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(public int $orderId) {}

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function handle(): void
    {
        if (! config('courierhub.couriers.pathao.enabled')) {
            return;
        }

        $order = DB::transaction(function (): ?Order {
            $order = Order::query()->lockForUpdate()->find($this->orderId);
            if (! $order || ! $order->phone_verified_at || ! $order->owner_confirmed_at || $order->courier_booking_status !== 'queued') {
                return null;
            }
            if (! $order->city_id || ! $order->zone_id || ! $order->area_id) {
                $order->update([
                    'courier_booking_status' => 'failed',
                    'courier_booking_error' => 'Pathao city, zone, and area are required.',
                ]);

                return null;
            }

            $order->update([
                'courier_booking_status' => 'booking',
                'courier_booking_started_at' => now(),
                'courier_booking_error' => null,
            ]);

            return $order;
        });

        if (! $order) {
            return;
        }

        $items = $order->items()->with('product')->get();
        $itemDescription = $items->map(fn (OrderItem $item): string => trim(
            $item->product_name.($item->variant_value ? ' - '.$item->variant_value : '')
        ))->implode(', ');
        $itemWeight = max(0.5, (float) $items->sum(
            fn (OrderItem $item): float => (float) ($item->product?->weight ?? 0.5) * $item->quantity
        ));

        try {
            $response = Courier::driver('pathao')->createOrder(OrderData::from([
                'merchant_order_id' => $order->order_number,
                'recipient_name' => $order->customer_name,
                'recipient_phone' => $order->phone,
                'recipient_address' => $order->address,
                'amount_to_collect' => $order->total,
                'weight' => $itemWeight,
                'city_id' => $order->city_id,
                'zone_id' => $order->zone_id,
                'area_id' => $order->area_id,
                'item_description' => $itemDescription,
                'item_quantity' => $items->sum('quantity'),
            ]));

            if ($response->tracking_id === '') {
                throw new \RuntimeException('Pathao did not return a consignment ID.');
            }

            $order->update([
                'courier_name' => 'pathao',
                'courier_tracking_id' => $response->tracking_id,
                'courier_booking_status' => 'booked',
                'courier_booking_error' => null,
            ]);
            $order->transitionTo(OrderStatus::Confirmed, source: 'system');
        } catch (Throwable $exception) {
            report($exception);
            $order->update([
                'courier_booking_status' => 'failed',
                'courier_booking_error' => 'Pathao could not confirm this booking. Check the merchant dashboard before retrying.',
            ]);
        }
    }
}
