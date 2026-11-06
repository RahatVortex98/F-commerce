<?php

namespace Database\Seeders;

use App\Models\BlacklistedPhone;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'clothing'],
            ['name' => 'Clothing'],
        );

        Product::firstOrCreate(
            ['category_id' => $category->id, 'name' => 'Cotton Panjabi'],
            [
                'description' => 'Comfortable cotton panjabi for daily wear.',
                'price' => 1250,
                'active' => true,
            ],
        );

        Product::firstOrCreate(
            ['category_id' => $category->id, 'name' => 'Casual Shirt'],
            [
                'description' => 'Everyday casual shirt.',
                'price' => 850,
                'active' => true,
            ],
        );

        BlacklistedPhone::firstOrCreate(
            ['phone' => '01700000000'],
            ['reason' => 'fake_order', 'return_count' => 3],
        );
    }
}
