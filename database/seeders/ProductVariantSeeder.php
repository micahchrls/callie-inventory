<?php

namespace Database\Seeders;

use App\Models\Platform;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    private array $platformIds = [];

    public function run(): void
    {
        // Initialize platform IDs array
        $this->platformIds = Platform::pluck('id')->toArray();

        // Ensure we have platforms to work with
        if (empty($this->platformIds)) {
            throw new \Exception('No platforms found. Please run PlatformSeeder first.');
        }

        $products = Product::all();

        foreach ($products as $product) {
            $this->createTwoVariantsForProduct($product);
        }
    }

    private function createTwoVariantsForProduct(Product $product): void
    {
        // Create exactly 2 variants for each product
        $variants = [
            [
                'material' => '14K Gold',
                'size' => 'Small',
                'variation_name' => 'Small 14K Gold',
            ],
            [
                'material' => '18K Gold',
                'size' => 'Large',
                'variation_name' => 'Large 18K Gold',
            ],
        ];

        foreach ($variants as $variant) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $this->generateSKU($product->name, $variant['material'], $variant['size']),
                'variation_name' => $variant['variation_name'],
                'size' => $variant['size'],
                'material' => $variant['material'],
                'platform_id' => $this->platformIds[array_rand($this->platformIds)],
                'quantity_in_stock' => rand(10, 100),
                'reorder_level' => rand(5, 20),
                'is_active' => true,
            ]);
        }
    }

    private function generateSKU(string $productName, string $material, string $size): string
    {
        // Generate SKU based on product name, material, and size
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $productName), 0, 3));
        $materialCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $material), 0, 2));
        $sizeCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $size), 0, 2));

        return $productCode.'-'.$materialCode.'-'.$sizeCode.'-'.rand(1000, 9999);
    }
}
