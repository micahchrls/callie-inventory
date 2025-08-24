<?php

namespace App\Filament\Resources\ArchivedProductResource\Pages;

use App\Filament\Resources\ArchivedProductResource;
use App\Models\Product\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListArchivedProducts extends ListRecords
{
    protected static string $resource = ArchivedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('restore_all')
                ->label('Restore All')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Restore All Archived Products')
                ->modalDescription('Are you sure you want to restore ALL archived products? This action will make all archived products available again in the active products list.')
                ->modalSubmitActionLabel('Yes, Restore All')
                ->action(function () {
                    $archivedProducts = Product::onlyTrashed()->get();
                    $count = $archivedProducts->count();

                    if ($count === 0) {
                        Notification::make()
                            ->title('No Products to Restore')
                            ->body('There are no archived products to restore.')
                            ->warning()
                            ->send();
                        return;
                    }

                    foreach ($archivedProducts as $product) {
                        $product->restore();
                    }

                    Notification::make()
                        ->title('All Products Restored')
                        ->body("{$count} products have been successfully restored.")
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => Product::onlyTrashed()->exists()),
        ];
    }

    public function getTitle(): string
    {
        $count = Product::onlyTrashed()->count();
        return "Archived Products ({$count})";
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add any widgets here if needed
        ];
    }
}
