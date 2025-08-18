<?php

namespace App\Imports;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use App\Models\Product\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductInventoryImport implements ToCollection, WithHeadingRow
{
    use Importable;

    public $imported = 0;

    public $updated = 0;

    public $skipped = 0;

    public $errors = [];

    public $consecutiveEmptyRows = 0;

    public $maxConsecutiveEmptyRows = 10; // Stop after 10 consecutive empty rows

    public function collection(Collection $rows)
    {
        Log::info('Starting inventory import', [
            'total_rows' => $rows->count(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header row and 0-based index

            try {
                // Check if we should stop due to too many consecutive empty rows
                if ($this->consecutiveEmptyRows >= $this->maxConsecutiveEmptyRows) {
                    Log::info("Stopping import due to {$this->maxConsecutiveEmptyRows} consecutive empty rows at row $rowNumber");
                    break;
                }

                $this->processRow($row, $rowNumber);
            } catch (\Exception $e) {
                $errorMessage = "Row $rowNumber: ".$e->getMessage();
                $this->errors[] = $errorMessage;
                $this->skipped++;

                Log::error("Import error on row $rowNumber", [
                    'error' => $e->getMessage(),
                    'row_data' => $row->toArray(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('Inventory import completed', [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors_count' => count($this->errors),
            'consecutive_empty_rows_at_end' => $this->consecutiveEmptyRows,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    private function processRow($row, $rowNumber)
    {
        // Convert collection to array for easier access and normalize keys
        $data = $this->normalizeRowData($row->toArray());

        // Check if this is an empty row
        if ($this->isEmptyRow($data)) {
            $this->consecutiveEmptyRows++;
            Log::debug("Skipping empty row $rowNumber (consecutive: {$this->consecutiveEmptyRows})");

            return;
        }

        // Reset consecutive empty row counter since we found data
        $this->consecutiveEmptyRows = 0;

        Log::debug("Processing row $rowNumber", [
            'raw_data' => $row->toArray(),
            'normalized_data' => $data,
        ]);

        // Validate required fields
        if (empty($data['product_name'])) {
            throw new \Exception("Product name is required (found: '".($data['product_name'] ?? 'null')."')");
        }

        // Find variant by SKU first, then by product name + variation
        $variant = null;

        if (! empty($data['sku'])) {
            $variant = ProductVariant::where('sku', $data['sku'])->first();
            Log::debug("SKU lookup for row $rowNumber", [
                'sku' => $data['sku'],
                'found' => $variant ? 'yes' : 'no',
            ]);
        }

        if (! $variant && ! empty($data['product_name'])) {
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

                Log::debug("Product+Variation lookup for row $rowNumber", [
                    'product_name' => $data['product_name'],
                    'variation_name' => $variationName,
                    'found' => $variant ? 'yes' : 'no',
                ]);
            }
        }

        if (! $variant) {
            // Create new product and variant if they don't exist
            Log::info("Creating new product for row $rowNumber", [
                'product_name' => $data['product_name'],
                'sku' => $data['sku'],
            ]);

            $variant = $this->createNewProductAndVariant($data, $rowNumber);
            if (! $variant) {
                return; // Error already logged
            }
            $this->imported++;
        } else {
            // Update existing variant
            Log::info("Updating existing variant for row $rowNumber", [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'current_stock' => $variant->quantity_in_stock,
            ]);

            $this->updateExistingVariant($variant, $data, $rowNumber);
            $this->updated++;
        }
    }

    private function isEmptyRow($data)
    {
        // Check if all important fields are empty
        $importantFields = ['product_name', 'sku', 'stock_in', 'stock_out_shopee', 'stock_out_tiktok'];

        $hasData = false;
        foreach ($importantFields as $field) {
            if (! empty($data[$field])) {
                $hasData = true;
                break;
            }
        }

        // Also check if any numeric values are present
        if (! $hasData) {
            foreach ($data as $key => $value) {
                if (is_numeric($value) && $value > 0) {
                    $hasData = true;
                    break;
                }
                // Check for any non-empty string values
                if (is_string($value) && trim($value) !== '') {
                    $hasData = true;
                    break;
                }
            }
        }

        return ! $hasData;
    }

    private function normalizeRowData($data)
    {
        // Log the raw data to see what we're actually getting
        Log::debug('Raw Excel row data', [
            'data' => $data,
            'keys' => array_keys($data),
        ]);

        // Normalize column names to handle variations in Excel headers
        $normalized = [];

        foreach ($data as $key => $value) {
            // Convert key to string and normalize
            $stringKey = (string) $key;
            $normalizedKey = strtolower(str_replace([' ', '(', ')', '-', '_'], ['_', '', '', '_', '_'], $stringKey));

            // Clean up the value
            $cleanValue = is_string($value) ? trim($value) : $value;
            if ($cleanValue === '' || $cleanValue === null) {
                $cleanValue = null;
            }

            $normalized[$normalizedKey] = $cleanValue;
        }

        Log::debug('Normalized keys', [
            'normalized' => array_keys($normalized),
        ]);

        // Map to expected field names with multiple possible column name variations
        $mappedData = [];

        // Product information - try multiple variations
        $mappedData['product_name'] = $this->findColumnValue($normalized, [
            'product_name', 'productname', 'product', 'name', 'item_name', 'item',
        ]);

        $mappedData['variation_name'] = $this->findColumnValue($normalized, [
            'variation_name', 'variationname', 'variation', 'variant_name', 'variant',
        ]);

        $mappedData['sku'] = $this->findColumnValue($normalized, [
            'sku', 'product_code', 'code', 'item_code',
        ]);

        $mappedData['category'] = $this->findColumnValue($normalized, [
            'category', 'cat', 'product_category', 'type',
        ]);

        $mappedData['sub_category'] = $this->findColumnValue($normalized, [
            'sub_category', 'subcategory', 'sub_cat', 'subcat',
        ]);

        // Stock calculations - try multiple variations with better parsing
        $stockIn = $this->parseNumericValue($this->findColumnValue($normalized, [
            'stock_in', 'stockin', 'stock_received', 'received', 'incoming', 'in',
        ], 0));

        $stockOutShopee = $this->parseNumericValue($this->findColumnValue($normalized, [
            'stock_out_shopee', 'stockout_shopee', 'out_shopee', 'shopee_out', 'shopee',
        ], 0));

        $stockOutTiktok = $this->parseNumericValue($this->findColumnValue($normalized, [
            'stock_out_tiktok', 'stockout_tiktok', 'out_tiktok', 'tiktok_out', 'tiktok',
        ], 0));

        $mappedData['stock_in'] = $stockIn;
        $mappedData['stock_out_shopee'] = $stockOutShopee;
        $mappedData['stock_out_tiktok'] = $stockOutTiktok;

        // Calculate final stock quantity
        $totalStockOut = $stockOutShopee + $stockOutTiktok;
        $mappedData['final_stock_qty'] = $stockIn - $totalStockOut;

        // Log stock calculations for debugging
        Log::debug('Stock calculations', [
            'stock_in' => $stockIn,
            'stock_out_shopee' => $stockOutShopee,
            'stock_out_tiktok' => $stockOutTiktok,
            'total_stock_out' => $totalStockOut,
            'final_stock_qty' => $mappedData['final_stock_qty'],
        ]);

        // Additional fields that might be in Excel
        $mappedData['size'] = $this->findColumnValue($normalized, ['size']);
        $mappedData['color'] = $this->findColumnValue($normalized, ['color', 'colour']);
        $mappedData['material'] = $this->findColumnValue($normalized, ['material']);
        $mappedData['weight'] = $this->findColumnValue($normalized, ['weight']);
        $mappedData['location'] = $this->findColumnValue($normalized, ['location']);
        $mappedData['reorder_level'] = $this->parseNumericValue($this->findColumnValue($normalized, ['reorder_level', 'reorder', 'min_stock'], 5));
        $mappedData['notes'] = $this->findColumnValue($normalized, ['notes', 'note', 'comments']);
        $mappedData['is_active'] = $this->findColumnValue($normalized, ['is_active', 'active'], true);

        Log::debug('Final mapped data', [
            'mapped_data' => $mappedData,
        ]);

        return $mappedData;
    }

    private function parseNumericValue($value, $default = 0)
    {
        if ($value === null || $value === '') {
            return $default;
        }

        // Handle string numbers with formatting
        if (is_string($value)) {
            // Remove common formatting characters
            $cleanValue = str_replace([',', ' ', '$'], '', $value);
            if (is_numeric($cleanValue)) {
                return (int) $cleanValue;
            }
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private function findColumnValue($data, $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key]) && $data[$key] !== null) {
                return $data[$key];
            }
        }

        return $default;
    }

    private function createNewProductAndVariant($data, $rowNumber)
    {
        // Validate required fields for new product
        if (empty($data['product_name'])) {
            $error = 'Product name is required for new products';
            $this->errors[] = "Row $rowNumber: $error";
            $this->skipped++;
            Log::warning("Validation error for row $rowNumber", ['error' => $error]);

            return null;
        }

        if (empty($data['sku'])) {
            $error = 'SKU is required for new products';
            $this->errors[] = "Row $rowNumber: $error";
            $this->skipped++;
            Log::warning("Validation error for row $rowNumber", ['error' => $error]);

            return null;
        }

        try {
            // Find or create category
            $categoryId = $this->findOrCreateCategory($data['category'] ?? null);
            $subCategoryId = $this->findOrCreateSubCategory($data['sub_category'] ?? null, $categoryId);

            // Create or get existing product
            $product = Product::where('name', $data['product_name'])->first();
            if (! $product) {
                $product = Product::create([
                    'name' => $data['product_name'],
                    'description' => $data['product_description'] ?? '',
                    'product_category_id' => $categoryId,
                    'product_sub_category_id' => $subCategoryId,
                ]);

                Log::info('Created new product', [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'row' => $rowNumber,
                ]);
            }

            // Calculate final stock quantity
            $finalStockQty = max(0, $data['final_stock_qty']); // Ensure non-negative

            // Create variant
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $data['sku'],
                'variation_name' => $data['variation_name'] ?? null,
                'size' => $data['size'] ?? null,
                'color' => $data['color'] ?? null,
                'material' => $data['material'] ?? null,
                'weight' => $data['weight'] ?? null,
                'quantity_in_stock' => $finalStockQty,
                'reorder_level' => (int) ($data['reorder_level'] ?? 5), // Default reorder level
                'location' => $data['location'] ?? '',
                'status' => $this->determineStatus($finalStockQty, (int) ($data['reorder_level'] ?? 5)),
                'notes' => $this->buildNotesFromData($data),
                'is_active' => $this->parseBoolean($data['is_active'] ?? true),
                'last_restocked_at' => $finalStockQty > 0 ? now() : null,
            ]);

            Log::info('Created new variant', [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'stock_qty' => $finalStockQty,
                'row' => $rowNumber,
            ]);

            // Log stock movement
            StockMovement::create([
                'product_variant_id' => $variant->id,
                'type' => 'initial_import',
                'quantity' => $finalStockQty,
                'notes' => 'Initial import stock quantity',
            ]);

            return $variant;

        } catch (\Exception $e) {
            $error = 'Failed to create product/variant: '.$e->getMessage();
            $this->errors[] = "Row $rowNumber: $error";
            $this->skipped++;
            Log::error("Product creation error for row $rowNumber", [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    private function updateExistingVariant($variant, $data, $rowNumber)
    {
        try {
            $updates = [];
            $originalStock = $variant->quantity_in_stock;

            // Always update stock quantity - this is the main purpose of the import
            $finalStockQty = max(0, (int) $data['final_stock_qty']); // Ensure non-negative integer
            $updates['quantity_in_stock'] = $finalStockQty;

            Log::info('Stock update for variant', [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'original_stock' => $originalStock,
                'new_stock' => $finalStockQty,
                'stock_data' => [
                    'stock_in' => $data['stock_in'],
                    'stock_out_shopee' => $data['stock_out_shopee'],
                    'stock_out_tiktok' => $data['stock_out_tiktok'],
                    'final_stock_qty' => $data['final_stock_qty'],
                ],
            ]);

            if ($finalStockQty > 0) {
                $updates['last_restocked_at'] = now();
            }

            // Update variation name if provided
            if (! empty($data['variation_name'])) {
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

            // Update reorder level
            if (isset($data['reorder_level']) && is_numeric($data['reorder_level'])) {
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

            // Update notes - build new notes with stock info
            $updates['notes'] = $this->buildNotesFromData($data);

            // Update status based on new quantity and reorder level
            $newReorderLevel = $updates['reorder_level'] ?? $variant->reorder_level;
            $updates['status'] = $this->determineStatus($finalStockQty, $newReorderLevel);

            // Perform the update
            Log::info('Updating variant with data', [
                'variant_id' => $variant->id,
                'updates' => $updates,
            ]);

            $updateResult = $variant->update($updates);

            if (! $updateResult) {
                Log::error('Failed to update variant', [
                    'variant_id' => $variant->id,
                    'updates' => $updates,
                ]);
                throw new \Exception("Database update failed for variant ID: {$variant->id}");
            }

            // Reload the variant to confirm the update
            $variant->refresh();

            Log::info('Successfully updated variant', [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'stock_change' => "{$originalStock} -> {$variant->quantity_in_stock}",
                'confirmed_stock' => $variant->quantity_in_stock,
                'updates_applied' => array_keys($updates),
                'row' => $rowNumber,
            ]);

            // Log stock movement
            StockMovement::create([
                'product_variant_id' => $variant->id,
                'type' => 'stock_update',
                'quantity' => $finalStockQty - $originalStock,
                'notes' => 'Stock update from import',
            ]);

            // Also update the parent product's category if provided
            if (! empty($data['category']) || ! empty($data['sub_category'])) {
                $productUpdates = [];

                if (! empty($data['category'])) {
                    $categoryId = $this->findOrCreateCategory($data['category']);
                    $productUpdates['product_category_id'] = $categoryId;
                }

                if (! empty($data['sub_category'])) {
                    $subCategoryId = $this->findOrCreateSubCategory(
                        $data['sub_category'],
                        $productUpdates['product_category_id'] ?? $variant->product->product_category_id
                    );
                    $productUpdates['product_sub_category_id'] = $subCategoryId;
                }

                if (! empty($productUpdates)) {
                    $variant->product->update($productUpdates);

                    Log::info('Updated product categories', [
                        'product_id' => $variant->product->id,
                        'updates' => array_keys($productUpdates),
                        'row' => $rowNumber,
                    ]);
                }
            }

        } catch (\Exception $e) {
            $error = 'Failed to update variant: '.$e->getMessage();
            $this->errors[] = "Row $rowNumber: $error";
            $this->skipped++;
            Log::error("Variant update error for row $rowNumber", [
                'variant_id' => $variant->id ?? null,
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
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

        if (! empty($data['notes'])) {
            $notes[] = $data['notes'];
        }

        // Add stock breakdown information
        $stockInfo = sprintf(
            'Stock In: %d, Out (Shopee): %d, Out (TikTok): %d',
            $data['stock_in'] ?? 0,
            $data['stock_out_shopee'] ?? 0,
            $data['stock_out_tiktok'] ?? 0
        );
        $notes[] = $stockInfo;

        // Add import timestamp
        $notes[] = 'Imported on '.now()->format('Y-m-d H:i:s');

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
