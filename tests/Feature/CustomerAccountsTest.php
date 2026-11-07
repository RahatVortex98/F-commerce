<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createCustomerAccountTestProduct(): Product
{
    $category = Category::query()->create([
        'name' => 'Test category',
        'slug' => Str::uuid()->toString(),
        'active' => true,
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Test product',
        'price' => 100,
        'active' => true,
    ]);
}

function createCustomerAccountTestOrder(User $customer, Product $product, array $attributes = []): Order
{
    $order = Order::query()->create(array_merge([
        'order_number' => 'BK-'.Str::upper(Str::random(10)),
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'customer_name' => $customer->name,
        'phone' => '01700000000',
        'address' => 'Test delivery address',
        'total' => 100,
        'status' => 'pending',
    ], $attributes));

    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'variant_value' => $order->variant_value,
        'unit_price' => (float) $order->total / max(1, $order->quantity),
        'quantity' => $order->quantity,
        'line_total' => $order->total,
    ]);

    return $order;
}

it('registers a customer and signs them in', function () {
    $this->post(route('customer.register.store'), [
        'name' => 'Samira Ahmed',
        'email' => 'SAMIRA@example.test',
        'password' => 'Customer123!',
        'password_confirmation' => 'Customer123!',
        'is_admin' => true,
    ])->assertRedirect(route('account.dashboard'));

    $customer = User::query()->where('email', 'samira@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($customer, 'customer');
    $this->assertDatabaseHas('users', [
        'name' => 'Samira Ahmed',
        'email' => 'samira@example.test',
        'is_admin' => false,
    ]);
});

it('lets registered customers sign in', function () {
    $customer = User::factory()->create([
        'email' => 'customer@example.test',
        'password' => 'Customer123!',
    ]);

    $this->post(route('customer.login.store'), [
        'email' => 'CUSTOMER@example.test',
        'password' => 'Customer123!',
    ])->assertRedirect(route('account.dashboard'));

    $this->assertAuthenticatedAs($customer, 'customer');
});

it('keeps administrator credentials and sessions out of customer pages', function () {
    $admin = User::factory()->create([
        'email' => 'admin@example.test',
        'password' => 'AdminPassword123!',
        'is_admin' => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('customer.login.store'), [
            'email' => 'admin@example.test',
            'password' => 'AdminPassword123!',
        ])
        ->assertSessionHasErrors('email');

    $this->assertGuest('customer');
    $this->assertAuthenticatedAs($admin, 'admin');
    $this->get(route('account.dashboard'))->assertRedirect(route('customer.login'));

    $product = createCustomerAccountTestProduct();
    $this->get(route('checkout', $product))->assertRedirect(route('customer.login'));
});

it('shows the signed-in customer name without an admin entry in storefront navigation', function () {
    $customer = User::factory()->create(['name' => 'Samira Ahmed']);

    $this->actingAs($customer, 'customer')
        ->get(route('shop'))
        ->assertOk()
        ->assertSeeText('Samira Ahmed')
        ->assertDontSee('Admin');
});

it('hides the administrator sign-in link from the customer login page', function () {
    $this->get(route('customer.login'))
        ->assertOk()
        ->assertDontSee('Admin')
        ->assertDontSee('Store administrator');
});

it('logs out a customer without ending the administrator session', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = User::factory()->create();

    $this->actingAs($admin, 'admin')
        ->actingAs($customer, 'customer')
        ->post(route('customer.logout'))
        ->assertRedirect(route('shop'));

    $this->assertGuest('customer');
    $this->assertAuthenticatedAs($admin, 'admin');
});

it('requires a customer account before checkout', function () {
    $product = createCustomerAccountTestProduct();

    $this->get(route('checkout', $product))
        ->assertRedirect(route('customer.login'));
});

it('limits order visibility to its customer and allows receipt confirmation after delivery', function () {
    $customer = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $product = createCustomerAccountTestProduct();
    $customerOrder = createCustomerAccountTestOrder($customer, $product, [
        'status' => 'delivered',
    ]);
    $otherOrder = createCustomerAccountTestOrder($otherCustomer, $product);

    $this->actingAs($customer, 'customer')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSeeText('Good to see you, '.$customer->name)
        ->assertSee($customerOrder->order_number)
        ->assertDontSee($otherOrder->order_number);

    $this->get(route('account.orders.show', $otherOrder))->assertNotFound();
    $product->update(['name' => 'Renamed after purchase']);
    $this->get(route('account.orders.show', $customerOrder))
        ->assertOk()
        ->assertSeeText('Test product')
        ->assertSeeText('Delivered');

    $this->post(route('account.orders.received', $customerOrder))
        ->assertRedirect(route('account.orders.show', $customerOrder));

    expect($customerOrder->fresh()->customer_received_at)->not->toBeNull();
});

it('does not allow customers to confirm an order that is not delivered', function () {
    $customer = User::factory()->create();
    $product = createCustomerAccountTestProduct();
    $order = createCustomerAccountTestOrder($customer, $product, [
        'status' => 'shipped',
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('account.orders.received', $order))
        ->assertStatus(409);

    expect($order->fresh()->customer_received_at)->toBeNull();
});

it('shows admin dashboard summaries only to administrators', function () {
    $this->app['env'] = 'local';
    config(['temporary_admin.email' => null]);

    $customer = User::factory()->create();
    $this->actingAs($customer, 'customer')->get('/bikrify')->assertRedirect();

    $admin = User::factory()->create();
    $admin->name = 'Spidy';
    $admin->forceFill(['is_admin' => true])->save();
    StoreSetting::query()->create([
        'store_name' => 'Spider Man',
        'owner_name' => 'Spidy',
    ]);
    $product = createCustomerAccountTestProduct();
    createCustomerAccountTestOrder($customer, $product, ['status' => 'delivered']);

    $this->actingAs($admin, 'admin')
        ->get('/bikrify')
        ->assertOk()
        ->assertSeeText('Active products')
        ->assertSeeText('Customer accounts')
        ->assertSeeText('Orders by status')
        ->assertSeeText('Orders over the last 7 days')
        ->assertSeeText('Spider Man')
        ->assertSeeText('Spidy');
});
