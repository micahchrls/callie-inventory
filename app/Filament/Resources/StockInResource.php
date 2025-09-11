<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockInResource\Pages;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\StockIn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class StockInResource extends Resource
{
    protected static ?string $model = StockIn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(Auth::id())
                    ->required(),

                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->options(function () {
                        // Pre render product names (Initial dropdown list)
                        return Product::limit(50)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->getSearchResultsUsing(fn (string $search): array => Product::where('name', 'like', "%{$search}%")
                        ->orWhere('base_sku', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($query) use ($search) {
                            $query->where('sku', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name
                    )
                    ->required(),
                Forms\Components\Select::make('product_variant_id')
                    ->relationship('productVariant', 'sku')
                    ->searchable()
                    ->options(function () {
                        // Initial render - preload first 50 SKUs with product names
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
                            return new HtmlString('<span class="text-gray-500 text-sm">Select a product variant to view current stock information</span>');
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

                        return new HtmlString(
                            "<div class='p-3 rounded-lg border {$colorClass}'>
                                <div class='flex items-center justify-between'>
                                    <div>
                                        <p class='font-semibold text-sm'>Current Stock: {$quantity} units</p>
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

                Forms\Components\Select::make('reason')
                    ->label('Reason for Stock In')
                    ->options([
                        'returned' => 'Returned to Supplier',
                        'return_to_callie' => 'Return to Callie',
                        'restock' => 'Restock',
                        'other' => 'Other',
                    ])
                    ->default('restock')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state == 'other') {
                            $set('show_custom_reason', true);
                        } else {
                            $set('show_custom_reason', false);
                            $set('custom_reason', null);
                        }
                    }),
                Forms\Components\TextInput::make('total_quantity')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2),

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
                Tables\Columns\TextColumn::make('reason'),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->color('success')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable()
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockIns::route('/'),
            'create' => Pages\CreateStockIn::route('/create'),
            'edit' => Pages\EditStockIn::route('/{record}/edit'),
            'view' => Pages\ViewStockIn::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $todayStockInCount = StockIn::whereDate('created_at', today())->count();

        return $todayStockInCount > 0 ? $todayStockInCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $todayStockInCount = StockIn::whereDate('created_at', today())->count();

        return $todayStockInCount > 0 ? 'success' : null;
    }
}
