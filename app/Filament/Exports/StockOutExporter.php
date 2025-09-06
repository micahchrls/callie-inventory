<?php

namespace App\Filament\Exports;

use App\Models\StockOut;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StockOutExporter extends Exporter
{
    protected static ?string $model = StockOut::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('created_at')
                ->label('Date/Time'),
            ExportColumn::make('product.name')
                ->label('Product Name'),
            ExportColumn::make('productVariant.sku')
                ->label('Variant SKU'),
            ExportColumn::make('productVariant.variation_name')
                ->label('Variant Name'),
            ExportColumn::make('total_quantity')
                ->label('Quantity Removed'),
            ExportColumn::make('reason')
                ->label('Reason'),
            ExportColumn::make('user.name')
                ->label('User'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your stock out export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
