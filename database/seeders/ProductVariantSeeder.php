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
            $this->createVariantsForProduct($product);
        }
    }

    private function createVariantsForProduct(Product $product): void
    {
        $categoryName = $product->productCategory->name ?? '';
        $subCategoryName = $product->productSubCategory->name ?? '';

        switch ($categoryName) {
            case 'Earrings':
                $this->createEarringVariants($product, $subCategoryName);
                break;
            case 'Necklaces':
                $this->createNecklaceVariants($product, $subCategoryName);
                break;
            case 'Rings':
                $this->createRingVariants($product, $subCategoryName);
                break;
            case 'Bracelets':
                $this->createBraceletVariants($product, $subCategoryName);
                break;
            case 'Pendants':
                $this->createPendantVariants($product, $subCategoryName);
                break;
            case 'Watches':
                $this->createWatchVariants($product, $subCategoryName);
                break;
            default:
                $this->createDefaultVariants($product);
                break;
        }
    }

    private function createEarringVariants(Product $product, string $subCategory): void
    {
        $materials = ['14K Gold', '18K Gold', 'Sterling Silver', 'Rose Gold'];
        $sizes = ['Small', 'Medium', 'Large'];

        foreach ($materials as $material) {
            foreach ($sizes as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $this->generateSKU($product->name, $material, $size),
                    'variation_name' => "{$size} {$material}",
                    'size' => $size,
                    'material' => $material,
                    'quantity_in_stock' => rand(0, 50),
                    'reorder_level' => rand(5, 15),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createNecklaceVariants(Product $product, string $subCategory): void
    {
        $materials = ['14K Gold', '18K Gold', 'Sterling Silver', 'Rose Gold'];
        $lengths = ['16"', '18"', '20"', '24"'];

        foreach ($materials as $material) {
            foreach ($lengths as $length) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $this->generateSKU($product->name, $material, $length),
                    'variation_name' => "{$length} {$material}",
                    'size' => $length,
                    'material' => $material,
                    'quantity_in_stock' => rand(0, 30),
                    'reorder_level' => rand(5, 10),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createRingVariants(Product $product, string $subCategory): void
    {
        $materials = ['14K Gold', '18K Gold', 'Sterling Silver', 'Platinum'];
        $sizes = ['5', '6', '7', '8', '9'];

        foreach ($materials as $material) {
            foreach ($sizes as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $this->generateSKU($product->name, $material, "Size {$size}"),
                    'variation_name' => "Size {$size} {$material}",
                    'size' => $size,
                    'material' => $material,
                    'quantity_in_stock' => rand(0, 20),
                    'reorder_level' => rand(3, 8),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createBraceletVariants(Product $product, string $subCategory): void
    {
        $materials = ['14K Gold', '18K Gold', 'Sterling Silver', 'Rose Gold'];
        $sizes = ['6.5"', '7"', '7.5"', '8"'];

        foreach ($materials as $material) {
            foreach ($sizes as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $this->generateSKU($product->name, $material, $size),
                    'variation_name' => "{$size} {$material}",
                    'size' => $size,
                    'material' => $material,
                    'quantity_in_stock' => rand(0, 25),
                    'reorder_level' => rand(5, 10),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createPendantVariants(Product $product, string $subCategory): void
    {
        $materials = ['14K Gold', '18K Gold', 'Sterling Silver', 'Rose Gold'];
        $gemstones = ['Diamond', 'Ruby', 'Sapphire', 'Emerald'];

        foreach ($materials as $material) {
            foreach ($gemstones as $gemstone) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $this->generateSKU($product->name, $material, $gemstone),
                    'variation_name' => "{$gemstone} {$material}",
                    'color' => $this->getGemstoneColor($gemstone),
                    'material' => $material,
                    'additional_attributes' => json_encode(['gemstone' => $gemstone]),
                    'quantity_in_stock' => rand(0, 15),
                    'reorder_level' => rand(3, 8),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createWatchVariants(Product $product, string $subCategory): void
    {
        $materials = ['Stainless Steel', 'Gold-Plated', 'Leather Band'];
        $colors = ['Black', 'Silver', 'Gold', 'Brown'];

        foreach ($materials as $material) {
            foreach ($colors as $color) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $this->generateSKU($product->name, $material, $color),
                    'variation_name' => "{$color} {$material}",
                    'color' => $color,
                    'material' => $material,
                    'platform_id' => $this->platformIds[array_rand($this->platformIds)],
                    'quantity_in_stock' => rand(0, 10),
                    'reorder_level' => rand(2, 5),
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createDefaultVariants(Product $product): void
    {
        $materials = ['14K Gold', 'Sterling Silver'];
        $colors = ['Gold', 'Silver'];

        foreach ($materials as $index => $material) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $this->generateSKU($product->name, $material, 'Default'),
                'variation_name' => $material,
                'material' => $material,
                'color' => $colors[$index],
                'platform_id' => $this->platformIds[array_rand($this->platformIds)],
                'quantity_in_stock' => rand(5, 25),
                'reorder_level' => rand(5, 10),
                'is_active' => true,
            ]);
        }
    }

    private function generateSKU(string $productName, string $material, string $variant): string
    {
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $productName), 0, 3));
        $materialCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $material), 0, 2));
        $variantCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $variant), 0, 3));

        return $productCode . '-' . $materialCode . '-' . $variantCode . '-' . rand(1000, 9999);
    }

    private function getGemstoneColor(string $gemstone): string
    {
        $colors = [
            'Diamond' => 'Clear',
            'Ruby' => 'Red',
            'Sapphire' => 'Blue',
            'Emerald' => 'Green',
        ];

        return $colors[$gemstone] ?? 'Clear';
    }
}
