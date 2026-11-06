<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'product_id', 'quantity', 'variant_value', 'variant_stock_reduced', 'customer_name',
        'phone', 'address', 'total', 'status',
        'courier_name', 'courier_tracking_id', 'owner_confirmed_at', 'owner_rejected_at',
        'customer_received_at',
        'checkout_token', 'public_token', 'phone_e164', 'phone_verification_hash',
        'phone_verification_expires_at', 'phone_verification_attempts',
        'phone_verified_at', 'city_id', 'zone_id', 'area_id',
        'courier_booking_status', 'courier_booking_started_at', 'courier_booking_error',
    ];

    protected function casts(): array
    {
        return [
            'phone_verification_expires_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'courier_booking_started_at' => 'datetime',
            'variant_stock_reduced' => 'boolean',
            'owner_confirmed_at' => 'datetime',
            'owner_rejected_at' => 'datetime',
            'customer_received_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
