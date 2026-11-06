<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 120);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('inquiry_type', 30)->index();
            $table->string('product_name')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_inquiries');
    }
};
