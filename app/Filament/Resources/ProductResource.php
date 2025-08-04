<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 1;

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

    public static function canRestore($record): bool
    {
        return auth()->check() && auth()->user()->can('products.restore');
    }

    public static function canForceDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('products.force-delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product Categorization')
                    ->schema([
                        Forms\Components\Select::make('product_category_id')
                            ->label('Category')
                            ->options(ProductCategory::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('product_sub_category_id')
                            ->label('Sub Category')
                            ->options(ProductSubCategory::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product Variations')
                    ->schema([
                        Forms\Components\Placeholder::make('no_variants')
                            ->label('')
                            ->content('No variations found for this product.')
                            ->visible(fn ($record) => $record && $record->exists && !$record->variants()->exists()),

                        Forms\Components\Repeater::make('variants')
                            ->relationship('variants')
                            ->schema([
                                Forms\Components\Grid::make(5)
                                    ->schema([
                                        Forms\Components\TextInput::make('variation_display')
                                            ->label('Variation')
                                            ->disabled()
                                            ->formatStateUsing(function ($record) {
                                                if (!$record) return 'Standard';

                                                $variationName = $record->variation_name ?: 'Standard';
                                                if (!$record->variation_name) {
                                                    $attributes = array_filter([
                                                        $record->size,
                                                        $record->color,
                                                        $record->material,
                                                        $record->weight,
                                                    ]);
                                                    if (!empty($attributes)) {
                                                        $variationName = implode(' | ', $attributes);
                                                    }
                                                }
                                                return $variationName;
                                            }),

                                        Forms\Components\TextInput::make('sku')
                                            ->label('SKU')
                                            ->disabled()
                                            ->formatStateUsing(fn ($state) => $state ?: 'N/A'),

                                        Forms\Components\TextInput::make('quantity_in_stock')
                                            ->label('Stock')
                                            ->disabled()
                                            ->formatStateUsing(fn ($state) => number_format($state ?? 0)),

                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->disabled()
                                            ->options([
                                                'in_stock' => 'In Stock',
                                                'low_stock' => 'Low Stock',
                                                'out_of_stock' => 'Out of Stock',
                                                'discontinued' => 'Discontinued',
                                            ]),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->disabled(),
                                    ])
                            ])
                            ->disabled()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed(true)
                            ->itemLabel(function ($state) {
                                if (!$state) return 'Variation';

                                $variationName = $state['variation_name'] ?? 'Standard';
                                if (!$variationName || $variationName === 'Standard') {
                                    $attributes = array_filter([
                                        $state['size'] ?? null,
                                        $state['color'] ?? null,
                                        $state['material'] ?? null,
                                        $state['weight'] ?? null,
                                    ]);
                                    if (!empty($attributes)) {
                                        $variationName = implode(' | ', $attributes);
                                    }
                                }

                                $stock = $state['quantity_in_stock'] ?? 0;
                                $status = $state['status'] ?? 'unknown';

                                return $variationName . ' • Stock: ' . number_format($stock) . ' • ' . ucwords(str_replace('_', ' ', $status));
                            })
                            ->visible(fn ($record) => $record && $record->exists && $record->variants()->exists()),
                    ])
                    ->visible(fn ($record) => $record && $record->exists)
                    ->collapsible(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->description ? Str::limit($record->description, 50) : null),

                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('productSubCategory.name')
                    ->label('Sub Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('product_category_id')
                    ->label('Category')
                    ->relationship('productCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('product_sub_category_id')
                    ->label('Sub Category')
                    ->relationship('productSubCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('with_stock')
                    ->label('Has Stock')
                    ->query(fn (Builder $query): Builder => $query->withStock())
                    ->toggle(),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock Alert')
                    ->query(fn (Builder $query): Builder => $query->withLowStock())
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->outOfStock())
                    ->toggle(),

                Tables\Filters\Filter::make('has_variants')
                    ->label('Has Variants')
                    ->query(fn (Builder $query): Builder => $query->has('variants'))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_create_products')
                    ->label('🚀 Bulk Create Products')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->size('lg')
                    ->modal()
                    ->modalWidth('7xl')
                    ->modalHeading('🚀 Bulk Create Products')
                    ->modalDescription('Create multiple products at once with their basic information.')
                    ->form([
                        Forms\Components\Section::make('Products to Create')
                            ->description('Add multiple products with their information.')
                            ->schema([
                                Forms\Components\Repeater::make('products')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Product Name')
                                            ->required()
                                            ->maxLength(100)
                                            ->columnSpan(2),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('product_category_id')
                                            ->label('Category')
                                            ->options(ProductCategory::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\Select::make('product_sub_category_id')
                                            ->label('Sub Category')
                                            ->options(ProductSubCategory::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->maxItems(20)
                                    ->addActionLabel('+ Add Another Product')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Product')
                                    ->cloneable(),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $successCount = 0;
                        $errors = [];

                        \DB::transaction(function () use ($data, &$successCount, &$errors) {
                            foreach ($data['products'] as $index => $productData) {
                                try {
                                    Product::create($productData);
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $errors[] = "Product " . ($index + 1) . " ({$productData['name']}): " . $e->getMessage();
                                }
                            }
                        });

                        if ($successCount > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title("🎉 Bulk Creation Successful")
                                ->body("Successfully created {$successCount} products!" . ($errors ? " " . count($errors) . " products had errors." : ""))
                                ->success()
                                ->duration(10000)
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view_products')
                                        ->label('View Products')
                                        ->url(static::getUrl('index'))
                                        ->button(),
                                ])
                                ->send();
                        }

                        if ($errors) {
                            \Filament\Notifications\Notification::make()
                                ->title("⚠️ Some Products Failed")
                                ->body("Errors: " . implode("; ", array_slice($errors, 0, 3)) . (count($errors) > 3 ? "..." : ""))
                                ->warning()
                                ->duration(15000)
                                ->send();
                        }
                    })
                    ->successNotification(null), // Disable default notification since we handle it manually
            ])
            ->actions([
                Tables\Actions\Action::make('manage_variants')
                    ->label('Manage Variants')
                    ->icon('heroicon-o-cube')
                    ->color('success')
                    ->url(fn (Product $record): string => ProductVariantResource::getUrl('index', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_update_category')
                        ->label('📂 Update Category')
                        ->icon('heroicon-o-folder')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('product_category_id')
                                ->label('New Category')
                                ->options(ProductCategory::all()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('product_sub_category_id', null)),

                            Forms\Components\Select::make('product_sub_category_id')
                                ->label('New Sub Category')
                                ->options(fn (Forms\Get $get): array =>
                                    $get('product_category_id')
                                        ? ProductSubCategory::where('product_category_id', $get('product_category_id'))
                                            ->pluck('name', 'id')
                                            ->toArray()
                                        : []
                                )
                                ->searchable()
                                ->preload()
                                ->disabled(fn (Forms\Get $get): bool => !$get('product_category_id')),
                        ])
                        ->action(function (array $data, $records): void {
                            $updates = ['product_category_id' => $data['product_category_id']];
                            if (isset($data['product_sub_category_id'])) {
                                $updates['product_sub_category_id'] = $data['product_sub_category_id'];
                            }

                            foreach ($records as $record) {
                                $record->update($updates);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('📂 Categories Updated')
                                ->body("Categories updated for {$records->count()} products")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('🔄 Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->form([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active Status')
                                ->default(true),
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $record) {
                                $record->update(['is_active' => $data['is_active']]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('🔄 Status Updated')
                                ->body("Status updated for {$records->count()} products")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('No Products Found')
            ->emptyStateDescription('Start by creating your first product.')
            ->emptyStateIcon('heroicon-o-cube');
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
