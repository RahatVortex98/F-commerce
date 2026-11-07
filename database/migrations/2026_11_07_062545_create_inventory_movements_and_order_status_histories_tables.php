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
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('delta');
            $table->enum('reason', ['sale', 'cancel', 'return', 'restock', 'adjustment', 'reject', 'expired']);
            // Records which stock bucket was decremented so releases restore the exact same inventory.
            $table->string('stock_target')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['product_id', 'created_at']);
            $table->index('variant_id');
            $table->index('order_id');
        });

        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from')->nullable();
            $table->string('to');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source', ['admin', 'customer', 'courier', 'system']);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
        });

        DB::table('orders')
            ->where('variant_stock_reduced', true)
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('inventory_movements')->insert([
                        'product_id' => $order->product_id,
                        'variant_id' => DB::table('product_variants')
                            ->where('product_id', $order->product_id)
                            ->where('value', $order->variant_value)
                            ->value('id'),
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'delta' => -$order->quantity,
                        'reason' => 'sale',
                        'stock_target' => 'product',
                        'note' => 'Backfilled from a legacy shared-stock reservation.',
                        'created_at' => $order->created_at ?? now(),
                    ]);
                }
            });

        DB::table('orders')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('order_status_histories')->insert([
                        'order_id' => $order->id,
                        'from' => null,
                        'to' => $order->status,
                        'actor_id' => null,
                        'source' => 'system',
                        'note' => 'Status imported when order history was introduced.',
                        'created_at' => $order->created_at ?? now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('inventory_movements');
    }
};
