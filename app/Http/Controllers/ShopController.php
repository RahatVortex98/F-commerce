<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class ShopController extends Controller
{
    public function index()
    {
        $products = Product::query()->where('active', true)->with('category')->orderBy('name')->get();
        $discountedProducts = $products->filter(fn (Product $product) => $product->hasActiveDiscount());
        $categories = Category::query()->where('active', true)->orderBy('name')->get();

        return view('shop', compact('products', 'categories', 'discountedProducts'));
    }

    public function show(Product $product)
    {
        abort_unless($product->active, 404);
        $product->load('productVariants');

        return view('product', compact('product'));
    }
}
