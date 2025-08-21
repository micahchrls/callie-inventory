<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Filament\Resources\ProductVariantResource\RelationManagers;
use App\Models\Product\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Product Variants';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Product Management';

    // Role-based access control - staff can view/create/edit, only owners can delete
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('products.view');
    }

    public static function canView($record): bool
    {
        return auth()->check() && auth()->user()->can('products.view');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('products.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('products.edit');
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('products.delete');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->check() && auth()->user()->can('products.delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('SKU copied!')
                    ->copyMessageDuration(1500)
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('variation_name')
                    ->label('Variation')
                    ->getStateUsing(function ($record) {
                        // Get the main variant's variation name or build from attributes
                        if ($record->variation_name) {
                            return $record->variation_name;
                        }

                        // Build variation name from attributes if no explicit name
                        $attributes = array_filter([
                            $record->size,
                            $record->color,
                            $record->material,
                            $record->weight,
                        ]);

                        return ! empty($attributes) ? implode(' | ', $attributes) : 'Standard';
                    })
                    ->searchable()
                    ->sortable(false)
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 30 ? $state : null;
                    })
                    ->color('gray'),

                Tables\Columns\TextColumn::make('product.productCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('product.productSubCategory.name')
                    ->label('Sub Category')
                    ->getStateUsing(function ($record) {
                        return $record->product->productSubCategory ? $record->product->productSubCategory->name : '-';
                    })
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('secondary')
                    ->placeholder('-'),

                // Stock Quantity Column (Display Only)
                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock Qty')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->quantity_in_stock <= 0 => 'danger',
                        $record->quantity_in_stock <= $record->reorder_level => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn ($state) => number_format($state)),

                // Reorder Level Column (Display Only)
                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder At')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->color('gray'),

                // Status Column (Dynamically Calculated)
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        // Calculate status based on current stock and reorder level
                        if ($record->quantity_in_stock <= 0) {
                            return 'out_of_stock';
                        } elseif ($record->quantity_in_stock <= $record->reorder_level) {
                            return 'low_stock';
                        } else {
                            return 'in_stock';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        'discontinued' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                // Active Status Column (Display Only)
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Last Restocked')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Never')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All variants')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock Alert')
                    ->query(fn (Builder $query): Builder => $query->lowStock())
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->outOfStock())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),

                    BulkAction::make('bulk_status_update')
                        ->label('Update Status')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'in_stock' => 'In Stock',
                                    'low_stock' => 'Low Stock',
                                    'out_of_stock' => 'Out of Stock',
                                ])
                                ->required(),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active Status'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->status = $data['status'];
                                if (isset($data['is_active'])) {
                                    $record->is_active = $data['is_active'];
                                }
                                $record->save();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ShopeeStockOutsRelationManager::class,
            RelationManagers\TiktokStockOutsRelationManager::class,
            RelationManagers\BazarStockOutsRelationManager::class,
            RelationManagers\OthersStockOutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),

            'view' => Pages\ViewProductVariant::route('/{record}'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
