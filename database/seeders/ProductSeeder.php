<?php

namespace Database\Seeders;

use App\Http\Controllers\ProductController;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Locate the existing producer (User ID 2 Owen Mpay or first available producer)
        $producer = User::where('role', 'producer')->find(2) ?? User::where('role', 'producer')->first();

        if (!$producer) {
            $this->command?->warn('Aucun producteur trouvé en base pour associer les produits.');
            return;
        }

        $defaultProducts = ProductController::getDefaultProducts();

        foreach ($defaultProducts as $item) {
            // Category resolution
            $categoryName = $item['categorie'] ?? 'Produit agricole';
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName]
            );

            // Region resolution from "Region (City)"
            $rawRegion = $item['region'] ?? 'Centre';
            $region = $rawRegion;
            $city = 'Cameroun';
            if (preg_match('/^(.*?)\s*\((.*?)\)$/', $rawRegion, $matches)) {
                $region = trim($matches[1]);
                $city = trim($matches[2]);
            }

            $location = Location::firstOrCreate(
                [
                    'user_id' => $producer->id,
                    'region' => $region,
                ],
                [
                    'country' => 'Cameroun',
                    'city' => $city,
                    'is_primary' => false,
                ]
            );

            $product = Product::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'producer_id' => $producer->id,
                    'category_id' => $category->id,
                    'location_id' => $location->id,
                    'name' => $item['nom'],
                    'description' => $item['description'] ?? '',
                    'price' => $item['prix_num'] ?? 1000,
                    'unit' => $item['unite'] ?? 'kg',
                    'stock_quantity' => $item['stock'] ?? 100,
                    'minimum_order' => 1,
                    'is_available' => true,
                    'status' => 'published',
                    'featured_image' => $item['image'] ?? null,
                    'published_at' => now(),
                ]
            );

            if (!empty($item['image'])) {
                ProductImage::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'image_path' => $item['image'],
                    ],
                    [
                        'is_primary' => true,
                        'sort_order' => 1,
                    ]
                );
            }
        }

        $this->command?->info('Seeding des produits terminé avec succès (' . count($defaultProducts) . ' produits).');
    }
}

