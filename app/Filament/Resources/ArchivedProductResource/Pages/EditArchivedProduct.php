<?php

namespace App\Filament\Resources\ArchivedProductResource\Pages;

use App\Filament\Resources\ArchivedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchivedProduct extends EditRecord
{
    protected static string $resource = ArchivedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\DeleteAction::make(),
        ];
    }
}
