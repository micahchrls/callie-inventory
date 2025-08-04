<?php

namespace App\Filament\Resources\ShopeeInventoryResource\Pages;

use App\Filament\Resources\ShopeeInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShopeeInventories extends ListRecords
{
    protected static string $resource = ShopeeInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Product Variant')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Shopee Inventory Management';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add any widgets if needed in the future
        ];
    }
}
