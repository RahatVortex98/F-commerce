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
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('status')->default('pending')->change();
            $table->decimal('subtotal', 10, 2)->default(0)->after('total');
            $table->decimal('delivery_charge', 10, 2)->default(0)->after('subtotal');
            $table->decimal('discount_total', 10, 2)->default(0)->after('delivery_charge');
            $table->enum('stock_state', ['none', 'reserved', 'released'])->default('none')->after('status');
            $table->softDeletes();
            $table->index('status');
            $table->index('created_at');
            $table->index('phone');
            $table->index(['user_id', 'created_at']);
            $table->index('courier_booking_status');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->softDeletes();
            $table->decimal('weight', 8, 3)->nullable()->default(0.500)->after('price');
            $table->index(['active', 'name']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->softDeletes();
        });

        DB::table('orders')->orderBy('id')->chunkById(100, function ($orders): void {
            foreach ($orders as $order) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update([
                        'subtotal' => $order->total,
                        'delivery_charge' => 0,
                        'discount_total' => 0,
                        'stock_state' => $order->variant_stock_reduced ? 'reserved' : 'none',
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['active', 'name']);
            $table->dropSoftDeletes();
            $table->dropColumn('weight');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['courier_booking_status']);
            $table->dropSoftDeletes();
            $table->dropColumn(['subtotal', 'delivery_charge', 'discount_total', 'stock_state']);
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered', 'returned', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }
};
