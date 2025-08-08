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
use Livewire\Attributes\Url;
use Illuminate\Contracts\View\View;

class ListInventory extends ListRecords
{
    protected static string $resource = InventoryResource::class;
    
    #[Url]
    public string $viewType = 'table';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleView')
                ->label(fn() => $this->viewType === 'table' ? 'Grid View' : 'Table View')
                ->icon(fn() => $this->viewType === 'table' ? 'heroicon-o-squares-2x2' : 'heroicon-o-table-cells')
                ->color('gray')
                ->action(function () {
                    $this->viewType = $this->viewType === 'table' ? 'grid' : 'table';
                }),
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
    
    public function getView(): string
    {
        if ($this->viewType === 'grid') {
            return 'filament.resources.inventory.pages.list-inventory-grid';
        }
        
        return parent::getView();
    }
    
    protected function getViewData(): array
    {
        if ($this->viewType === 'grid') {
            $query = $this->getFilteredTableQuery();
            
            // Apply tab filter manually based on activeTab
            $activeTab = $this->activeTab;
            if ($activeTab === 'tiktok') {
                $query = $query->whereHas('platform', fn ($q) => $q->where('name', 'TikTok'));
            } elseif ($activeTab === 'shopee') {
                $query = $query->whereHas('platform', fn ($q) => $q->where('name', 'Shopee'));
            }
            
            return [
                'records' => $query->with([
                    'product.productCategory',
                    'product.productSubCategory',
                    'platform'
                ])->paginate(12),
            ];
        }
        
        return parent::getViewData();
    }
}
