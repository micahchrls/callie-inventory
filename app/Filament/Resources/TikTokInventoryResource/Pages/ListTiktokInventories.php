<?php

namespace App\Filament\Resources\TiktokInventoryResource\Pages;

use App\Filament\Resources\TiktokInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTiktokInventories extends ListRecords
{
    protected static string $resource = TiktokInventoryResource::class;

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
        return 'TikTok Inventory Management';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add any widgets if needed in the future
        ];
    }
}
