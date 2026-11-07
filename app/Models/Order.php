<?php

namespace App\Models;

use App\Enums\InventoryMovementReason;
use App\Enums\OrderStatus;
use App\Enums\OrderStockState;
use App\Exceptions\InvalidOrderTransitionException;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'user_id', 'product_id', 'quantity', 'variant_value', 'variant_stock_reduced', 'customer_name',
        'phone', 'address', 'total', 'subtotal', 'delivery_charge', 'discount_total', 'status', 'stock_state',
        'courier_name', 'courier_tracking_id', 'owner_confirmed_at', 'owner_rejected_at',
        'customer_received_at',
        'checkout_token', 'public_token', 'phone_e164', 'phone_verification_hash',
        'phone_verification_expires_at', 'phone_verification_attempts',
        'phone_verified_at', 'city_id', 'zone_id', 'area_id',
        'courier_booking_status', 'courier_booking_started_at', 'courier_booking_error',
    ];

    protected function casts(): array
    {
        return [
            'phone_verification_expires_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'courier_booking_started_at' => 'datetime',
            'variant_stock_reduced' => 'boolean',
            'status' => OrderStatus::class,
            'stock_state' => OrderStockState::class,
            'total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'delivery_charge' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'owner_confirmed_at' => 'datetime',
            'owner_rejected_at' => 'datetime',
            'customer_received_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function transitionTo(
        OrderStatus $to,
        ?User $actor = null,
        ?string $note = null,
        ?string $source = null,
    ): void {
        DB::transaction(function () use ($to, $actor, $note, $source): void {
            $order = self::query()->lockForUpdate()->findOrFail($this->getKey());
            $from = $order->status;

            if ($from === $to) {
                return;
            }

            if (! $from->canTransitionTo($to)) {
                throw new InvalidOrderTransitionException(
                    "Order [{$order->id}] cannot transition from [{$from->value}] to [{$to->value}]."
                );
            }

            $source ??= $actor === null
                ? 'system'
                : (Auth::guard('customer')->user()?->is($actor) ? 'customer' : 'admin');

            if (! in_array($source, ['admin', 'customer', 'courier', 'system'], true)) {
                throw new InvalidArgumentException("Invalid order status source [{$source}].");
            }

            $order->forceFill(['status' => $to])->save();
            $order->statusHistories()->create([
                'from' => $from->value,
                'to' => $to->value,
                'actor_id' => $actor?->getKey(),
                'source' => $source,
                'note' => $note,
                'created_at' => now(),
            ]);

            if ($to === OrderStatus::Cancelled) {
                app(StockService::class)->release($order, InventoryMovementReason::Cancel);
            } elseif ($to === OrderStatus::Returned) {
                app(StockService::class)->release($order, InventoryMovementReason::Return);
            }
        }, attempts: 3);

        $this->refresh();
    }
}
