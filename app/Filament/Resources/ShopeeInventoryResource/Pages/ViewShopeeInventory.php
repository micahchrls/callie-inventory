<?php

namespace App\Filament\Resources\ShopeeInventoryResource\Pages;

use App\Filament\Resources\ShopeeInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewShopeeInventory extends ViewRecord
{
    protected static string $resource = ShopeeInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Shopee Product: '.$this->record->product->name;
    }
}
