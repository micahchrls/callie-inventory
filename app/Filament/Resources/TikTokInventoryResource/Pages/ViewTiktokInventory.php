<?php

namespace App\Filament\Resources\TiktokInventoryResource\Pages;

use App\Filament\Resources\TiktokInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTiktokInventory extends ViewRecord
{
    protected static string $resource = TiktokInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'TikTok Product: '.$this->record->product->name;
    }
}
