<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('sku')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'value']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_value')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
            $table->index('order_id');
            $table->index('product_id');
        });

        DB::table('products')
            ->whereNotNull('variants')
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    $variants = json_decode((string) $product->variants, true);
                    if (! is_array($variants)) {
                        continue;
                    }

                    foreach ($variants as $variant) {
                        if (! is_array($variant) || ! isset($variant['value']) || ! is_string($variant['value'])) {
                            continue;
                        }

                        DB::table('product_variants')->insert([
                            'product_id' => $product->id,
                            'value' => $variant['value'],
                            'sku' => isset($variant['sku']) && is_string($variant['sku']) ? $variant['sku'] : null,
                            // Imported variants use the legacy product-level pool until per-variant counts are set.
                            'stock' => null,
                            'price_override' => isset($variant['price_override']) && is_numeric($variant['price_override'])
                                ? $variant['price_override']
                                : null,
                            'created_at' => $product->created_at ?? now(),
                            'updated_at' => $product->updated_at ?? now(),
                        ]);
                    }
                }
            });

        DB::table('orders')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    $product = DB::table('products')->where('id', $order->product_id)->first();
                    if (! $product) {
                        continue;
                    }

                    $quantity = max(1, (int) $order->quantity);
                    $variant = $order->variant_value === null
                        ? null
                        : DB::table('product_variants')
                            ->where('product_id', $order->product_id)
                            ->where('value', $order->variant_value)
                            ->first();

                    DB::table('order_items')->insert([
                        'order_id' => $order->id,
                        'product_id' => $order->product_id,
                        'variant_id' => $variant?->id,
                        'product_name' => $product->name,
                        'variant_value' => $order->variant_value,
                        'unit_price' => round((float) $order->total / $quantity, 2),
                        'quantity' => $quantity,
                        'line_total' => $order->total,
                        'created_at' => $order->created_at ?? now(),
                        'updated_at' => $order->updated_at ?? now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('product_variants');
    }
};
