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
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('checkout_token')->nullable()->unique();
            $table->uuid('public_token')->nullable()->unique();
            $table->string('phone_e164', 16)->nullable()->index();
            $table->string('phone_verification_hash')->nullable();
            $table->timestamp('phone_verification_expires_at')->nullable();
            $table->unsignedTinyInteger('phone_verification_attempts')->default(0);
            $table->timestamp('phone_verified_at')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->unsignedInteger('zone_id')->nullable();
            $table->unsignedInteger('area_id')->nullable();
            $table->string('courier_booking_status')->default('not_started');
            $table->timestamp('courier_booking_started_at')->nullable();
            $table->text('courier_booking_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropUnique(['checkout_token']);
            $table->dropIndex(['phone_e164']);
            $table->dropColumn([
                'checkout_token',
                'public_token',
                'phone_e164',
                'phone_verification_hash',
                'phone_verification_expires_at',
                'phone_verification_attempts',
                'phone_verified_at',
                'city_id',
                'zone_id',
                'area_id',
                'courier_booking_status',
                'courier_booking_started_at',
                'courier_booking_error',
            ]);
        });
    }
};
