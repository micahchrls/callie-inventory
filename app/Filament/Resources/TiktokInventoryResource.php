<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TiktokInventoryResource\Pages;
use App\Models\Product\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TiktokInventoryResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'TikTok Inventory';

    protected static ?string $modelLabel = 'TikTok Product';

    protected static ?string $pluralModelLabel = 'TikTok Inventory';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // hides from navigation
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('platform', function (Builder $query) {
                $query->where('name', 'TikTok');
            })
            ->with(['product', 'platform']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('product_name')
                            ->label('Product Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->product?->name),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('product_description')
                            ->label('Description')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2)
                            ->formatStateUsing(fn ($record) => $record?->product?->description),

                        Forms\Components\TextInput::make('platform_name')
                            ->label('Platform')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->platform?->name ?? 'TikTok'),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Forms\Components\Section::make('Stock Management')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity_in_stock')
                                    ->label('Current Stock')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        $reorderLevel = $get('reorder_level') ?? 5;
                                        if ($state <= 0) {
                                            $set('status', 'out_of_stock');
                                        } elseif ($state <= $reorderLevel) {
                                            $set('status', 'low_stock');
                                        } else {
                                            $set('status', 'in_stock');
                                        }
                                    }),

                                Forms\Components\TextInput::make('reorder_level')
                                    ->label('Reorder Level')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0)
                                    ->step(1)
                                    ->helperText('Alert when stock reaches this level')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        $currentStock = $get('quantity_in_stock') ?? 0;
                                        if ($currentStock <= 0) {
                                            $set('status', 'out_of_stock');
                                        } elseif ($currentStock <= $state) {
                                            $set('status', 'low_stock');
                                        } else {
                                            $set('status', 'in_stock');
                                        }
                                    }),
                            ]),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock',
                                'out_of_stock' => 'Out of Stock',
                                'discontinued' => 'Discontinued',
                            ])
                            ->default('in_stock')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive products will not be displayed in listings'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Variant Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('variation_name')
                                    ->label('Variation Name')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('size')
                                    ->label('Size')
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('color')
                                    ->label('Color')
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('material')
                                    ->label('Material')
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('weight')
                                    ->label('Weight (g)')
                                    ->numeric()
                                    ->step(0.01),
                            ]),

                        Forms\Components\KeyValue::make('additional_attributes')
                            ->label('Additional Attributes')
                            ->helperText('Add any extra product attributes as key-value pairs'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->tooltip(fn ($record) => $record->product->description),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied to clipboard'),

                Tables\Columns\TextColumn::make('variation_name')
                    ->label('Variation')
                    ->searchable()
                    ->placeholder('Standard'),

                Tables\Columns\TextColumn::make('size')
                    ->label('Size')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('color')
                    ->label('Color')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->color(fn ($state, $record) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= $record->reorder_level => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        'discontinued' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        'discontinued' => 'Discontinued',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Last Restocked')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->placeholder('Never'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        'discontinued' => 'Discontinued',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All products')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock Alert')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('quantity_in_stock <= reorder_level'))
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_in_stock', '<=', 0))
                    ->toggle(),

                Tables\Filters\SelectFilter::make('product.product_category_id')
                    ->label('Category')
                    ->relationship('product.productCategory', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Stock')
                    ->color('primary'),

                Tables\Actions\Action::make('quick_adjust')
                    ->label('Quick Adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\Section::make('Stock Adjustment')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Current Stock')
                                            ->disabled()
                                            ->default(fn ($record) => $record->quantity_in_stock),

                                        Forms\Components\TextInput::make('adjustment')
                                            ->label('Adjustment (+/-)')
                                            ->numeric()
                                            ->required()
                                            ->helperText('Enter positive number to add stock, negative to reduce'),
                                    ]),

                                Forms\Components\Textarea::make('reason')
                                    ->label('Reason for Adjustment')
                                    ->required()
                                    ->rows(2),
                            ]),
                    ])
                    ->action(function (array $data, $record): void {
                        $oldStock = $record->quantity_in_stock;
                        $newStock = $oldStock + $data['adjustment'];
                        $finalStock = max(0, $newStock);

                        $record->update(['quantity_in_stock' => $finalStock]);

                        // Update status based on new stock level
                        $record->updateStatus();

                        // Create stock movement record with correct field names
                        $record->stockMovements()->create([
                            'movement_type' => $data['adjustment'] > 0 ? 'restock' : 'adjustment',
                            'quantity_before' => $oldStock,
                            'quantity_change' => $data['adjustment'],
                            'quantity_after' => $finalStock,
                            'reason' => $data['reason'],
                            'user_id' => auth()->id(),
                            'platform' => 'TikTok',
                        ]);

                        Notification::make()
                            ->title('Stock Updated')
                            ->body("Stock adjusted by {$data['adjustment']}. New stock: ".$finalStock)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('stock_out')
                    ->label('Stock Out')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Section::make('Stock Out')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Current Stock')
                                            ->disabled()
                                            ->default(fn ($record) => $record->quantity_in_stock),

                                        Forms\Components\TextInput::make('quantity_out')
                                            ->label('Quantity to Remove')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->maxValue(fn ($record) => $record->quantity_in_stock)
                                            ->helperText('Enter the number of units to remove from stock')
                                            ->live()
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                if ($state && $get('current_stock')) {
                                                    $newStock = max(0, $get('current_stock') - $state);
                                                    $set('new_stock', $newStock);
                                                }
                                            }),

                                        Forms\Components\TextInput::make('new_stock')
                                            ->label('New Stock Level')
                                            ->disabled()
                                            ->default(fn ($record) => $record->quantity_in_stock),
                                    ]),

                                Forms\Components\Select::make('reason_type')
                                    ->label('Reason for Stock Out')
                                    ->options([
                                        'sold' => 'Sold/Order Fulfilled',
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

                                Forms\Components\Textarea::make('custom_reason')
                                    ->label('Custom Reason')
                                    ->visible(fn ($get) => $get('reason_type') === 'other')
                                    ->required(fn ($get) => $get('reason_type') === 'other')
                                    ->rows(2),

                                Forms\Components\Textarea::make('notes')
                                    ->label('Additional Notes (Optional)')
                                    ->rows(2),
                            ]),
                    ])
                    ->action(function (array $data, $record): void {
                        $oldStock = $record->quantity_in_stock;
                        $quantityOut = $data['quantity_out'];
                        $newStock = max(0, $oldStock - $quantityOut);

                        // Validate stock availability
                        if ($quantityOut > $oldStock) {
                            Notification::make()
                                ->title('Insufficient Stock')
                                ->body("Cannot remove {$quantityOut} units. Only {$oldStock} units available.")
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['quantity_in_stock' => $newStock]);

                        // Update status based on new stock level
                        $record->updateStatus();

                        // Build reason text
                        $reasonText = $data['reason_type'] === 'other'
                            ? $data['custom_reason']
                            : ucfirst(str_replace('_', ' ', $data['reason_type']));

                        if (! empty($data['notes'])) {
                            $reasonText .= ' - '.$data['notes'];
                        }

                        // Create stock movement record
                        $record->stockMovements()->create([
                            'movement_type' => 'stock_out',
                            'quantity_before' => $oldStock,
                            'quantity_change' => -$quantityOut,
                            'quantity_after' => $newStock,
                            'reason' => $reasonText,
                            'user_id' => auth()->id(),
                            'platform' => 'TikTok',
                        ]);

                        Notification::make()
                            ->title('Stock Out Successful')
                            ->body("Removed {$quantityOut} units. New stock: {$newStock}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->quantity_in_stock > 0),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make('export')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('tiktok-inventory-'.date('Y-m-d'))
                                ->withColumns([
                                    Column::make('product.name')->heading('Product Name'),
                                    Column::make('sku')->heading('SKU'),
                                    Column::make('variation_name')->heading('Variation'),
                                    Column::make('size')->heading('Size'),
                                    Column::make('color')->heading('Color'),
                                    Column::make('quantity_in_stock')->heading('Stock'),
                                    Column::make('reorder_level')->heading('Reorder Level'),
                                    Column::make('status')->heading('Status'),
                                ]),
                        ]),

                    BulkAction::make('bulk_stock_update')
                        ->label('Bulk Stock Update')
                        ->icon('heroicon-o-arrow-up')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('stock_adjustment')
                                ->label('Stock Adjustment (+/-)')
                                ->numeric()
                                ->required()
                                ->helperText('Positive to add, negative to reduce'),

                            Forms\Components\Textarea::make('reason')
                                ->label('Reason')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $newStock = $record->quantity_in_stock + $data['stock_adjustment'];
                                $record->update(['quantity_in_stock' => max(0, $newStock)]);

                                $record->stockMovements()->create([
                                    'movement_type' => $data['stock_adjustment'] > 0 ? 'restock' : 'adjustment',
                                    'quantity_before' => $record->quantity_in_stock,
                                    'quantity_change' => $data['stock_adjustment'],
                                    'quantity_after' => max(0, $newStock),
                                    'reason' => $data['reason'],
                                    'user_id' => auth()->id(),
                                ]);
                            }

                            Notification::make()
                                ->title('Bulk Stock Update Complete')
                                ->body(count($records).' products updated successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTiktokInventories::route('/'),
            'create' => Pages\CreateTiktokInventory::route('/create'),
            'view' => Pages\ViewTiktokInventory::route('/{record}'),
            'edit' => Pages\EditTiktokInventory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $lowStockCount = static::getEloquentQuery()
            ->whereRaw('quantity_in_stock <= reorder_level')
            ->count();

        return $lowStockCount > 0 ? (string) $lowStockCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'warning' : null;
    }
}
