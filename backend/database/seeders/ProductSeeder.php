<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

// Sample bakery catalogue for fresh setups that don't import from Supabase
// (use `php artisan products:import-from-supabase` for the real ~76 products).
// Idempotent: keyed by name, so re-running updates instead of duplicating.
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->products() as $p) {
            Product::updateOrCreate(
                ['name' => $p['name']],
                $p + [
                    'original_price' => $p['original_price'] ?? null,
                    'features' => $p['features'] ?? [],
                    'calories' => $p['calories'] ?? null,
                    'is_featured' => $p['is_featured'] ?? false,
                    'status' => $p['status'] ?? null,
                    'archived_at' => null,
                ]
            );
        }
    }

    private function products(): array
    {
        $img = fn ($id) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w=600&q=80";

        return [
            // --- Cakes ---
            [
                'name' => 'Classic Mocha Cake', 'category' => 'Cake', 'price' => 650, 'original_price' => 720,
                'description' => 'Moist chocolate sponge layered with mocha buttercream and a dusting of cocoa.',
                'image_path' => $img('1565958011703-44f9829ba187'), 'calories' => 420, 'features' => ['Gluten', 'Eggs', 'Milk'],
                'is_featured' => true, 'status' => 'best_seller',
            ],
            [
                'name' => 'Ube Chiffon Cake', 'category' => 'Cake', 'price' => 720,
                'description' => 'Light-as-air purple yam chiffon with sweet ube halaya swirl.',
                'image_path' => $img('1488477181946-6428a0291777'), 'calories' => 380, 'features' => ['Gluten', 'Eggs', 'Milk'],
                'status' => 'new',
            ],
            [
                'name' => 'Red Velvet Slice', 'category' => 'Cake', 'price' => 150,
                'description' => 'Velvety cocoa cake with tangy cream cheese frosting.',
                'image_path' => $img('1586985289688-ca3cf47d3e6e'), 'calories' => 410, 'features' => ['Gluten', 'Eggs', 'Milk'],
                'status' => 'new',
            ],

            // --- Breads ---
            [
                'name' => 'Soft Ensaymada', 'category' => 'Bread', 'price' => 45,
                'description' => 'Buttery brioche topped with cheese and a sprinkle of sugar.',
                'image_path' => $img('1509440159596-0249088772ff'), 'calories' => 290, 'features' => ['Gluten', 'Eggs', 'Milk'],
                'status' => 'best_seller',
            ],
            [
                'name' => 'Fresh Pandesal (12pcs)', 'category' => 'Bread', 'price' => 60,
                'description' => 'The classic Filipino breakfast roll, baked fresh every morning.',
                'image_path' => $img('1549931319-a545dcf3bc73'), 'calories' => 140, 'features' => ['Gluten', 'Soy'],
            ],
            [
                'name' => 'Whole Wheat Loaf', 'category' => 'Bread', 'price' => 95,
                'description' => 'Hearty whole-wheat sandwich loaf, soft and lightly sweet.',
                'image_path' => $img('1598373182133-52452f7691ef'), 'calories' => 220, 'features' => ['Gluten', 'Soy'],
            ],

            // --- Pastries ---
            [
                'name' => 'Buttery Croissant', 'category' => 'Pastry', 'price' => 85,
                'description' => 'Flaky, golden, 24-hour laminated croissant.',
                'image_path' => $img('1555507036-ab1f4038808a'), 'calories' => 270, 'features' => ['Gluten', 'Milk'],
                'status' => 'best_seller',
            ],
            [
                'name' => 'Chocolate Danish', 'category' => 'Pastry', 'price' => 95,
                'description' => 'Buttery danish pastry filled with rich dark chocolate.',
                'image_path' => $img('1509365390695-33acd1f0c6c2'), 'calories' => 320, 'features' => ['Gluten', 'Eggs', 'Milk'],
                'status' => 'new',
            ],

            // --- Cupcakes ---
            [
                'name' => 'Chocolate Cupcakes', 'category' => 'Cupcakes', 'price' => 180,
                'description' => 'Box of rich chocolate cupcakes with fudge frosting.',
                'image_path' => $img('1426869981800-95ebf51ce900'), 'calories' => 300, 'features' => ['Gluten', 'Eggs', 'Milk'],
            ],

            // --- Cookies ---
            [
                'name' => 'Assorted Cookies', 'category' => 'Cookies', 'price' => 220, 'original_price' => 260,
                'description' => 'A dozen freshly baked cookies — choc chip, oatmeal, and double chocolate.',
                'image_path' => $img('1499636136210-6f4ee915583e'), 'calories' => 150, 'features' => ['Gluten', 'Eggs', 'Milk'],
                'is_featured' => true,
            ],

            // --- Beverages ---
            [
                'name' => 'Iced Americano', 'category' => 'Drinks', 'price' => 110,
                'description' => 'Bold espresso over ice — the perfect pairing for any treat.',
                'image_path' => $img('1517701550927-30cf4ba1dba5'), 'calories' => 15, 'features' => [],
            ],
            [
                'name' => 'Hot Chocolate', 'category' => 'Drinks', 'price' => 120,
                'description' => 'Velvety dark hot chocolate, topped with marshmallows.',
                'image_path' => $img('1542990253-0d0f5be5f0ed'), 'calories' => 250, 'features' => ['Milk'],
                'status' => 'sold_out',
            ],
        ];
    }
}
