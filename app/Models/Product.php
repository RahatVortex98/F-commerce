<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = ['category_id', 'name', 'product_kind', 'stock', 'description', 'price', 'sale_price', 'sale_ends_at', 'image', 'gallery', 'variants', 'active'];

    protected function casts(): array
    {
        return ['sale_ends_at' => 'datetime', 'stock' => 'integer', 'gallery' => 'array', 'variants' => 'array', 'active' => 'boolean'];
    }

    public function hasActiveDiscount(): bool
    {
        return $this->sale_price !== null
            && (float) $this->sale_price > 0
            && (float) $this->sale_price < (float) $this->price
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());
    }

    public function currentPrice(): float
    {
        return $this->hasActiveDiscount() ? (float) $this->sale_price : (float) $this->price;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
