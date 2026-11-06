<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
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
    return Order::query()->create(array_merge([
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
}

it('registers a customer and signs them in', function () {
    $this->post(route('customer.register.store'), [
        'name' => 'Samira Ahmed',
        'email' => 'SAMIRA@example.test',
        'password' => 'Customer123!',
        'password_confirmation' => 'Customer123!',
        'is_admin' => true,
    ])->assertRedirect(route('account.dashboard'));

    $this->assertAuthenticated();
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

    $this->assertAuthenticatedAs($customer);
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

    $this->actingAs($customer)
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee($customerOrder->order_number)
        ->assertDontSee($otherOrder->order_number);

    $this->get(route('account.orders.show', $otherOrder))->assertNotFound();

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

    $this->actingAs($customer)
        ->post(route('account.orders.received', $order))
        ->assertStatus(409);

    expect($order->fresh()->customer_received_at)->toBeNull();
});

it('shows admin dashboard summaries only to administrators', function () {
    $this->app['env'] = 'local';
    config(['temporary_admin.email' => null]);

    $customer = User::factory()->create();
    $this->actingAs($customer)->get('/bikrify')->assertForbidden();

    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $product = createCustomerAccountTestProduct();
    createCustomerAccountTestOrder($customer, $product, ['status' => 'delivered']);

    $this->actingAs($admin)
        ->get('/bikrify')
        ->assertOk()
        ->assertSeeText('Active products')
        ->assertSeeText('Customer accounts')
        ->assertSeeText('Orders by status')
        ->assertSeeText('Orders over the last 7 days');
});
