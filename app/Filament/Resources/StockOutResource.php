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
                            ->required(),

                        Forms\Components\ToggleButtons::make('platform')
                            ->label('Select Platform')
                            ->options([
                                'shopee' => 'Shopee',
                                'tiktok' => 'TikTok',
                                'bazar' => 'Bazar',
                                'others' => 'Others',
                            ])
                            ->icons([
                                'shopee' => 'heroicon-o-shopping-bag',
                                'tiktok' => 'heroicon-o-play',
                                'bazar' => 'heroicon-o-building-storefront',
                                'others' => 'heroicon-o-ellipsis-horizontal',
                            ])
                            ->colors([
                                'shopee' => 'warning',
                                'tiktok' => 'danger',
                                'bazar' => 'info',
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
                                'sale' => 'Sold/Order Fulfilled',
                                'damaged' => 'Damaged/Defective',
                                'lost' => 'Lost/Stolen',
                                'returned' => 'Returned to Supplier',
                                'expired' => 'Expired/Obsolete',
                                'transfer' => 'Transferred to Another Location',
                                'other' => 'Other',
                            ])
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
                Tables\Columns\TextColumn::make('created_at')
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
}
