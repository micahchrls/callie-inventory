<?php

namespace App\Filament\Resources\ShopeeInventoryResource\Pages;

use App\Filament\Resources\ShopeeInventoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShopeeInventory extends CreateRecord
{
    protected static string $resource = ShopeeInventoryResource::class;

    public function getTitle(): string
    {
        return 'Add New Shopee Product Variant';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure the platform is set to Shopee
        $shopeePlatform = \App\Models\Platform::where('name', 'Shopee')->first();
        $data['platform_id'] = $shopeePlatform?->id;

        return $data;
    }
}
