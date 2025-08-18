<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?string $modelLabel = 'Stock Movement';

    protected static ?string $pluralModelLabel = 'Stock Movements';

    protected static ?string $navigationGroup = 'Inventory Management';

    protected static ?int $navigationSort = 3;

    // Role-based access control - staff can view movements but not modify
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('stock.movements.view');
    }

    public static function canView($record): bool
    {
        return auth()->check() && auth()->user()->can('stock.movements.view');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Movement Details')
                    ->schema([
                        Forms\Components\Select::make('product_variant_id')
                            ->label('Product Variant')
                            ->relationship(
                                'productVariant',
                                'sku',
                                fn (Builder $query) => $query->with('product')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->sku.' - '.$record->product->name.
                                ($record->variation_name ? ' ('.$record->variation_name.')' : '')
                            )
                            ->searchable()
                            ->required()
                            ->disabled(),

                        Forms\Components\Select::make('movement_type')
                            ->label('Movement Type')
                            ->options([
                                'restock' => 'Restock',
                                'sale' => 'Sale',
                                'adjustment' => 'Adjustment',
                                'damage' => 'Damage',
                                'loss' => 'Loss',
                                'return' => 'Return',
                                'transfer' => 'Transfer',
                                'initial_stock' => 'Initial Stock',
                                'manual_edit' => 'Manual Edit',
                            ])
                            ->required()
                            ->disabled(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quantity_before')
                                    ->label('Quantity Before')
                                    ->numeric()
                                    ->disabled(),

                                Forms\Components\TextInput::make('quantity_change')
                                    ->label('Quantity Change')
                                    ->numeric()
                                    ->disabled(),

                                Forms\Components\TextInput::make('quantity_after')
                                    ->label('Quantity After')
                                    ->numeric()
                                    ->disabled(),
                            ]),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Reference Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('reference_type')
                                    ->label('Reference Type')
                                    ->disabled(),

                                Forms\Components\TextInput::make('reference_id')
                                    ->label('Reference ID')
                                    ->disabled(),
                            ]),

                        Forms\Components\TextInput::make('platform')
                            ->label('Platform')
                            ->disabled(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->disabled()
                            ->rows(2),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->disabled()
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('Audit Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->disabled(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('ip_address')
                                    ->label('IP Address')
                                    ->disabled(),

                                Forms\Components\TextInput::make('created_at')
                                    ->label('Date & Time')
                                    ->disabled(),
                            ]),

                        Forms\Components\Textarea::make('user_agent')
                            ->label('Browser Info')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Movement Details')
                    ->schema([
                        TextEntry::make('productVariant.sku')
                            ->label('SKU')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('productVariant.product.name')
                            ->label('Product'),

                        TextEntry::make('productVariant.variation_name')
                            ->label('Variation')
                            ->placeholder('Standard'),

                        TextEntry::make('movement_type')
                            ->label('Movement Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'restock' => 'success',
                                'sale' => 'info',
                                'adjustment' => 'warning',
                                'damage', 'loss' => 'danger',
                                'return' => 'primary',
                                'transfer' => 'secondary',
                                'initial_stock' => 'gray',
                                'manual_edit' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'restock' => 'Restock',
                                'sale' => 'Sale',
                                'adjustment' => 'Adjustment',
                                'damage' => 'Damage',
                                'loss' => 'Loss',
                                'return' => 'Return',
                                'transfer' => 'Transfer',
                                'initial_stock' => 'Initial Stock',
                                'manual_edit' => 'Manual Edit',
                                default => ucfirst($state),
                            }),

                        TextEntry::make('quantity_before')
                            ->label('Quantity Before')
                            ->numeric(),

                        TextEntry::make('quantity_change')
                            ->label('Quantity Change')
                            ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').number_format($state))
                            ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                            ->weight('bold'),

                        TextEntry::make('quantity_after')
                            ->label('Quantity After')
                            ->numeric()
                            ->weight('bold'),

                        TextEntry::make('created_at')
                            ->label('Date & Time')
                            ->dateTime('M d, Y \a\t H:i'),
                    ])
                    ->columns(3),

                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('User')
                            ->placeholder('System'),

                        TextEntry::make('platform')
                            ->label('Platform')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('reason')
                            ->label('Reason')
                            ->placeholder('No reason provided'),

                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),

                        TextEntry::make('reference_type')
                            ->label('Reference Type')
                            ->placeholder('No reference'),

                        TextEntry::make('reference_id')
                            ->label('Reference ID')
                            ->placeholder('No reference ID'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('productVariant.product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('productVariant.variation_name')
                    ->label('Variation')
                    ->getStateUsing(function ($record) {
                        if ($record->productVariant->variation_name) {
                            return $record->productVariant->variation_name;
                        }

                        $attributes = array_filter([
                            $record->productVariant->size,
                            $record->productVariant->color,
                            $record->productVariant->material,
                        ]);

                        return ! empty($attributes) ? implode(' | ', $attributes) : 'Standard';
                    })
                    ->limit(20),

                Tables\Columns\TextColumn::make('productVariant.platform.name')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'TikTok' => 'purple',
                        'Shopee' => 'warning',
                        'Lazada' => 'info',
                        null, '' => 'gray',
                        default => 'primary',
                    })
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'restock' => 'success',
                        'sale' => 'info',
                        'adjustment' => 'warning',
                        'damage', 'loss' => 'danger',
                        'return' => 'primary',
                        'transfer' => 'secondary',
                        'initial_stock' => 'gray',
                        'manual_edit' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'restock' => 'Restock',
                        'sale' => 'Sale',
                        'adjustment' => 'Adjustment',
                        'damage' => 'Damage',
                        'loss' => 'Loss',
                        'return' => 'Return',
                        'transfer' => 'Transfer',
                        'initial_stock' => 'Initial Stock',
                        'manual_edit' => 'Manual Edit',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('quantity_before')
                    ->label('Before')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Change')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').number_format($state))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('quantity_after')
                    ->label('After')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')
                    ->label('Movement Type')
                    ->options([
                        'restock' => 'Restock',
                        'sale' => 'Sale',
                        'adjustment' => 'Adjustment',
                        'damage' => 'Damage',
                        'loss' => 'Loss',
                        'return' => 'Return',
                        'transfer' => 'Transfer',
                        'initial_stock' => 'Initial Stock',
                        'manual_edit' => 'Manual Edit',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('stock_increases')
                    ->label('Stock Increases')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_change', '>', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('stock_decreases')
                    ->label('Stock Decreases')
                    ->query(fn (Builder $query): Builder => $query->where('quantity_change', '<', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->headerActions([
                Tables\Actions\Action::make('product_history')
                    ->label('View Product History')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->visible(fn (): bool => request()->has('record'))
                    ->action(function () {
                        $recordId = request()->get('record');
                        if ($recordId) {
                            $movement = StockMovement::find($recordId);
                            if ($movement) {
                                return redirect()->route('filament.admin.resources.stock-movements.index', [
                                    'tableFilters' => [
                                        'product_variant' => [
                                            'value' => $movement->product_variant_id,
                                        ],
                                    ],
                                ]);
                            }
                        }
                    }),

                //                Tables\Actions\Action::make('variant_timeline')
                //                    ->label('Variant Timeline')
                //                    ->icon('heroicon-o-list-bullet')
                //                    ->color('primary')
                //                    ->modalHeading('Stock Movement History')
                //                    ->modalWidth(MaxWidth::SevenExtraLarge)
                //                    ->modalContent(function () {
                //                        // Get current record if viewing individual movement
                //                        $currentRecordId = request()->get('record');
                //                        $productVariantId = null;
                //
                //                        if ($currentRecordId) {
                //                            $currentMovement = StockMovement::find($currentRecordId);
                //                            $productVariantId = $currentMovement?->product_variant_id;
                //                        }
                //
                //                        if (!$productVariantId) {
                //                            return null;
                //                        }
                //
                //                        // Create a new table instance for the modal
                //                        return Tables\Table::make()
                //                            ->query(
                //                                StockMovement::query()
                //                                    ->where('product_variant_id', $productVariantId)
                //                                    ->with(['productVariant.product', 'user'])
                //                                    ->orderBy('created_at', 'desc')
                //                            )
                //                            ->columns([
                //                                Tables\Columns\TextColumn::make('created_at')
                //                                    ->label('Date & Time')
                //                                    ->dateTime('M d, Y H:i')
                //                                    ->sortable(),
                //
                //                                Tables\Columns\TextColumn::make('movement_type')
                //                                    ->label('Type')
                //                                    ->badge()
                //                                    ->color(fn (string $state): string => match ($state) {
                //                                        'restock' => 'success',
                //                                        'sale' => 'info',
                //                                        'adjustment' => 'warning',
                //                                        'damage', 'loss' => 'danger',
                //                                        'return' => 'primary',
                //                                        'transfer' => 'secondary',
                //                                        'initial_stock' => 'gray',
                //                                        'manual_edit' => 'warning',
                //                                        default => 'gray',
                //                                    }),
                //
                //                                Tables\Columns\TextColumn::make('quantity_before')
                //                                    ->label('Before')
                //                                    ->alignCenter(),
                //
                //                                Tables\Columns\TextColumn::make('quantity_change')
                //                                    ->label('Change')
                //                                    ->alignCenter()
                //                                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . number_format($state))
                //                                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                //
                //                                Tables\Columns\TextColumn::make('quantity_after')
                //                                    ->label('After')
                //                                    ->alignCenter(),
                //
                //                                Tables\Columns\TextColumn::make('user.name')
                //                                    ->label('User')
                //                                    ->placeholder('System'),
                //
                //                                Tables\Columns\TextColumn::make('reason')
                //                                    ->label('Reason')
                //                                    ->limit(30)
                //                                    ->placeholder('No reason'),
                //                            ])
                //                            ->paginated([10, 25, 50]);
                //                    })
                //                    ->modalSubmitAction(false)
                //                    ->modalCancelActionLabel('Close'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for audit trail - read only
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
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
            'index' => Pages\ListStockMovements::route('/'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Stock movements are auto-generated, not manually created
    }

    public static function canEdit($record): bool
    {
        return false; // Audit trail should be immutable
    }

    public static function canDelete($record): bool
    {
        return false; // Audit trail should be permanent
    }

    public static function getNavigationBadge(): ?string
    {
        return StockMovement::whereDate('created_at', today())->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $todayCount = StockMovement::whereDate('created_at', today())->count();

        return $todayCount > 0 ? 'primary' : null;
    }
}
