<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductVariant extends CreateRecord
{
    protected static string $resource = ProductVariantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-update status based on quantity
        if ($data['quantity_in_stock'] <= 0) {
            $data['status'] = 'out_of_stock';
        } elseif ($data['quantity_in_stock'] <= ($data['reorder_level'] ?? 10)) {
            $data['status'] = 'low_stock';
        } else {
            $data['status'] = 'in_stock';
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
