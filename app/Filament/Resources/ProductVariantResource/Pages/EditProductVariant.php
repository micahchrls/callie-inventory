<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductVariant extends EditRecord
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
