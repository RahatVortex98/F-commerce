<?php

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createOrderStatusTestOrder(OrderStatus $status): Order
{
    $category = Category::query()->create([
        'name' => 'Status test category',
        'slug' => Str::uuid()->toString(),
        'active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Status test product',
        'price' => 100,
        'active' => true,
    ]);
    $customer = User::factory()->create();

    return Order::query()->create([
        'order_number' => 'OS-'.Str::upper(Str::random(10)),
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'customer_name' => $customer->name,
        'phone' => '01700000000',
        'address' => 'Status test address',
        'total' => 100,
        'status' => $status,
    ]);
}

it('allows every configured order status transition and records history', function () {
    foreach (OrderStatus::transitionMap() as $from => $targets) {
        foreach ($targets as $target) {
            $order = createOrderStatusTestOrder(OrderStatus::from($from));
            $order->transitionTo($target, source: 'system');

            expect($order->fresh()->status)->toBe($target);
            $this->assertDatabaseHas('order_status_histories', [
                'order_id' => $order->id,
                'from' => $from,
                'to' => $target->value,
                'source' => 'system',
            ]);
        }
    }
});

it('rejects every transition not present in the state map', function () {
    foreach (OrderStatus::cases() as $from) {
        foreach (OrderStatus::cases() as $to) {
            if ($from === $to || $from->canTransitionTo($to)) {
                continue;
            }

            $order = createOrderStatusTestOrder($from);

            expect(fn () => $order->transitionTo($to))
                ->toThrow(InvalidOrderTransitionException::class);
            expect($order->fresh()->status)->toBe($from);
            $this->assertDatabaseMissing('order_status_histories', ['order_id' => $order->id]);
        }
    }
});

it('treats a repeated transition to the current status as an idempotent no-op', function () {
    $order = createOrderStatusTestOrder(OrderStatus::Pending);
    $order->transitionTo(OrderStatus::Confirmed);
    $order->transitionTo(OrderStatus::Confirmed);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->statusHistories()->count())->toBe(1);
});
