<?php

namespace App\Imports;

use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class ProductInventoryImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public $imported = 0;
    public $updated = 0;
    public $skipped = 0;
    public $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row, $index + 2); // +2 because of header row and 0-based index
            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->skipped++;
                Log::error("Import error on row " . ($index + 2), [
                    'error' => $e->getMessage(),
                    'row_data' => $row->toArray()
                ]);
            }
        }
    }

    private function processRow($row, $rowNumber)
    {
        // Convert collection to array for easier access
        $data = $row->toArray();

        // Find variant by SKU first, then by product name + variation
        $variant = null;

        if (!empty($data['sku'])) {
            $variant = ProductVariant::where('sku', $data['sku'])->first();
        }

        if (!$variant && !empty($data['product_name'])) {
            // Try to find existing product and variant combination
            $product = Product::where('name', $data['product_name'])->first();
            if ($product) {
                $variationName = $data['variation_name'] ?? null;
                if ($variationName) {
                    $variant = $product->variants()
                        ->where('variation_name', $variationName)
                        ->first();
                } else {
                    // Get the first variant if no specific variation is mentioned
                    $variant = $product->variants()->first();
                }
            }
        }

        if (!$variant) {
            // Create new product and variant if they don't exist
            $variant = $this->createNewProductAndVariant($data, $rowNumber);
            if (!$variant) {
                return; // Error already logged
            }
            $this->imported++;
        } else {
            // Update existing variant
            $this->updateExistingVariant($variant, $data, $rowNumber);
            $this->updated++;
        }
    }

    private function createNewProductAndVariant($data, $rowNumber)
    {
        // Validate required fields for new product
        if (empty($data['product_name'])) {
            $this->errors[] = "Row $rowNumber: Product name is required for new products";
            $this->skipped++;
            return null;
        }

        if (empty($data['sku'])) {
            $this->errors[] = "Row $rowNumber: SKU is required for new products";
            $this->skipped++;
            return null;
        }

        // Find or create category
        $categoryId = $this->findOrCreateCategory($data['category'] ?? null);
        $subCategoryId = $this->findOrCreateSubCategory($data['sub_category'] ?? null, $categoryId);

        // Create or get existing product
        $product = Product::where('name', $data['product_name'])->first();
        if (!$product) {
            $product = Product::create([
                'name' => $data['product_name'],
                'description' => $data['product_description'] ?? '',
                'product_category_id' => $categoryId,
                'product_sub_category_id' => $subCategoryId,
            ]);
        }

        // Calculate stock quantity from shipped quantities
        $stockQtyShipped = (int) ($data['stock_qty_shipped'] ?? 0);
        $stockOutShipped = (int) ($data['stock_out_shipped'] ?? 0);
        $finalStockQty = $stockQtyShipped - $stockOutShipped;

        // Create variant
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $data['sku'],
            'variation_name' => $data['variation_name'] ?? null,
            'size' => $data['size'] ?? null,
            'color' => $data['color'] ?? null,
            'material' => $data['material'] ?? null,
            'weight' => $data['weight'] ?? null,
            'quantity_in_stock' => max(0, $finalStockQty), // Ensure non-negative
            'reorder_level' => (int) ($data['reorder_level'] ?? 5), // Default reorder level
            'location' => $data['location'] ?? '',
            'status' => $this->determineStatus($finalStockQty, (int) ($data['reorder_level'] ?? 5)),
            'notes' => $this->buildNotesFromData($data),
            'is_active' => $this->parseBoolean($data['is_active'] ?? true),
            'last_restocked_at' => $finalStockQty > 0 ? now() : null,
        ]);

        return $variant;
    }

    private function updateExistingVariant($variant, $data, $rowNumber)
    {
        $updates = [];

        // Update variation name if provided
        if (!empty($data['variation_name'])) {
            $updates['variation_name'] = $data['variation_name'];
        }

        // Update variant attributes
        if (isset($data['size'])) {
            $updates['size'] = $data['size'];
        }
        if (isset($data['color'])) {
            $updates['color'] = $data['color'];
        }
        if (isset($data['material'])) {
            $updates['material'] = $data['material'];
        }
        if (isset($data['weight'])) {
            $updates['weight'] = $data['weight'];
        }

        // Calculate and update stock quantity
        if (isset($data['stock_qty_shipped']) || isset($data['stock_out_shipped'])) {
            $stockQtyShipped = (int) ($data['stock_qty_shipped'] ?? 0);
            $stockOutShipped = (int) ($data['stock_out_shipped'] ?? 0);
            $finalStockQty = $stockQtyShipped - $stockOutShipped;

            $updates['quantity_in_stock'] = max(0, $finalStockQty); // Ensure non-negative
            if ($finalStockQty > 0) {
                $updates['last_restocked_at'] = now();
            }
        }

        // Update reorder level
        if (isset($data['reorder_level'])) {
            $updates['reorder_level'] = (int) $data['reorder_level'];
        }

        // Update location
        if (isset($data['location'])) {
            $updates['location'] = $data['location'];
        }

        // Update active status
        if (isset($data['is_active'])) {
            $updates['is_active'] = $this->parseBoolean($data['is_active']);
        }

        // Update notes if provided
        if (!empty($data['notes'])) {
            $updates['notes'] = $data['notes'];
        }

        // Update status based on new quantity and reorder level
        if (isset($updates['quantity_in_stock']) || isset($updates['reorder_level'])) {
            $newQty = $updates['quantity_in_stock'] ?? $variant->quantity_in_stock;
            $newReorderLevel = $updates['reorder_level'] ?? $variant->reorder_level;
            $updates['status'] = $this->determineStatus($newQty, $newReorderLevel);
        }

        // Update variant if there are changes
        if (!empty($updates)) {
            $variant->update($updates);
        }

        // Also update the parent product's category if provided
        if (!empty($data['category']) || !empty($data['sub_category'])) {
            $productUpdates = [];

            if (!empty($data['category'])) {
                $categoryId = $this->findOrCreateCategory($data['category']);
                $productUpdates['product_category_id'] = $categoryId;
            }

            if (!empty($data['sub_category'])) {
                $subCategoryId = $this->findOrCreateSubCategory(
                    $data['sub_category'],
                    $productUpdates['product_category_id'] ?? $variant->product->product_category_id
                );
                $productUpdates['product_sub_category_id'] = $subCategoryId;
            }

            if (!empty($productUpdates)) {
                $variant->product->update($productUpdates);
            }
        }
    }

    private function findOrCreateCategory($categoryName)
    {
        if (empty($categoryName)) {
            // Return default category ID or create a default one
            $defaultCategory = ProductCategory::firstOrCreate(
                ['name' => 'Uncategorized'],
                ['description' => 'Default category for uncategorized products']
            );
            return $defaultCategory->id;
        }

        $category = ProductCategory::firstOrCreate(
            ['name' => $categoryName],
            ['description' => "Auto-created category: {$categoryName}"]
        );

        return $category->id;
    }

    private function findOrCreateSubCategory($subCategoryName, $categoryId)
    {
        if (empty($subCategoryName)) {
            // Return default subcategory ID or create a default one
            $defaultSubCategory = ProductSubCategory::firstOrCreate(
                ['name' => 'General', 'product_category_id' => $categoryId],
                ['description' => 'Default subcategory for general products']
            );
            return $defaultSubCategory->id;
        }

        $subCategory = ProductSubCategory::firstOrCreate(
            ['name' => $subCategoryName, 'product_category_id' => $categoryId],
            ['description' => "Auto-created subcategory: {$subCategoryName}"]
        );

        return $subCategory->id;
    }

    private function determineStatus($quantity, $reorderLevel)
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        } elseif ($quantity <= $reorderLevel) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    private function parseBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['yes', 'true', '1', 'active', 'on']);
        }

        return (bool) $value;
    }

    private function buildNotesFromData($data)
    {
        $notes = [];

        if (!empty($data['notes'])) {
            $notes[] = $data['notes'];
        }

        // Add import timestamp
        $notes[] = "Imported on " . now()->format('Y-m-d H:i:s');

        return implode(' | ', $notes);
    }

    public function getImportSummary()
    {
        return [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'total_processed' => $this->imported + $this->updated + $this->skipped,
        ];
    }
}
