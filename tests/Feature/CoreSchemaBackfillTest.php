<?php

use App\Filament\Resources\ProductResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('backfills variants, order snapshots, inventory movements, and status history without dropping legacy columns', function () {
    $category = Category::query()->create([
        'name' => 'Migration test category',
        'slug' => Str::uuid()->toString(),
        'active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Legacy product name',
        'price' => 100,
        'stock' => 7,
        'variants' => [
            ['value' => 'M', 'sku' => 'LEGACY-M', 'stock' => 10, 'price_override' => 120],
        ],
        'active' => true,
    ]);
    $customer = User::factory()->create();
    $order = Order::query()->create([
        'order_number' => 'BK-'.Str::upper(Str::random(10)),
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'variant_value' => 'M',
        'variant_stock_reduced' => true,
        'customer_name' => $customer->name,
        'phone' => '01700000000',
        'address' => 'Original delivery address',
        'total' => 240,
        'status' => 'confirmed',
    ]);

    Schema::dropIfExists('order_items');
    Schema::dropIfExists('product_variants');

    $variantAndItemMigration = require database_path('migrations/2026_11_07_062543_create_product_variants_and_order_items_tables.php');
    $variantAndItemMigration->up();

    $variant = $product->productVariants()->firstOrFail();
    $item = $order->items()->firstOrFail();

    expect($variant->value)->toBe('M')
        ->and($variant->sku)->toBe('LEGACY-M')
        ->and($variant->stock)->toBeNull()
        ->and((float) $variant->price_override)->toBe(120.0)
        ->and($item->product_name)->toBe('Legacy product name')
        ->and($item->variant_id)->toBe($variant->id)
        ->and((float) $item->unit_price)->toBe(120.0)
        ->and((float) $item->line_total)->toBe(240.0);

    Schema::dropIfExists('order_status_histories');
    Schema::dropIfExists('inventory_movements');
    $movementAndHistoryMigration = require database_path('migrations/2026_11_07_062545_create_inventory_movements_and_order_status_histories_tables.php');
    $movementAndHistoryMigration->up();

    $this->assertDatabaseHas('inventory_movements', [
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'order_id' => $order->id,
        'delta' => -2,
        'reason' => 'sale',
        'stock_target' => 'product',
    ]);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from' => null,
        'to' => 'confirmed',
        'source' => 'system',
    ]);
    expect(Schema::hasColumn('orders', 'product_id'))
        ->toBeTrue()
        ->and(Schema::hasColumn('orders', 'variant_value'))->toBeTrue()
        ->and((int) DB::table('products')->where('id', $product->id)->value('stock'))->toBe(7);
});

it('synchronizes repeater variants and enforces a single stock mode', function () {
    $category = Category::query()->create([
        'name' => 'Variant resource category',
        'slug' => Str::uuid()->toString(),
        'active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Variant resource product',
        'price' => 100,
        'variants' => [
            ['value' => 'M', 'sku' => 'SHIRT-M', 'stock' => 4, 'price_override' => 110],
            ['value' => 'L', 'sku' => 'SHIRT-L', 'stock' => 2, 'price_override' => null],
        ],
        'active' => true,
    ]);

    $variants = $product->productVariants()->orderBy('value')->get();

    expect($variants)->toHaveCount(2)
        ->and($variants[0]->sku)->toBe('SHIRT-L')
        ->and($variants[0]->stock)->toBe(2)
        ->and($variants[1]->sku)->toBe('SHIRT-M')
        ->and($variants[1]->stock)->toBe(4);
    expect(ProductResource::prepareVariantInventory([
        'stock' => 9,
        'variants' => [
            ['value' => 'M', 'stock' => 4],
            ['value' => 'L', 'stock' => 2],
        ],
    ])['stock'])->toBeNull();
    expect(fn () => ProductResource::prepareVariantInventory([
        'variants' => [
            ['value' => 'M', 'stock' => 4],
            ['value' => 'L', 'stock' => null],
        ],
    ]))->toThrow(ValidationException::class);
});
