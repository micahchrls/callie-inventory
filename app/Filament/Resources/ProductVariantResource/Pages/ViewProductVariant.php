<?php

namespace App\Filament\Resources\ProductVariantResource\Pages;

use App\Filament\Resources\ProductVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

class ViewProductVariant extends ViewRecord
{
    protected static string $resource = ProductVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),

            Actions\Action::make('adjust_stock')
                ->label('Adjust Stock')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->form([
                    Forms\Components\Select::make('action')
                        ->label('Action')
                        ->options([
                            'add' => 'Add to Stock',
                            'subtract' => 'Subtract from Stock',
                            'set' => 'Set Stock Level',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(0),
                ])
                ->action(function (array $data): void {
                    $this->record->adjustStock($data['quantity'], $data['action']);
                    $this->refreshFormData(['quantity_in_stock', 'status']);
                }),
        ];
    }
}
