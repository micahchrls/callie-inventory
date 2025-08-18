<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListProductVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            ExportAction::make()
                ->label('Export Variants')
                ->color('success')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(fn ($resource) => 'product-variants-'.date('Y-m-d'))
                        ->withColumns([
                            Column::make('product.name')->heading('Product Name'),
                            Column::make('sku')->heading('SKU'),
                            Column::make('variation_name')->heading('Variation Name'),
                            Column::make('size')->heading('Size'),
                            Column::make('color')->heading('Color'),
                            Column::make('material')->heading('Material'),
                            Column::make('weight')->heading('Weight'),
                            Column::make('price')->heading('Price'),
                            Column::make('cost_price')->heading('Cost Price'),
                            Column::make('quantity_in_stock')->heading('Stock Quantity'),
                            Column::make('reorder_level')->heading('Reorder Level'),
                            Column::make('location')->heading('Location'),
                            Column::make('status')->heading('Status'),
                            Column::make('is_active')->heading('Active'),
                            Column::make('notes')->heading('Notes'),
                            Column::make('last_restocked_at')->heading('Last Restocked'),
                        ]),
                ]),
        ];
    }
}
