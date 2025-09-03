<?php

namespace App\Filament\Resources\StockOutResource\Pages;

use App\Filament\Resources\StockOutResource;
use App\Models\StockOutItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockOut extends CreateRecord
{
    protected static string $resource = StockOutResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Get the form data
        $data = $this->form->getState();

        // Create the corresponding StockOutItem record
        StockOutItem::create([
            'stock_out_id' => $this->record->id,
            'platform' => $data['platform'],
            'quantity' => $data['total_quantity'],
            'note' => $data['notes'] ?? null,
        ]);
    }
}
