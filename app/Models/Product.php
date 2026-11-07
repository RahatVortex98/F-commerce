<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'name', 'product_kind', 'stock', 'weight', 'description', 'price', 'sale_price', 'sale_ends_at', 'image', 'gallery', 'variants', 'active'];

    protected function casts(): array
    {
        return [
            'sale_ends_at' => 'datetime',
            'stock' => 'integer',
            'weight' => 'decimal:3',
            'gallery' => 'array',
            'variants' => 'array',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            foreach ($product->variants ?? [] as $variantData) {
                if (! is_array($variantData) || ! isset($variantData['value']) || ! is_string($variantData['value'])) {
                    continue;
                }

                $variant = $product->productVariants()->firstOrNew(['value' => $variantData['value']]);
                $variant->fill([
                    'sku' => isset($variantData['sku']) && is_string($variantData['sku']) ? $variantData['sku'] : $variant->sku,
                    'price_override' => isset($variantData['price_override']) && is_numeric($variantData['price_override'])
                        ? $variantData['price_override']
                        : $variant->price_override,
                ]);

                if (array_key_exists('stock', $variantData)) {
                    $variant->stock = is_numeric($variantData['stock']) ? (int) $variantData['stock'] : null;
                }

                $variant->save();
            }
        });
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

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
