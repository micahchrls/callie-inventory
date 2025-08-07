<?php

namespace App\Filament\Pages;

use App\Models\StockMovement;
use App\Models\Product\ProductVariant;
use App\Models\Product\Product;
use App\Models\Platform;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockoutDetailsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    protected static string $view = 'filament.pages.stockout-details';
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $slug = 'stockout-details';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Stock Out Details';
    protected static ?int $navigationSort = 3;

    public string $date;
    public ?string $platform = null;
    public array $summaryStats = [];

    public function mount(): void
    {
        $this->date = request('date', now()->format('Y-m-d'));
        $this->platform = request('platform');
        $this->loadSummaryStats();
    }
    
    protected function loadSummaryStats(): void
    {
        $query = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->join('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->where('stock_movements.movement_type', 'stock_out')
            ->whereDate('stock_movements.created_at', $this->date);
            
        if ($this->platform) {
            $query->where('platforms.name', $this->platform);
        }
        
        $stats = $query->select([
            DB::raw('COUNT(DISTINCT stock_movements.product_variant_id) as unique_products'),
            DB::raw('SUM(ABS(stock_movements.quantity_change)) as total_quantity'),
            DB::raw('COUNT(*) as total_movements'),
            DB::raw('COALESCE(SUM(stock_movements.total_cost), 0) as total_value')
        ])->first();
        
        $topReason = StockMovement::query()
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->join('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->where('stock_movements.movement_type', 'stock_out')
            ->whereDate('stock_movements.created_at', $this->date)
            ->when($this->platform, fn($q) => $q->where('platforms.name', $this->platform))
            ->select('stock_movements.reason', DB::raw('COUNT(*) as count'))
            ->whereNotNull('stock_movements.reason')
            ->groupBy('stock_movements.reason')
            ->orderByDesc('count')
            ->first();
        
        $this->summaryStats = [
            'unique_products' => $stats->unique_products ?? 0,
            'total_quantity' => $stats->total_quantity ?? 0,
            'total_movements' => $stats->total_movements ?? 0,
            'total_value' => $stats->total_value ?? 0,
            'top_reason' => $topReason->reason ?? 'N/A',
        ];
    }

    public function getTitle(): string
    {
        $formattedDate = Carbon::parse($this->date)->format('F j, Y');
        
        if ($this->platform) {
            return "Stock Out Details - {$this->platform} - {$formattedDate}";
        }
        
        return "Stock Out Details - {$formattedDate}";
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->size('sm')
                    ->weight('medium')
                    ->copyable()
                    ->copyMessage('SKU copied')
                    ->copyMessageDuration(1500),
                    
                Tables\Columns\TextColumn::make('productVariant.product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('productVariant.platform.name')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Shopee' => 'warning',
                        'TikTok' => 'danger', 
                        'Lazada' => 'info',
                        default => 'primary',
                    }),
                    
                Tables\Columns\TextColumn::make('productVariant.variation_name')
                    ->label('Variant')
                    ->searchable()
                    ->default('Standard'),
                    
                Tables\Columns\TextColumn::make('category_subcategory')
                    ->label('Category')
                    ->getStateUsing(function (StockMovement $record): string {
                        $category = $record->productVariant->product->productCategory?->name ?? 'No Category';
                        $subcategory = $record->productVariant->product->productSubCategory?->name;
                        return $subcategory ? "{$category} / {$subcategory}" : $category;
                    })
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Quantity Out')
                    ->numeric()
                    ->alignCenter()
                    ->weight('bold')
                    ->color('danger')
                    ->formatStateUsing(fn (int $state): string => abs($state) . ' units')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Out')
                            ->using(fn ($query) => $query->sum(\DB::raw('ABS(quantity_change)')))
                            ->numeric(decimalPlaces: 0)
                            ->suffix(' units'),
                    ]),
                    
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sold' => 'success',
                        'damaged' => 'danger',
                        'lost' => 'warning',
                        'expired' => 'gray',
                        'returned' => 'info',
                        'transfer' => 'primary',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'unknown')),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->placeholder('No notes')
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('reason')
                    ->label('Reason')
                    ->options([
                        'sold' => 'Sold',
                        'damaged' => 'Damaged',
                        'lost' => 'Lost',
                        'expired' => 'Expired',
                        'returned' => 'Returned',
                        'transfer' => 'Transfer',
                        'other' => 'Other',
                    ])
                    ->placeholder('All Reasons')
                    ->indicator('Reason'),
                    
                SelectFilter::make('productVariant.platform.name')
                    ->label('Platform')
                    ->options(
                        Platform::query()
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray()
                    )
                    ->placeholder('All Platforms')
                    ->visible(fn (): bool => !$this->platform)
                    ->indicator('Platform')
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['value'])) {
                            return $query->whereHas('productVariant.platform', function ($q) use ($data) {
                                $q->where('name', $data['value']);
                            });
                        }
                        return $query;
                    }),
                    
                Filter::make('high_quantity')
                    ->label('High Quantity (>10)')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_change', '<', -10))
                    ->toggle()
                    ->indicator('High Quantity'),
                    
                Filter::make('product_search')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('search')
                            ->placeholder('Search products...')
                            ->prefixIcon('heroicon-m-magnifying-glass'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['search'],
                            fn (Builder $query, $search): Builder => $query->whereHas(
                                'productVariant.product',
                                fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                            )
                        );
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                Action::make('view_variant')
                    ->label('View Product')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->url(fn (StockMovement $record): string => 
                        route('filament.admin.resources.product-variants.view', [
                            'record' => $record->productVariant->id
                        ])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these stock movement records? This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->striped()
            ->poll('60s')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->emptyStateHeading('No stock movements found')
            ->emptyStateDescription('There are no stock out movements for this date' . ($this->platform ? " and platform ({$this->platform})" : '') . '. Try adjusting your filters or selecting a different date.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->emptyStateActions([
                Action::make('back_to_calendar')
                    ->label('Back to Calendar')
                    ->icon('heroicon-m-calendar-days')
                    ->url(url()->previous())
                    ->color('primary'),
            ]);
    }

    protected function getTableQuery()
    {
        $query = StockMovement::query()
            ->with([
                'productVariant.product.productCategory',
                'productVariant.product.productSubCategory', 
                'productVariant.platform',
                'user'
            ])
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->join('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->where('stock_movements.movement_type', 'stock_out')
            ->whereDate('stock_movements.created_at', $this->date);

        if ($this->platform) {
            $query->where('platforms.name', $this->platform);
        }
        
        // Select stock_movements.* to avoid ambiguous column issues
        $query->select('stock_movements.*');

        return $query;
    }
}
