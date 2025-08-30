<?php

namespace App\Filament\Imports;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Product Name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('category')
                ->label('Product Category')
                ->relationship('productCategory', ['id', 'name'])
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('sub_category')
                ->label('Product Sub-Category')
                ->relationship('productSubCategory', ['id', 'name'])
                ->requiredMapping()
                ->rules(['required', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Product
    {
        // Create category if it doesn't exist
        $productCategory = ProductCategory::firstOrCreate(
            ['name' => $this->data['category']],
            [
                'name' => $this->data['category'],
                'description' => 'Auto-created during import',
            ]
        );

        // Create sub-category if it doesn't exist
        $productSubCategory = ProductSubCategory::firstOrCreate(
            ['name' => $this->data['sub_category']],
            [
                'name' => $this->data['sub_category'],
                'description' => 'Auto-created during import',
                'product_category_id' => $productCategory->id,
            ]
        );

        $product = Product::firstOrNew([
            'product_category_id' => $productCategory->id,
            'product_sub_category_id' => $productSubCategory->id,
            'name' => $this->data['name'],
            'base_sku' => $this->baseSkuGenerate(),
            'status' => 'active'
        ]);

        if (!$product) {
            throw new RowImportFailedException("Row import failed");
        }

        // Save the product first to get its ID
        $product->save();

        return $product;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    private function baseSkuGenerate(): string
    {
        $productName = $this->data['name'] ?? '';

        // Split the product name into words and take the first letter of each word
        $words = explode(' ', trim($productName));
        $baseSku = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $baseSku .= strtoupper(substr($word, 0, 1));
            }
        }

        return $baseSku;
    }
}
