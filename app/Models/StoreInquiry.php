<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreInquiry extends Model
{
    protected $fillable = [
        'customer_name',
        'email',
        'phone',
        'inquiry_type',
        'product_name',
        'message',
        'status',
    ];
}
