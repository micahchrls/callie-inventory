<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\Platform;

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
            $this->createSingleVariantForProduct($product);
        }
    }

    private function createSingleVariantForProduct(Product $product): void
    {
        // Create a single default variant for each product
        $materials = ['14K Gold', '18K Gold', 'Sterling Silver', 'Rose Gold'];
        $sizes = ['One Size', 'Standard', 'Regular', 'Default'];

        $randomMaterial = $materials[array_rand($materials)];
        $randomSize = $sizes[array_rand($sizes)];

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $this->generateSKU($product->name, $randomMaterial, $randomSize),
            'variation_name' => "{$randomSize} {$randomMaterial}",
            'size' => $randomSize,
            'material' => $randomMaterial,
            'platform_id' => $this->platformIds[array_rand($this->platformIds)],
            'quantity_in_stock' => rand(10, 100),
            'reorder_level' => rand(5, 20),
            'is_active' => true,
        ]);
    }

    private function generateSKU(string $productName, string $material, string $size): string
    {
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, 6));
        $materialCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $material), 0, 3));
        $sizeCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $size), 0, 3));

        return $productCode . '-' . $materialCode . '-' . $sizeCode . '-' . rand(100, 999);
    }
}
