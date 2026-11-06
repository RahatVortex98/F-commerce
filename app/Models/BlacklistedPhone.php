<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedPhone extends Model
{
    protected $fillable = ['phone', 'reason', 'return_count'];
}
