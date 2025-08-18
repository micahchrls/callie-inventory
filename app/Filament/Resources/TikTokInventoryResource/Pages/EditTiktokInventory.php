<?php

namespace App\Filament\Resources\TiktokInventoryResource\Pages;

use App\Filament\Resources\TiktokInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTiktokInventory extends EditRecord
{
    protected static string $resource = TiktokInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit TikTok Product: '.$this->record->product->name;
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
