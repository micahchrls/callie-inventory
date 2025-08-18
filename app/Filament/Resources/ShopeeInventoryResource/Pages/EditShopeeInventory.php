<?php

namespace App\Filament\Resources\ShopeeInventoryResource\Pages;

use App\Filament\Resources\ShopeeInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShopeeInventory extends EditRecord
{
    protected static string $resource = ShopeeInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Shopee Product: '.$this->record->product->name;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relationships and add their data to the form
        $this->record->load(['product', 'platform']);

        // Add relationship data to form data using the correct field names
        $data['product_name'] = $this->record->product?->name;
        $data['product_description'] = $this->record->product?->description;
        $data['platform_name'] = $this->record->platform?->name;

        return $data;
    }
}
