<?php

use App\Enums\OrderStatus;
use App\Enums\OrderStockState;
use App\Exceptions\StockUnavailableException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createStockServiceTestProduct(?int $stock): Product
{
    $category = Category::query()->create([
        'name' => 'Inventory test category',
        'slug' => Str::uuid()->toString(),
        'active' => true,
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Inventory test product',
        'price' => 100,
        'stock' => $stock,
        'active' => true,
    ]);
}

function createStockServiceTestOrder(Product $product, int $quantity = 1, ?ProductVariant $variant = null): Order
{
    $customer = User::factory()->create();
    $order = Order::query()->create([
        'order_number' => 'ST-'.Str::upper(Str::random(10)),
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'variant_value' => $variant?->value,
        'customer_name' => $customer->name,
        'phone' => '01700000000',
        'address' => 'Inventory test address',
        'subtotal' => 100 * $quantity,
        'total' => 100 * $quantity,
        'status' => OrderStatus::Pending,
    ]);

    $order->items()->create([
        'product_id' => $product->id,
        'variant_id' => $variant?->id,
        'product_name' => $product->name,
        'variant_value' => $variant?->value,
        'unit_price' => 100,
        'quantity' => $quantity,
        'line_total' => 100 * $quantity,
    ]);

    return $order;
}

it('reserves tracked simple-product stock once per order', function () {
    $product = createStockServiceTestProduct(5);
    $order = createStockServiceTestOrder($product, 2);
    $stock = app(StockService::class);

    $stock->reserve($order);
    $stock->reserve($order);

    expect($product->fresh()->stock)->toBe(3)
        ->and($order->fresh()->stock_state)->toBe(OrderStockState::Reserved);
    $this->assertDatabaseCount('inventory_movements', 1);
    $this->assertDatabaseHas('inventory_movements', [
        'order_id' => $order->id,
        'delta' => -2,
        'reason' => 'sale',
        'stock_target' => 'product',
    ]);
});

it('rolls back every line when a later line is short', function () {
    $firstProduct = createStockServiceTestProduct(5);
    $secondProduct = createStockServiceTestProduct(0);
    $order = createStockServiceTestOrder($firstProduct, 2);
    $order->items()->create([
        'product_id' => $secondProduct->id,
        'product_name' => $secondProduct->name,
        'unit_price' => 100,
        'quantity' => 1,
        'line_total' => 100,
    ]);

    expect(fn () => app(StockService::class)->reserve($order))
        ->toThrow(StockUnavailableException::class);

    expect($firstProduct->fresh()->stock)->toBe(5)
        ->and($secondProduct->fresh()->stock)->toBe(0)
        ->and($order->fresh()->stock_state)->toBe(OrderStockState::None);
    $this->assertDatabaseCount('inventory_movements', 0);
});

it('atomically rejects one of two orders competing for the last unit', function () {
    $product = createStockServiceTestProduct(1);
    $firstOrder = createStockServiceTestOrder($product);
    $secondOrder = createStockServiceTestOrder($product);
    $stock = app(StockService::class);

    $stock->reserve($firstOrder);
    expect(fn () => $stock->reserve($secondOrder))
        ->toThrow(StockUnavailableException::class);

    expect($product->fresh()->stock)->toBe(0)
        ->and($firstOrder->fresh()->stock_state)->toBe(OrderStockState::Reserved)
        ->and($secondOrder->fresh()->stock_state)->toBe(OrderStockState::None);
    $this->assertDatabaseCount('inventory_movements', 1);
});

it('restores stock when a reserved order is cancelled and does not restore twice', function () {
    $product = createStockServiceTestProduct(4);
    $order = createStockServiceTestOrder($product, 3);
    $stock = app(StockService::class);
    $stock->reserve($order);

    $order->transitionTo(OrderStatus::Cancelled);
    $order->transitionTo(OrderStatus::Cancelled);

    expect($product->fresh()->stock)->toBe(4)
        ->and($order->fresh()->stock_state)->toBe(OrderStockState::Released);
    $this->assertDatabaseHas('inventory_movements', [
        'order_id' => $order->id,
        'delta' => 3,
        'reason' => 'cancel',
    ]);
    $this->assertDatabaseCount('inventory_movements', 2);
});

it('restores reserved variant stock when an order is returned', function () {
    $product = createStockServiceTestProduct(10);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'value' => 'M',
        'stock' => 3,
    ]);
    $order = createStockServiceTestOrder($product, 2, $variant);
    app(StockService::class)->reserve($order);
    $order->forceFill(['status' => OrderStatus::Delivered])->save();

    $order->transitionTo(OrderStatus::Returned);

    expect($variant->fresh()->stock)->toBe(3)
        ->and($product->fresh()->stock)->toBe(10)
        ->and($order->fresh()->stock_state)->toBe(OrderStockState::Released);
    $this->assertDatabaseHas('inventory_movements', [
        'order_id' => $order->id,
        'delta' => 2,
        'reason' => 'return',
        'stock_target' => 'variant',
    ]);
});

it('leaves null stock untracked through reservation and release', function () {
    $product = createStockServiceTestProduct(null);
    $order = createStockServiceTestOrder($product, 3);
    $stock = app(StockService::class);
    $stock->reserve($order);

    $order->transitionTo(OrderStatus::Cancelled);

    expect($product->fresh()->stock)->toBeNull()
        ->and($order->fresh()->stock_state)->toBe(OrderStockState::Released);
    $this->assertDatabaseHas('inventory_movements', [
        'order_id' => $order->id,
        'delta' => 0,
        'reason' => 'sale',
        'stock_target' => null,
    ]);
    $this->assertDatabaseMissing('inventory_movements', [
        'order_id' => $order->id,
        'reason' => 'cancel',
    ]);
});

it('uses the legacy shared pool for imported variants without a variant count', function () {
    $product = createStockServiceTestProduct(5);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'value' => 'M',
        'stock' => null,
    ]);
    $order = createStockServiceTestOrder($product, 2, $variant);

    app(StockService::class)->reserve($order);

    expect($product->fresh()->stock)->toBe(3)
        ->and($variant->fresh()->stock)->toBeNull();
    $this->assertDatabaseHas('inventory_movements', [
        'order_id' => $order->id,
        'delta' => -2,
        'stock_target' => 'product',
    ]);
});
