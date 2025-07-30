<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Product Variants';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535),
                                Forms\Components\Select::make('product_category_id')
                                    ->label('Category')
                                    ->relationship('productCategory', 'name')
                                    ->required(),
                                Forms\Components\Select::make('product_sub_category_id')
                                    ->label('Sub Category')
                                    ->relationship('productSubCategory', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),

                        Forms\Components\Toggle::make('_auto_generate_sku')
                            ->label('Auto Generate SKU')
                            ->helperText('Enable to automatically generate SKU based on product and variant attributes')
                            ->default(true)
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->unique(ProductVariant::class, 'sku', ignoreRecord: true)
                            ->maxLength(255)
                            ->required(fn (Forms\Get $get) => !$get('_auto_generate_sku'))
                            ->hidden(fn (Forms\Get $get) => $get('_auto_generate_sku'))
                            ->helperText('Enter a unique SKU for this variant'),

                        Forms\Components\Placeholder::make('generated_sku_preview')
                            ->label('Generated SKU Preview')
                            ->content(function (Forms\Get $get, ?ProductVariant $record) {
                                if (!$get('_auto_generate_sku')) {
                                    return null;
                                }

                                if (!$get('product_id')) {
                                    return 'Please select a product first';
                                }

                                // Create a temporary variant for preview
                                $tempVariant = new ProductVariant([
                                    'product_id' => $get('product_id'),
                                    'size' => $get('size'),
                                    'color' => $get('color'),
                                    'material' => $get('material'),
                                ]);

                                // Load the product relationship for SKU generation
                                if ($tempVariant->product_id) {
                                    $tempVariant->setRelation('product', Product::find($tempVariant->product_id));
                                    return $tempVariant->generateSku();
                                }

                                return 'SKU will be generated automatically';
                            })
                            ->visible(fn (Forms\Get $get) => $get('_auto_generate_sku'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('variation_name')
                            ->label('Variation Name')
                            ->placeholder('e.g., Large Gold, Size 7 Silver')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Variant Attributes')
                    ->schema([
                        Forms\Components\TextInput::make('size')
                            ->label('Size')
                            ->placeholder('e.g., Small, 7, 18"')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('color')
                            ->label('Color')
                            ->placeholder('e.g., Gold, Silver, Red')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('material')
                            ->label('Material')
                            ->placeholder('e.g., 14K Gold, Sterling Silver')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('weight')
                            ->label('Weight')
                            ->placeholder('e.g., 2.5g, 0.5oz')
                            ->maxLength(50),

                        Forms\Components\KeyValue::make('additional_attributes')
                            ->label('Additional Attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Attribute Value'),
                    ])->columns(2),

                Forms\Components\Section::make('Inventory Management')
                    ->schema([
                        Forms\Components\TextInput::make('quantity_in_stock')
                            ->label('Quantity in Stock')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\TextInput::make('reorder_level')
                            ->label('Reorder Level')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(10),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock',
                                'out_of_stock' => 'Out of Stock',
                            ])
                            ->default('in_stock')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([


                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\DateTimePicker::make('last_restocked_at')
                            ->label('Last Restocked')
                            ->displayFormat('M d, Y H:i'),
                    ])->columns(2),
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
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('variation_name')
                    ->label('Variation')
                    ->searchable()
                    ->placeholder('No specific variation'),

                Tables\Columns\TextColumn::make('size')
                    ->label('Size')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('color')
                    ->label('Color')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('material')
                    ->label('Material')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Last Restocked')
                    ->dateTime('M d, Y')
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
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
            ->headerActions([
                Tables\Actions\Action::make('bulk_create_variants')
                    ->label('🚀 Bulk Create Variants')
                    ->icon('heroicon-o-squares-plus')
                    ->color('success')
                    ->size('lg')
                    ->modal()
                    ->modalWidth('7xl')
                    ->modalHeading('🚀 Bulk Create Product Variants')
                    ->modalDescription('Create multiple variants for existing products at once.')
                    ->form([
                        Forms\Components\Section::make('Base Product Selection')
                            ->description('Select the product and set default values for all variants (can be overridden per variant)')
                            ->schema([
                                Forms\Components\Select::make('base_product_id')
                                    ->label('Base Product')
                                    ->placeholder('Select product to create variants for')
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Toggle::make('auto_generate_sku')
                                    ->label('Auto Generate SKUs')
                                    ->default(true)
                                    ->helperText('Automatically generate unique SKUs based on product and variant attributes'),

                                Forms\Components\Select::make('default_status')
                                    ->label('Default Status')
                                    ->options([
                                        'in_stock' => 'In Stock',
                                        'low_stock' => 'Low Stock',
                                        'out_of_stock' => 'Out of Stock',
                                    ])
                                    ->default('in_stock'),

                                Forms\Components\TextInput::make('default_quantity')
                                    ->label('Default Stock Quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),

                                Forms\Components\TextInput::make('default_reorder_level')
                                    ->label('Default Reorder Level')
                                    ->numeric()
                                    ->default(10)
                                    ->minValue(0),
                            ])
                            ->columns(3)
                            ->collapsible(),

                        Forms\Components\Section::make('Variants to Create')
                            ->description('Add multiple variants with their specific attributes. Base product and default values will be used where not specified.')
                            ->schema([
                                Forms\Components\Repeater::make('variants')
                                    ->schema([
                                        Forms\Components\TextInput::make('variation_name')
                                            ->label('Variation Name')
                                            ->required()
                                            ->maxLength(100)
                                            ->columnSpan(2)
                                            ->placeholder('e.g., Gold Small Ring'),

                                        Forms\Components\TextInput::make('sku')
                                            ->label('Custom SKU (optional)')
                                            ->helperText('Leave empty for auto-generation')
                                            ->maxLength(50)
                                            ->columnSpan(2)
                                            ->hidden(fn (Forms\Get $get): bool => $get('../../auto_generate_sku')),

                                        Forms\Components\Select::make('size')
                                            ->label('Size')
                                            ->options([
                                                'XS' => 'Extra Small',
                                                'S' => 'Small',
                                                'M' => 'Medium',
                                                'L' => 'Large',
                                                'XL' => 'Extra Large',
                                                '6' => 'Size 6',
                                                '7' => 'Size 7',
                                                '8' => 'Size 8',
                                                '9' => 'Size 9',
                                                '10' => 'Size 10',
                                                '11' => 'Size 11',
                                                '12' => 'Size 12',
                                            ])
                                            ->searchable()
                                            ->allowHtml()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('value')
                                                    ->label('Size')
                                                    ->required(),
                                            ])
                                            ->createOptionUsing(fn (array $data): string => $data['value']),

                                        Forms\Components\Select::make('color')
                                            ->label('Color')
                                            ->options([
                                                'Gold' => 'Gold',
                                                'Silver' => 'Silver',
                                                'Rose Gold' => 'Rose Gold',
                                                'Platinum' => 'Platinum',
                                                'White Gold' => 'White Gold',
                                                'Black' => 'Black',
                                                'Blue' => 'Blue',
                                                'Red' => 'Red',
                                                'Green' => 'Green',
                                                'Clear' => 'Clear',
                                            ])
                                            ->searchable()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('value')
                                                    ->label('Color')
                                                    ->required(),
                                            ])
                                            ->createOptionUsing(fn (array $data): string => $data['value']),

                                        Forms\Components\Select::make('material')
                                            ->label('Material')
                                            ->options([
                                                'Gold' => 'Gold',
                                                'Silver' => 'Silver',
                                                'Platinum' => 'Platinum',
                                                'Stainless Steel' => 'Stainless Steel',
                                                'Titanium' => 'Titanium',
                                                'Leather' => 'Leather',
                                                'Diamond' => 'Diamond',
                                                'Pearl' => 'Pearl',
                                                'Gemstone' => 'Gemstone',
                                            ])
                                            ->searchable()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('value')
                                                    ->label('Material')
                                                    ->required(),
                                            ])
                                            ->createOptionUsing(fn (array $data): string => $data['value']),

                                        Forms\Components\TextInput::make('weight')
                                            ->label('Weight (grams)')
                                            ->numeric()
                                            ->step(0.01)
                                            ->minValue(0),

                                        Forms\Components\TextInput::make('quantity_in_stock')
                                            ->label('Stock Quantity')
                                            ->helperText('Leave empty to use default')
                                            ->numeric()
                                            ->minValue(0),

                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->helperText('Leave empty to use default')
                                            ->numeric()
                                            ->minValue(0),

                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->placeholder('Use default status')
                                            ->options([
                                                'in_stock' => 'In Stock',
                                                'low_stock' => 'Low Stock',
                                                'out_of_stock' => 'Out of Stock',
                                            ]),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notes')
                                            ->maxLength(65535)
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->columnSpan(2),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->maxItems(50)
                                    ->addActionLabel('+ Add Another Variant')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['variation_name'] ?? 'New Variant')
                                    ->cloneable(),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $successCount = 0;
                        $errors = [];

                        \DB::transaction(function () use ($data, &$successCount, &$errors) {
                            foreach ($data['variants'] as $index => $variantData) {
                                try {
                                    // Set base product
                                    $variantData['product_id'] = $data['base_product_id'];

                                    // Use defaults where not specified
                                    if (empty($variantData['quantity_in_stock'])) {
                                        $variantData['quantity_in_stock'] = $data['default_quantity'] ?? 0;
                                    }

                                    if (empty($variantData['reorder_level'])) {
                                        $variantData['reorder_level'] = $data['default_reorder_level'] ?? 10;
                                    }

                                    if (empty($variantData['status'])) {
                                        $variantData['status'] = $data['default_status'] ?? 'in_stock';
                                    }

                                    // Auto-generate SKU if enabled and no custom SKU provided
                                    if ($data['auto_generate_sku'] && empty($variantData['sku'])) {
                                        $variantData['auto_generate_sku'] = true;
                                    }

                                    // Remove empty values to avoid database constraints
                                    $variantData = array_filter($variantData, function($value) {
                                        return $value !== null && $value !== '';
                                    });

                                    ProductVariant::create($variantData);
                                    $successCount++;
                                } catch (\Exception $e) {
                                    $errors[] = "Variant " . ($index + 1) . " ({$variantData['variation_name']}): " . $e->getMessage();
                                }
                            }
                        });

                        if ($successCount > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title("🎉 Bulk Variant Creation Successful")
                                ->body("Successfully created {$successCount} variants!" . ($errors ? " " . count($errors) . " variants had errors." : ""))
                                ->success()
                                ->duration(10000)
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view_variants')
                                        ->label('View Variants')
                                        ->url(static::getUrl('index'))
                                        ->button(),
                                ])
                                ->send();
                        }

                        if ($errors) {
                            \Filament\Notifications\Notification::make()
                                ->title("⚠️ Some Variants Failed")
                                ->body("Errors: " . implode("; ", array_slice($errors, 0, 3)) . (count($errors) > 3 ? "..." : ""))
                                ->warning()
                                ->duration(15000)
                                ->send();
                        }
                    })
                    ->successNotification(null), // Disable default notification since we handle it manually
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'view' => Pages\ViewProductVariant::route('/{record}'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
