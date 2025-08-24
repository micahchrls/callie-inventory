<?php

namespace App\Filament\Resources\ArchivedProductResource\Pages;

use App\Filament\Resources\ArchivedProductResource;
use App\Models\Product\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewArchivedProduct extends ViewRecord
{
    protected static string $resource = ArchivedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('restore')
                ->label('Restore Product')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore Product')
                ->modalDescription(fn (Product $record): string =>
                    "Are you sure you want to restore '{$record->name}'? This will make it available again in the active products list along with all its variants."
                )
                ->modalSubmitActionLabel('Yes, Restore')
                ->action(function (Product $record) {
                    $record->restore();

                    Notification::make()
                        ->title('Product Restored')
                        ->body("'{$record->name}' has been successfully restored.")
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.archived-products.index');
                })
                ->visible(fn (Product $record): bool => $record->trashed()),

            Actions\Action::make('back')
                ->label('Back to Archives')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => ArchivedProductResource::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return "Archived Product: {$this->getRecord()->name}";
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets for archived product statistics if needed
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Add widgets showing variant information, stock history, etc.
        ];
    }
}
