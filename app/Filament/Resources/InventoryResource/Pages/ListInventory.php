<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\InventoryResource\Widgets\CategoryBreakdownWidget;
use App\Filament\Resources\InventoryResource\Widgets\InventoryOverview;
use App\Filament\Resources\InventoryResource\Widgets\LowStockAlertWidget;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListInventory extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    #[Url]
    public string $viewType = 'table';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleView')
                ->label(fn () => $this->viewType === 'table' ? 'Grid View' : 'Table View')
                ->icon(fn () => $this->viewType === 'table' ? 'heroicon-o-squares-2x2' : 'heroicon-o-table-cells')
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

            return [
                'records' => $query->with([
                    'product.productCategory',
                    'product.productSubCategory',
                ])->paginate(12),
            ];
        }

        return parent::getViewData();
    }
}
