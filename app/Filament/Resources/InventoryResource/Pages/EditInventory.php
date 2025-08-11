<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInventory extends EditRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_restock')
                ->label('🔄 Quick Restock')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('quantity')
                        ->label('Add Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->step(1),
                    \Filament\Forms\Components\Checkbox::make('update_restock_date')
                        ->label('Update restocked date')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $this->record->adjustStock($data['quantity'], 'add');

                    if ($data['update_restock_date']) {
                        $this->record->update(['last_restocked_at' => now()]);
                    }

                    Notification::make()
                        ->title('✅ Stock Updated')
                        ->body("Added {$data['quantity']} units to {$this->record->product->name}")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            Actions\ViewAction::make()
                ->label('View Details'),

            Actions\DeleteAction::make()
                ->label('Delete Item'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Inventory updated successfully!';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Auto-update status based on quantity changes
        if (isset($data['quantity_in_stock']) && isset($data['reorder_level'])) {
            if ($data['quantity_in_stock'] <= 0) {
                $data['status'] = 'out_of_stock';
            } elseif ($data['quantity_in_stock'] <= $data['reorder_level']) {
                $data['status'] = 'low_stock';
            } else {
                $data['status'] = 'in_stock';
            }
        }

        return $data;
    }
}
