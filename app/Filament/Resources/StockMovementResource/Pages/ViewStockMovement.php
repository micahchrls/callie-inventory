<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit or delete actions - audit trail is immutable
        ];
    }

    public function getTitle(): string
    {
        return 'Stock Movement Details';
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();
        return "Movement #{$record->id} - {$record->movement_type_display} on " . $record->created_at->format('M d, Y H:i');
    }
}
