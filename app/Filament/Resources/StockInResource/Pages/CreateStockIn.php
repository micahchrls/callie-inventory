<?php

namespace App\Filament\Resources\StockInResource\Pages;

use App\Filament\Resources\StockInResource;
use App\Models\Product\ProductVariant;
use App\Models\StockInItem;
use Filament\Resources\Pages\CreateRecord;

class CreateStockIn extends CreateRecord
{
    protected static string $resource = StockInResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Get the form data
        $data = $this->form->getState();

        // Find the ProductVariant and update stock + status
        $productVariant = ProductVariant::find($data['product_variant_id']);

        // Increment stock
        $productVariant->increment('quantity_in_stock', $data['total_quantity']);

        // Update status based on new stock levels
        $productVariant->updateStockStatus();

        // Create the corresponding StockInItem record
        StockInItem::create([
            'stock_in_id' => $this->record->id,
            'quantity' => $data['total_quantity'],
            'note' => $data['notes'] ?? null,
        ]);
    }
}
