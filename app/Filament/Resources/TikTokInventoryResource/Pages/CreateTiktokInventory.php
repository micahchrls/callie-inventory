<?php

namespace App\Filament\Resources\TiktokInventoryResource\Pages;

use App\Filament\Resources\TiktokInventoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTiktokInventory extends CreateRecord
{
    protected static string $resource = TiktokInventoryResource::class;

    public function getTitle(): string
    {
        return 'Add New TikTok Product Variant';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure the platform is set to TikTok
        $tiktokPlatform = \App\Models\Platform::where('name', 'TikTok')->first();
        $data['platform_id'] = $tiktokPlatform?->id;

        return $data;
    }
}
