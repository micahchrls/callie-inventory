<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
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
                // Product Information Section
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Product Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        // Auto-generate SKU when name changes
                                        if (!empty($state)) {
                                            $words = explode(' ', $state);
                                            $sku = '';
                                            foreach ($words as $word) {
                                                $cleanWord = trim($word);
                                                if (!empty($cleanWord)) {
                                                    $sku .= strtoupper(substr($cleanWord, 0, 1));
                                                }
                                            }
                                            $set('base_sku', $sku);
                                        }
                                    }),

                                Forms\Components\TextInput::make('base_sku')
                                    ->label('SKU')
                                    ->placeholder('Auto-generated from product name')
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('This will be the product SKU. For products with variants, this becomes the base SKU for variant generation.')
                                    ->readOnly()
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
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
                            ]),
                    ])
                    ->columns(2),

                // Product Variants Section (only show on create)
                Forms\Components\Section::make('Product Variants')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship('variants')
                            ->schema([
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('size')
                                            ->label('Size')
                                            ->placeholder('e.g., Small, 7, 18"')
                                            ->maxLength(50)
                                            ->live()
                                            ->afterStateUpdated(fn ($state, $set, $get) => $set('sku', self::generateVariantSku($get))),

                                        Forms\Components\TextInput::make('color')
                                            ->label('Color')
                                            ->placeholder('e.g., Gold, Silver')
                                            ->maxLength(50)
                                            ->live()
                                            ->afterStateUpdated(fn ($state, $set, $get) => $set('sku', self::generateVariantSku($get))),

                                        Forms\Components\TextInput::make('material')
                                            ->label('Material')
                                            ->placeholder('e.g., 14K Gold')
                                            ->maxLength(100)
                                            ->live()
                                            ->afterStateUpdated(fn ($state, $set, $get) => $set('sku', self::generateVariantSku($get))),

                                        Forms\Components\TextInput::make('variant_initial')
                                            ->label('Variant Initial')
                                            ->maxLength(50)
                                            ->live()
                                            ->afterStateUpdated(fn ($state, $set, $get) => $set('sku', self::generateVariantSku($get))),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('sku')
                                            ->label('SKU')
                                            ->disabled()
                                            ->dehydrated()
                                            ->unique('product_variants', 'sku', ignoreRecord: true)
                                            ->helperText('Auto-generated based on variant attributes')
                                            ->required(),

                                        Forms\Components\TextInput::make('quantity_in_stock')
                                            ->label('Initial Stock')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->helperText('Starting inventory quantity')
                                            ->required(),

                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->numeric()
                                            ->default(5)
                                            ->minValue(0)
                                            ->helperText('Minimum stock before reorder alert')
                                            ->required(),
                                    ]),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'in_stock' => 'In Stock',
                                        'low_stock' => 'Low Stock',
                                        'out_of_stock' => 'Out of Stock',
                                    ])
                                    ->default('in_stock')
                                    ->required(),

                                Forms\Components\KeyValue::make('additional_attributes')
                                    ->label('Additional Attributes')
                                    ->keyLabel('Attribute')
                                    ->valueLabel('Value')
                                    ->addActionLabel('Add Attribute')
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn (array $state): ?string =>
                                !empty($state['sku'])
                                    ? "Variant: {$state['sku']}"
                                    : 'New Variant'
                            )
                            ->addActionLabel('Add Product Variant')
                            ->collapsible()
                            ->cloneable()
                            ->deleteAction(
                                fn ($action) => $action->requiresConfirmation()
                            )
                            ->defaultItems(0)
                            ->minItems(0)
                            ->columnSpanFull(),
                    ])
                    ->description('Add variants for this product (optional). You can also add variants later from the product detail page. If you do not add any variants, the product will use the base SKU.')
                    ->visible(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->collapsible()
                    ->collapsed()
                    ->columns(1),
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
            RelationManagers\ProductVariantRelationManager::class,
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

    /**
     * Generate variant SKU preview for form display
     */
    protected function generateVariantSkuPreview($get, $set): void
    {
        $productName = $get('../../name');
        $baseSku = $get('../../base_sku');

        if (!$baseSku && $productName) {
            $words = explode(' ', $productName);
            $baseSku = '';
            foreach ($words as $word) {
                $cleanWord = trim($word);
                if (!empty($cleanWord)) {
                    $baseSku .= strtoupper(substr($cleanWord, 0, 1));
                }
            }
        }

        if (!$baseSku) {
            return;
        }

        // Generate variant code
        $variantCode = self::generateVariantCode(
            $get('size'),
            $get('color'),
            $get('material'),
            $get('variant_initial')
        );

        if ($variantCode) {
            $sku = sprintf('%s-%s-0001', $baseSku, $variantCode);
        } else {
            $sku = $baseSku;
        }

        $set('sku', $sku);
    }

    /**
     * Generate final variant SKU with incremental numbering
     */
    protected function generateFinalVariantSku(string $baseSku, array $variantData): string
    {
        if (empty($baseSku)) {
            return '';
        }

        // Generate variant code
        $variantCode = self::generateVariantCode(
            $variantData['size'] ?? null,
            $variantData['color'] ?? null,
            $variantData['material'] ?? null,
            $variantData['variant_initial'] ?? null
        );

        if ($variantCode) {
            // For now, just return with -0001 suffix. In a real implementation,
            // you'd check for existing SKUs and increment accordingly
            return sprintf('%s-%s-0001', $baseSku, $variantCode);
        }

        return $baseSku;
    }

    /**
     * Generate variant code from attributes
     */
    protected static function generateVariantCode(?string $size, ?string $color, ?string $material, ?string $variantInitial): string
    {
        $code = '';

        // Size - take as-is
        if (!empty($size)) {
            $code .= strtoupper(substr(trim($size), 0, 1));
        }

        // Color - first letter
        if (!empty($color)) {
            $code .= strtoupper(substr(trim($color), 0, 1));
        }

        // Material - first letter
        if (!empty($material)) {
            $code .= strtoupper(substr(trim($material), 0, 1));
        }

        // Variant Initial - as provided
        if (!empty($variantInitial)) {
            $code .= strtoupper($variantInitial);
        }

        return $code;
    }

    protected static function generateVariantSku($get): string
    {
        $baseSku = $get('../../base_sku');

        if (!$baseSku) {
            return '';
        }

        // Generate variant code
        $variantCode = self::generateVariantCode(
            $get('size'),
            $get('color'),
            $get('material'),
            $get('variant_initial')
        );

        if ($variantCode) {
            // For now, just return with -0001 suffix. In a real implementation,
            // you'd check for existing SKUs and increment accordingly
            return sprintf('%s-%s-0001', $baseSku, $variantCode);
        }

        return $baseSku;
    }
}
