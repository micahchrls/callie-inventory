<?php

namespace App\Filament\Resources\ProductSubCategoryResource\Pages;

use App\Filament\Resources\ProductSubCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductSubCategory extends ViewRecord
{
    protected static string $resource = ProductSubCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
