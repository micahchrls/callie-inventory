<?php

namespace App\Filament\Pages;

use App\Models\StockMovement;
use App\Models\Product\ProductVariant;
use App\Models\Product\Product;
use App\Models\Platform;
use App\Exports\StockTransactionsExport;
use App\Jobs\ExportStockTransactionsToExcel;
use App\Jobs\ExportStockTransactionsToPdf;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Livewire\Attributes\On;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class StockTransactionsPage extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithHeaderActions;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';
    protected static string $view = 'filament.pages.stock-transactions';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'stock-transactions';
    protected static ?string $navigationGroup = 'Inventory Management';
    protected static ?string $navigationLabel = 'Stock Transactions';
    protected static ?int $navigationSort = 3;

    public string $date;
    public ?string $platform = null;
    public ?string $transactionType = null; // 'stock_in', 'stock_out', or null for all

    public function mount(): void
    {
        // Get parameters from request, with sensible defaults
        $this->date = request('date') ?: now()->format('Y-m-d');
        $this->platform = request('platform') ?: null;
        
        // Handle movement_type parameter from calendar widget
        $movementType = request('movement_type');
        if ($movementType) {
            $this->transactionType = match($movementType) {
                'in' => 'stock_in',
                'out' => 'stock_out',
                default => null
            };
        } else {
            // Fallback to old 'type' parameter for backward compatibility
            $this->transactionType = request('type') ?: null;
        }
        
        // Validate the date format to prevent errors
        try {
            Carbon::parse($this->date);
        } catch (\Exception $e) {
            $this->date = now()->format('Y-m-d');
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StockTransactionsSummaryWidget::make([
                'date' => $this->date,
                'platform' => $this->platform,
                'transactionType' => $this->transactionType,
            ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 7; // Display 7 columns for all the stats
    }

    #[On('filament.transactions.filter-updated')]
    public function updateFilters($date = null, $platform = null, $type = null): void
    {
        if ($date) {
            $this->date = $date;
        }
        if ($platform !== null) {
            $this->platform = $platform;
        }
        if ($type !== null) {
            $this->transactionType = $type;
        }

        // Refresh the table
        $this->resetTable();
    }

    public function getTitle(): string
    {
        try {
            $formattedDate = Carbon::parse($this->date)->format('F j, Y');
        } catch (\Exception $e) {
            $formattedDate = now()->format('F j, Y');
        }
        
        $typeLabel = match($this->transactionType) {
            'stock_in' => 'Stock In',
            'stock_out' => 'Stock Out',
            default => 'All Transactions'
        };
        
        if ($this->platform) {
            return "Stock Transactions - {$typeLabel} - {$this->platform} - {$formattedDate}";
        }
        
        return "Stock Transactions - {$typeLabel} - {$formattedDate}";
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }
    
    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.pages.dashboard') => 'Dashboard',
            'Stock Transactions',
        ];
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
                    ->label('Quantity')
                    ->numeric()
                    ->alignCenter()
                    ->weight('bold')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(function (int $state): string {
                        $prefix = $state > 0 ? '+' : '';
                        return $prefix . $state . ' units';
                    })
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Change')
                            ->numeric(decimalPlaces: 0)
                            ->suffix(' units'),
                    ]),

                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'restock', 'return', 'initial_stock' => 'success',
                        'stock_out', 'sale' => 'danger',
                        'adjustment' => 'warning',
                        'damage', 'loss' => 'danger',
                        'transfer' => 'info',
                        'manual_edit' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sold' => 'success',
                        'restock' => 'success',
                        'damaged' => 'danger',
                        'lost' => 'warning',
                        'expired' => 'gray',
                        'returned' => 'info',
                        'transfer' => 'primary',
                        'adjustment' => 'warning',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'N/A')),

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
                Filter::make('date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Date')
                            ->default($this->date)
                            ->maxDate(now())
                            ->displayFormat('M d, Y')
                            ->closeOnDateSelection(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['date'])) {
                            // Update the page's date property
                            $this->date = $data['date'];
                            // Apply the date filter
                            return $query->whereDate('stock_movements.created_at', $data['date']);
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['date'])) {
                            return 'Date: ' . Carbon::parse($data['date'])->format('M d, Y');
                        }
                        return null;
                    }),
                    
                SelectFilter::make('movement_type')
                    ->label('Transaction Type')
                    ->options([
                        'restock' => 'Restock',
                        'stock_out' => 'Stock Out',
                        'sale' => 'Sale',
                        'return' => 'Return',
                        'adjustment' => 'Adjustment',
                        'damage' => 'Damage',
                        'loss' => 'Loss',
                        'transfer' => 'Transfer',
                        'initial_stock' => 'Initial Stock',
                        'manual_edit' => 'Manual Edit',
                    ])
                    ->placeholder('All Types')
                    ->indicator('Type'),

                Filter::make('stock_direction')
                    ->label('Stock Direction')
                    ->form([
                        \Filament\Forms\Components\Radio::make('direction')
                            ->options([
                                'in' => 'Stock In',
                                'out' => 'Stock Out',
                                'all' => 'All',
                            ])
                            ->default('all'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['direction'] ?? 'all',
                            fn (Builder $query, $direction): Builder => match($direction) {
                                'in' => $query->where('quantity_change', '>', 0),
                                'out' => $query->where('quantity_change', '<', 0),
                                default => $query,
                            }
                        );
                    }),

                SelectFilter::make('reason')
                    ->label('Reason')
                    ->options([
                        'sold' => 'Sold',
                        'restock' => 'Restock',
                        'damaged' => 'Damaged',
                        'lost' => 'Lost',
                        'expired' => 'Expired',
                        'returned' => 'Returned',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Adjustment',
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

                Filter::make('product_search')
                    ->form([
                        TextInput::make('search')
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
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function () {
                        return $this->exportToExcel();
                    }),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function () {
                        return $this->exportToPdf();
                    }),
            ])
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
            ->emptyStateHeading('No stock transactions found')
            ->emptyStateDescription('There are no stock transactions for this date' . ($this->platform ? " and platform ({$this->platform})" : '') . ($this->transactionType ? " and type ({$this->transactionType})" : '') . '. Try adjusting your filters or selecting a different date.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->emptyStateActions([
                Action::make('back_to_dashboard')
                    ->label('Back to Dashboard')
                    ->icon('heroicon-m-home')
                    ->url(route('filament.admin.pages.dashboard'))
                    ->color('primary'),
            ]);
    }
    protected function getTableQuery()
    {
        // Ensure date is valid
        try {
            $date = Carbon::parse($this->date)->format('Y-m-d');
        } catch (\Exception $e) {
            $date = now()->format('Y-m-d');
        }
        
        $query = StockMovement::query()
            ->with([
                'productVariant.product.productCategory',
                'productVariant.product.productSubCategory', 
                'productVariant.platform',
                'user'
            ])
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->whereDate('stock_movements.created_at', $date);

        // Filter by transaction type
        if ($this->transactionType === 'stock_in') {
            $query->where(function ($q) {
                $q->whereIn('stock_movements.movement_type', ['restock', 'return', 'initial_stock'])
                  ->orWhere('stock_movements.quantity_change', '>', 0);
            });
        } elseif ($this->transactionType === 'stock_out') {
            $query->where(function ($q) {
                $q->whereIn('stock_movements.movement_type', ['stock_out', 'sale'])
                  ->orWhere('stock_movements.quantity_change', '<', 0);
            });
        }

        if ($this->platform) {
            $query->where('platforms.name', $this->platform);
        }

        // Select stock_movements.* to avoid ambiguous column issues
        $query->select('stock_movements.*');

        return $query;
    }

    protected function exportToExcel()
    {
        try {
            $stockMovements = $this->getExportData();

            if ($stockMovements->isEmpty()) {
                Notification::make()
                    ->title('No data to export')
                    ->body('There are no stock transaction records for the selected criteria.')
                    ->warning()
                    ->send();
                return;
            }

            $fileName = $this->generateFileName('xlsx');

            return Excel::download(
                new StockTransactionsExport($stockMovements, $this->date, $this->platform, $this->transactionType),
                $fileName
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export failed')
                ->body('An error occurred while exporting: ' . $e->getMessage())
                ->danger()
                ->send();

            Log::error('Excel export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function exportToPdf()
    {
        try {
            $stockMovements = $this->getExportData();

            if ($stockMovements->isEmpty()) {
                Notification::make()
                    ->title('No data to export')
                    ->body('There are no stock transaction records for the selected criteria.')
                    ->warning()
                    ->send();
                return;
            }

            $fileName = $this->generateFileName('pdf');

            // Calculate statistics
            $orderQuantity = $stockMovements->count();
            $productQuantity = $stockMovements->unique('productVariant.id')->count();
            $itemQuantity = $stockMovements->sum(function ($item) {
                return abs($item->quantity_change);
            });

            $data = [
                'stockMovements' => $stockMovements,
                'date' => $this->date,
                'platform' => $this->platform,
                'userName' => auth()->user()->name ?? 'System',
                'printTime' => Carbon::parse($this->date)->format('m-d_H-i-s'),
                'orderQuantity' => $orderQuantity,
                'productQuantity' => $productQuantity,
                'itemQuantity' => $itemQuantity,
            ];

            $pdf = Pdf::loadView('reports.stock-transactions-pdf', $data);
            $pdf->setPaper('A4', 'portrait');

            return response()->streamDownload(
                fn () => print($pdf->output()),
                $fileName
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export failed')
                ->body('An error occurred while exporting: ' . $e->getMessage())
                ->danger()
                ->send();

            Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function getExportData()
    {
        // Ensure date is valid
        try {
            $date = Carbon::parse($this->date)->format('Y-m-d');
        } catch (\Exception $e) {
            $date = now()->format('Y-m-d');
        }
        
        $query = StockMovement::query()
            ->with([
                'productVariant.product.productCategory',
                'productVariant.product.productSubCategory',
                'productVariant.platform',
                'user'
            ])
            ->join('product_variants', 'stock_movements.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('platforms', 'product_variants.platform_id', '=', 'platforms.id')
            ->whereDate('stock_movements.created_at', $date);

        // Filter by transaction type
        if ($this->transactionType === 'stock_in') {
            $query->where(function ($q) {
                $q->whereIn('stock_movements.movement_type', ['restock', 'return', 'initial_stock'])
                  ->orWhere('stock_movements.quantity_change', '>', 0);
            });
        } elseif ($this->transactionType === 'stock_out') {
            $query->where(function ($q) {
                $q->whereIn('stock_movements.movement_type', ['stock_out', 'sale'])
                  ->orWhere('stock_movements.quantity_change', '<', 0);
            });
        }

        if ($this->platform) {
            $query->where('platforms.name', $this->platform);
        }

        return $query->select('stock_movements.*')
            ->orderBy('stock_movements.created_at', 'desc')
            ->get();
    }

    protected function generateFileName(string $extension): string
    {
        $date = Carbon::parse($this->date)->format('Y-m-d');
        $platform = $this->platform ? "_{$this->platform}" : '';
        $type = $this->transactionType ? "_{$this->transactionType}" : '';
        $timestamp = now()->format('His');

        return "stock_transactions_{$date}{$platform}{$type}_{$timestamp}.{$extension}";
    }
}
