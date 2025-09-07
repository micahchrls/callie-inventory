<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInventory extends EditRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('stock_alert_settings')
                ->label('Alert Settings')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->form([
                    Forms\Components\Section::make('Stock Alert Configuration')
                        ->schema([
                            Forms\Components\TextInput::make('reorder_level')
                                ->label('Reorder Level')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(1)
                                ->default(fn () => $this->record->reorder_level)
                                ->helperText('System will alert when stock drops to or below this level'),

                            Forms\Components\Toggle::make('low_stock_alerts')
                                ->label('Enable Low Stock Alerts')
                                ->default(true)
                                ->helperText('Receive notifications when stock is low'),

                            Forms\Components\Toggle::make('out_of_stock_alerts')
                                ->label('Enable Out of Stock Alerts')
                                ->default(true)
                                ->helperText('Receive notifications when stock is depleted'),
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'reorder_level' => $data['reorder_level'],
                    ]);

                    Notification::make()
                        ->title('Alert Settings Updated')
                        ->body("Reorder level set to {$data['reorder_level']} units")
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
        // Auto-update status based on quantity changes, but preserve "discontinued" if manually set
        if (isset($data['quantity_in_stock']) && isset($data['reorder_level'])) {
            // Only auto-calculate status if it's NOT manually set to "discontinued"
            if (!isset($data['status']) || $data['status'] !== 'discontinued') {
                if ($data['quantity_in_stock'] <= 0) {
                    $data['status'] = 'out_of_stock';
                } elseif ($data['quantity_in_stock'] <= $data['reorder_level']) {
                    $data['status'] = 'low_stock';
                } else {
                    $data['status'] = 'in_stock';
                }
            }
            // If status is 'discontinued', preserve it and don't override
        }

        return $data;
    }
}
