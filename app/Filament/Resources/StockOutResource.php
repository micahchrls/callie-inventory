<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOutResource\Pages;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\StockOut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\Platform;

class StockOutResource extends Resource
{
    protected static ?string $model = StockOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->options(function () {
                                // Pre-render product names (initial dropdown list)
                                return Product::limit(50)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search): array {
                                return Product::query()
                                    ->where('name', 'like', "{$search}%") // ✅ uses index efficiently
                                    ->orWhereHas('variants', function ($query) use ($search) {
                                        $query->where('sku', 'like', "{$search}%");
                                    })
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => Product::query()->whereKey($value)->value('name') // ✅ optimized single-column fetch
                            )
                            ->required(),
                        Forms\Components\Select::make('product_variant_id')
                            ->label('Product Variant')
                            ->relationship('productVariant', 'sku')
                            ->searchable()
                            ->options(function () {
                                // Initial render – preload first 50 SKUs with product names
                                return ProductVariant::query()
                                    ->with('product:id,name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($variant) => [
                                        $variant->id => "{$variant->sku} — {$variant->product->name}",
                                    ])
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search): array {
                                return ProductVariant::query()
                                    ->with('product:id,name')
                                    ->where('sku', 'like', "{$search}%") // ✅ fast prefix search
                                    ->orWhereHas('product', function ($query) use ($search) {
                                        $query->where('name', 'like', "{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($variant) => [
                                        $variant->id => "{$variant->sku} — {$variant->product->name}",
                                    ])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => ProductVariant::query()
                                ->with('product:id,name')
                                ->whereKey($value)
                                ->get()
                                ->map(fn ($variant) => "{$variant->sku} — {$variant->product->name}")
                                ->first()
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $variant = ProductVariant::find($state);
                                    if ($variant) {
                                        $set('current_stock_info', [
                                            'quantity' => $variant->quantity_in_stock,
                                            'status' => $variant->getStockStatusText(),
                                            'status_color' => $variant->getStockStatusColor(),
                                            'reorder_level' => $variant->reorder_level,
                                        ]);
                                    }
                                } else {
                                    $set('current_stock_info', null);
                                }
                            })
                            ->required(),

                        Forms\Components\Placeholder::make('stock_information')
                            ->label('Current Stock Information')
                            ->content(function ($get) {
                                $stockInfo = $get('current_stock_info');

                                if (! $stockInfo) {
                                    return new \Illuminate\Support\HtmlString('<span class="text-gray-500 text-sm">Select a product variant to view current stock information</span>');
                                }

                                $quantity = $stockInfo['quantity'] ?? 0;
                                $status = $stockInfo['status'] ?? 'Unknown';
                                $statusColor = $stockInfo['status_color'] ?? 'gray';
                                $reorderLevel = $stockInfo['reorder_level'] ?? 0;

                                $colorClass = match ($statusColor) {
                                    'success' => 'text-green-600 bg-green-50 border-green-200',
                                    'warning' => 'text-yellow-600 bg-yellow-50 border-yellow-200',
                                    'danger' => 'text-red-600 bg-red-50 border-red-200',
                                    default => 'text-gray-600 bg-gray-50 border-gray-200',
                                };

                                return new \Illuminate\Support\HtmlString(
                                    "<div class='p-3 rounded-lg border {$colorClass}'>
                                        <div class='flex items-center justify-between'>
                                            <div>
                                                <p class='font-semibold text-sm'>Available Stock: {$quantity} units</p>
                                                <p class='text-xs opacity-75'>Reorder Level: {$reorderLevel} units</p>
                                            </div>
                                            <div>
                                                <span class='px-2 py-1 text-xs font-medium rounded-full bg-current bg-opacity-10'>
                                                    {$status}
                                                </span>
                                            </div>
                                        </div>
                                    </div>"
                                );
                            })
                            ->visible(fn ($get) => $get('product_variant_id'))
                            ->columnSpanFull(),
                        Forms\Components\ToggleButtons::make('platform')
                            ->label('Select Platform')
                            ->options([
                                'shopee' => 'Shopee',
                                'tiktok' => 'TikTok',
                                'bazaar' => 'Bazaar',
                                'others' => 'Others',
                            ])
                            ->icons([
                                'shopee' => 'heroicon-o-shopping-bag',
                                'tiktok' => 'heroicon-o-play',
                                'bazaar' => 'heroicon-o-building-storefront',
                                'others' => 'heroicon-o-ellipsis-horizontal',
                            ])
                            ->colors([
                                'shopee' => 'warning',
                                'tiktok' => 'danger',
                                'bazaar' => 'info',
                                'others' => 'gray',
                            ])
                            ->inline()
                            ->grouped()
                            ->columnSpanFull()
                            ->required(),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('total_quantity')
                            ->label('Quantity Out')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Select::make('reason')
                            ->label('Reason for Stock Out')
                            ->options([
                                'sold' => 'Sold/Order Fulfilled', // 🔑 Match this with default
                                'damaged' => 'Damaged/Defective',
                                'lost' => 'Lost/Stolen',
                                'returned' => 'Returned to Supplier',
                                'expired' => 'Expired/Obsolete',
                                'transfer' => 'Transferred to Another Location',
                                'other' => 'Other',
                            ])
                            ->default('sold') // ✅ Must match the option key
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === 'other') {
                                    $set('show_custom_reason', true);
                                } else {
                                    $set('show_custom_reason', false);
                                    $set('custom_reason', null);
                                }
                            }),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'success',
                        'damaged' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->numeric(),
                Tables\Columns\TextColumn::make('tiktok_quantity')
                    ->label('TikTok')
                    ->getStateUsing(function (StockOut $record): int {
                        return $record->stockOutItems->where('platform', 'tiktok')->sum('quantity');
                    })
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '-')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('shopee_quantity')
                    ->label('Shopee')
                    ->getStateUsing(function (StockOut $record): int {
                        return $record->stockOutItems->where('platform', 'shopee')->sum('quantity');
                    })
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '-')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('bazar_quantity')
                    ->label('Bazaar')
                    ->getStateUsing(function (StockOut $record): int {
                        return $record->stockOutItems->where('platform', Platform::BAZAAR->value)->sum('quantity');
                    })
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '-')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('others_quantity')
                    ->label('Others')
                    ->getStateUsing(function (StockOut $record): int {
                        return $record->stockOutItems->where('platform', 'others')->sum('quantity');
                    })
                    ->badge()
                    ->color('secondary')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state) : '-')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'sale' => 'Sale',
                        'damaged' => 'Damaged',
                        'expired' => 'Expired',
                        'lost' => 'Lost',
                        'promotion' => 'Promotion',
                        'sample' => 'Sample',
                        'return_to_supplier' => 'Return to Supplier',
                        'quality_issue' => 'Quality Issue',
                        'theft' => 'Theft',
                        'adjustment' => 'Adjustment',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOuts::route('/'),
            'create' => Pages\CreateStockOut::route('/create'),
            'edit' => Pages\EditStockOut::route('/{record}/edit'),
            'view' => Pages\ViewStockOut::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayStockOutCount = StockOut::whereDate('created_at', today())->count();

        return $todayStockOutCount > 0 ? $todayStockOutCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $todayStockOutCount = StockOut::whereDate('created_at', today())->count();

        return $todayStockOutCount > 0 ? 'info' : null;
    }
}
