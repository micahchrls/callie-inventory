<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Widgets\InventoryOverview;
use App\Filament\Resources\InventoryResource\Widgets\CategoryBreakdownWidget;
use App\Filament\Resources\InventoryResource\Widgets\LowStockAlertWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInventory extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Removed the circular reference action that was causing the RouteNotFoundException
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InventoryOverview::class,
            // CategoryBreakdownWidget::class,
            // LowStockAlertWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Items')
                ->icon('heroicon-o-squares-2x2')
                ->badge($this->getModel()::count()),

            'tiktok' => Tab::make('TikTok')
                ->icon('heroicon-o-play')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('platform', fn ($q) => $q->where('name', 'TikTok')))
                ->badge($this->getModel()::whereHas('platform', fn ($q) => $q->where('name', 'TikTok'))->count())
                ->badgeColor('purple'),

            'shopee' => Tab::make('Shopee')
                ->icon('heroicon-o-shopping-bag')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('platform', fn ($q) => $q->where('name', 'Shopee')))
                ->badge($this->getModel()::whereHas('platform', fn ($q) => $q->where('name', 'Shopee'))->count())
                ->badgeColor('orange'),
        ];
    }
}
