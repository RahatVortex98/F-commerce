<?php

namespace App\Services;

use App\Enums\InventoryMovementReason;
use App\Enums\OrderStatus;
use App\Enums\OrderStockState;
use App\Exceptions\StockUnavailableException;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function reserve(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($lockedOrder->stock_state === OrderStockState::Reserved) {
                return;
            }

            if ($lockedOrder->stock_state === OrderStockState::Released) {
                throw new RuntimeException("Released stock cannot be reserved again for order [{$lockedOrder->id}].");
            }

            if (in_array($lockedOrder->status, [OrderStatus::Cancelled, OrderStatus::Returned], true)) {
                throw new RuntimeException("Terminal order [{$lockedOrder->id}] cannot reserve stock.");
            }

            $items = $lockedOrder->items()->orderBy('id')->get();
            if ($items->isEmpty()) {
                throw new RuntimeException("Order [{$lockedOrder->id}] has no order items to reserve.");
            }

            foreach ($items as $item) {
                $this->reserveItem($lockedOrder, $item);
            }

            $lockedOrder->forceFill(['stock_state' => OrderStockState::Reserved])->save();
            $order->forceFill(['stock_state' => OrderStockState::Reserved]);
        }, attempts: 3);
    }

    public function release(Order $order, InventoryMovementReason $reason): void
    {
        if (! in_array($reason, [
            InventoryMovementReason::Cancel,
            InventoryMovementReason::Return,
            InventoryMovementReason::Reject,
            InventoryMovementReason::Expired,
        ], true)) {
            throw new RuntimeException("Inventory reason [{$reason->value}] cannot release reserved stock.");
        }

        DB::transaction(function () use ($order, $reason): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($lockedOrder->stock_state !== OrderStockState::Reserved) {
                return;
            }

            $sales = InventoryMovement::query()
                ->where('order_id', $lockedOrder->id)
                ->where('reason', InventoryMovementReason::Sale)
                ->orderBy('id')
                ->get();

            if ($sales->isEmpty()) {
                throw new RuntimeException("Reserved order [{$lockedOrder->id}] has no sale inventory movements.");
            }

            foreach ($sales as $sale) {
                if ($sale->delta === 0) {
                    continue;
                }

                if ($sale->delta > 0) {
                    throw new RuntimeException("Sale inventory movement [{$sale->id}] has a positive delta.");
                }

                $table = match ($sale->stock_target) {
                    'product' => 'products',
                    'variant' => 'product_variants',
                    default => throw new RuntimeException(
                        "Cannot release inventory movement [{$sale->id}] without a stock target."
                    ),
                };
                $stockId = $sale->stock_target === 'variant' ? $sale->variant_id : $sale->product_id;

                $restored = DB::table($table)
                    ->where('id', $stockId)
                    ->whereNotNull('stock')
                    ->increment('stock', abs($sale->delta));

                if ($restored > 0) {
                    InventoryMovement::query()->create([
                        'product_id' => $sale->product_id,
                        'variant_id' => $sale->variant_id,
                        'order_id' => $lockedOrder->id,
                        'user_id' => Auth::id(),
                        'delta' => abs($sale->delta),
                        'reason' => $reason,
                        'stock_target' => $sale->stock_target,
                        'note' => "Released from order {$lockedOrder->order_number}.",
                        'created_at' => now(),
                    ]);
                }
            }

            $lockedOrder->forceFill(['stock_state' => OrderStockState::Released])->save();
            $order->forceFill(['stock_state' => OrderStockState::Released]);
        }, attempts: 3);
    }

    private function reserveItem(Order $order, OrderItem $item): void
    {
        if ($item->quantity < 1) {
            throw new RuntimeException("Order item [{$item->id}] must have a positive quantity.");
        }

        $variant = $item->variant_id
            ? ProductVariant::query()->findOrFail($item->variant_id)
            : null;

        if (! $variant && $item->variant_value !== null) {
            $variant = ProductVariant::query()
                ->where('product_id', $item->product_id)
                ->where('value', $item->variant_value)
                ->first();

            if (! $variant) {
                throw new RuntimeException("Variant [{$item->variant_value}] was not found for order item [{$item->id}].");
            }
        }

        $target = null;
        if ($variant && $this->stockFor('product_variants', $variant->id) !== null) {
            if ($this->decrementTrackedStock('product_variants', $variant->id, $item->quantity, $order)) {
                $target = 'variant';
            }
        }

        if ($target === null && $this->stockFor('products', $item->product_id) !== null) {
            if ($this->decrementTrackedStock('products', $item->product_id, $item->quantity, $order)) {
                $target = 'product';
            }
        }

        InventoryMovement::query()->create([
            'product_id' => $item->product_id,
            'variant_id' => $variant?->id,
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'delta' => $target === null ? 0 : -$item->quantity,
            'reason' => InventoryMovementReason::Sale,
            'stock_target' => $target,
            'note' => $target === null ? 'Stock was untracked when the order was reserved.' : null,
            'created_at' => now(),
        ]);
    }

    private function decrementTrackedStock(string $table, int $id, int $quantity, Order $order): bool
    {
        $decremented = DB::table($table)
            ->where('id', $id)
            ->whereNotNull('stock')
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if ($decremented > 0) {
            return true;
        }

        $stock = $this->stockFor($table, $id);
        if ($stock === null) {
            return false;
        }

        throw new StockUnavailableException(
            "Insufficient stock for product inventory [{$id}] on order [{$order->id}]."
        );
    }

    private function stockFor(string $table, int $id): ?int
    {
        $stock = DB::table($table)->where('id', $id)->value('stock');

        return $stock === null ? null : (int) $stock;
    }
}
