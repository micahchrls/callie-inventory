<?php

namespace App\Filament\Exports;

use App\Models\Product\ProductVariant;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class InventoryExporter extends Exporter
{
    protected static ?string $model = ProductVariant::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('sku')
                ->label('SKU'),

            ExportColumn::make('product.name')
                ->label('Product Name'),

            ExportColumn::make('variation_name')
                ->label('Variation Name')
                ->formatStateUsing(function ($record) {
                    // Get the main variant's variation name or build from attributes
                    if ($record->variation_name) {
                        return $record->variation_name;
                    }

                    // Build variation name from attributes if no explicit name
                    $attributes = array_filter([
                        $record->size,
                        $record->color,
                        $record->material,
                    ]);

                    return !empty($attributes) ? implode(' | ', $attributes) : 'Standard';
                }),

            ExportColumn::make('product.productCategory.name')
                ->label('Category'),

            ExportColumn::make('product.productSubCategory.name')
                ->label('Sub Category')
                ->formatStateUsing(function ($record) {
                    return $record->product->productSubCategory ? $record->product->productSubCategory->name : '-';
                }),

            ExportColumn::make('size')
                ->label('Size'),

            ExportColumn::make('color')
                ->label('Color'),

            ExportColumn::make('material')
                ->label('Material'),

            ExportColumn::make('variant_initial')
                ->label('Variant Initial'),

            ExportColumn::make('quantity_in_stock')
                ->label('Current Stock'),

            ExportColumn::make('reorder_level')
                ->label('Reorder Level'),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(function ($record) {
                    // Calculate status based on quantity and reorder level
                    if ($record->status === 'discontinued') {
                        return 'Discontinued';
                    }

                    if ($record->quantity_in_stock <= 0) {
                        return 'Out of Stock';
                    } elseif ($record->quantity_in_stock <= $record->reorder_level) {
                        return 'Low Stock';
                    } else {
                        return 'In Stock';
                    }
                }),

            ExportColumn::make('is_active')
                ->label('Active')
                ->formatStateUsing(function ($state) {
                    return $state ? 'Yes' : 'No';
                }),

            ExportColumn::make('last_restocked_at')
                ->label('Last Restocked')
                ->formatStateUsing(function ($state) {
                    return $state ? $state->format('M d, Y') : 'Never';
                }),

            ExportColumn::make('created_at')
                ->label('Created')
                ->formatStateUsing(function ($state) {
                    return $state ? $state->format('M d, Y') : '-';
                }),

            ExportColumn::make('updated_at')
                ->label('Updated')
                ->formatStateUsing(function ($state) {
                    return $state ? $state->format('M d, Y H:i') : '-';
                }),

            ExportColumn::make('notes')
                ->label('Notes')
                ->formatStateUsing(function ($state) {
                    return $state ?: '-';
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your inventory export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
