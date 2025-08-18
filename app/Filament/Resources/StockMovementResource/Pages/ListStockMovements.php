<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - movements are auto-generated
        ];
    }

    public function getTitle(): string
    {
        return 'Stock Movement Audit Trail';
    }

    public function getSubheading(): ?string
    {
        return 'Complete history of all inventory changes with user accountability';
    }
}
