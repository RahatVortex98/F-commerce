<?php

use App\Enums\OrderStockState;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createCheckoutStockTestProduct(?int $stock): Product
{
    $category = Category::query()->create([
        'name' => 'Checkout test category',
        'slug' => Str::uuid()->toString(),
        'active' => true,
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Checkout stock product',
        'price' => 80,
        'stock' => $stock,
        'active' => true,
    ]);
}

function checkoutStockTestPayload(string $checkoutToken, int $quantity = 2): array
{
    return [
        'checkout_token' => $checkoutToken,
        'phone' => '01700000000',
        'address' => 'Checkout test address',
        'quantity' => $quantity,
    ];
}

beforeEach(function (): void {
    config([
        'sms.driver' => 'log',
        'courierhub.couriers.pathao.enabled' => false,
    ]);
});

it('reserves a simple product at order creation before phone verification', function () {
    $customer = User::factory()->create();
    $product = createCheckoutStockTestProduct(5);

    $response = $this->actingAs($customer, 'customer')
        ->post(route('checkout.store', $product), checkoutStockTestPayload((string) Str::uuid()));

    $order = Order::query()->firstOrFail();

    $response->assertRedirect(route('order.verify', $order->public_token));
    expect($product->fresh()->stock)->toBe(3)
        ->and($order->stock_state)->toBe(OrderStockState::Reserved)
        ->and($order->phone_verified_at)->toBeNull();
    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_name' => 'Checkout stock product',
        'quantity' => 2,
        'unit_price' => 80,
        'line_total' => 160,
    ]);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from' => null,
        'to' => 'pending',
        'source' => 'customer',
    ]);
});

it('does not create an order or decrement stock when the line is short', function () {
    $customer = User::factory()->create();
    $product = createCheckoutStockTestProduct(1);

    $response = $this->actingAs($customer, 'customer')
        ->from(route('checkout', $product))
        ->post(route('checkout.store', $product), checkoutStockTestPayload((string) Str::uuid()));

    $response->assertSessionHasErrors('quantity');
    expect($product->fresh()->stock)->toBe(1)
        ->and(Order::query()->count())->toBe(0)
        ->and(OrderItem::query()->count())->toBe(0);
});

it('shows per-variant availability on the product and checkout pages', function () {
    $customer = User::factory()->create();
    $product = createCheckoutStockTestProduct(null);
    $product->update([
        'product_kind' => 'shirt',
        'variants' => [
            ['value' => 'M', 'stock' => 3],
        ],
    ]);

    $this->get(route('product.show', $product))
        ->assertOk()
        ->assertSeeText('M · 3 available');
    $this->actingAs($customer, 'customer')
        ->get(route('checkout', $product))
        ->assertOk()
        ->assertSeeText('M — 3 available');
});

it('does not reserve stock a second time for an idempotent checkout resubmission', function () {
    $customer = User::factory()->create();
    $product = createCheckoutStockTestProduct(25);
    $payload = checkoutStockTestPayload((string) Str::uuid(), 25);
    $route = route('checkout.store', $product);

    $this->actingAs($customer, 'customer')->post($route, $payload);
    $firstOrder = Order::query()->firstOrFail();
    $product->update(['active' => false]);
    $response = $this->actingAs($customer, 'customer')->post($route, $payload);

    $response->assertRedirect(route('order.verify', $firstOrder->public_token));
    expect($product->fresh()->stock)->toBe(0)
        ->and(Order::query()->count())->toBe(1)
        ->and(OrderItem::query()->count())->toBe(1);
});
