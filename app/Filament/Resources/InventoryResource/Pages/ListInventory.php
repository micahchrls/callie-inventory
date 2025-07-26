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

            'low_stock' => Tab::make('⚠️ Low Stock')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0'))
                ->badge($this->getModel()::whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0')->count())
                ->badgeColor('warning'),

            'out_of_stock' => Tab::make('🚨 Out of Stock')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('quantity_in_stock', '<=', 0))
                ->badge($this->getModel()::where('quantity_in_stock', '<=', 0)->count())
                ->badgeColor('danger'),

            'in_stock' => Tab::make('✅ In Stock')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('quantity_in_stock > reorder_level'))
                ->badge($this->getModel()::whereRaw('quantity_in_stock > reorder_level')->count())
                ->badgeColor('success'),

            'discontinued' => Tab::make('📦 Discontinued')
                ->icon('heroicon-o-archive-box-x-mark')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'discontinued'))
                ->badge($this->getModel()::where('status', 'discontinued')->count())
                ->badgeColor('gray'),
        ];
    }
}
