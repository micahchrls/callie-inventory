<?php

namespace App\Filament\Resources\TiktokInventoryResource\Pages;

use App\Filament\Resources\TiktokInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListTiktokInventories extends ListRecords
{
    protected static string $resource = TiktokInventoryResource::class;
    
    #[Url]
    public string $viewType = 'table';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('toggleView')
                ->label(fn() => $this->viewType === 'table' ? 'Grid View' : 'Table View')
                ->icon(fn() => $this->viewType === 'table' ? 'heroicon-o-squares-2x2' : 'heroicon-o-table-cells')
                ->color('gray')
                ->action(function () {
                    $this->viewType = $this->viewType === 'table' ? 'grid' : 'table';
                }),
        ];
    }
    
    public function getView(): string
    {
        if ($this->viewType === 'grid') {
            return 'filament.resources.tiktok-inventory.pages.list-tiktok-inventories-grid';
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
                    'platform'
                ])->paginate(12),
            ];
        }
        
        return parent::getViewData();
    }
    
    public function getTitle(): string
    {
        return 'TikTok Inventory Management';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add any widgets if needed in the future
        ];
    }
}
