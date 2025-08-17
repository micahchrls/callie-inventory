<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Product\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductVariantRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Product Variants';

    protected static ?string $recordTitleAttribute = 'sku';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Variant Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('size')
                                    ->label('Size')
                                    ->placeholder('e.g., Small, 7, 18"')
                                    ->maxLength(50)
                                    ->live(),

                                Forms\Components\TextInput::make('color')
                                    ->label('Color')
                                    ->placeholder('e.g., Gold, Silver, Red')
                                    ->maxLength(50)
                                    ->live(),

                                Forms\Components\TextInput::make('material')
                                    ->label('Material')
                                    ->placeholder('e.g., 14K Gold, Sterling Silver')
                                    ->maxLength(100)
                                    ->live(),

                                Forms\Components\TextInput::make('variant_initial')
                                    ->label('Variant Initial')
                                    ->maxLength(50)
                                    ->live(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true)
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, ?Model $record, Forms\Get $get, Forms\Set $set) {
                                        if (!$record || !$record->sku) {
                                            $this->generateVariantSku($get, $set);
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('generateSku')
                                        ->label('Generate SKU')
                                        ->color('primary')
                                        ->icon('heroicon-s-sparkles')
                                        ->size('sm')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $this->generateVariantSku($get, $set);
                                        })
                                ])
                                    ->columnSpan(1)
                                    ->label(' ')

                                    ->extraAttributes(['style' => 'margin-top: 2rem;']),
                            ]),
                    ]),

                Forms\Components\Section::make('Stock Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity_in_stock')
                                    ->label('Quantity in Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->minValue(0),

                                Forms\Components\TextInput::make('reorder_level')
                                    ->label('Reorder Level')
                                    ->numeric()
                                    ->default(10)
                                    ->required()
                                    ->minValue(0)
                                    ->helperText('Alert when stock falls below this level'),
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

                    ]),

                Forms\Components\Section::make('Additional Attributes')
                    ->schema([
                        Forms\Components\KeyValue::make('additional_attributes')
                            ->label('Custom Attributes')
                            ->keyLabel('Attribute Name')
                            ->valueLabel('Attribute Value')
                            ->addActionLabel('Add Attribute')
                            ->reorderable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy'),

                Tables\Columns\TextColumn::make('size')
                    ->label('Size')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('color')
                    ->label('Color')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('material')
                    ->label('Material')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('variant_initial')
                    ->label('Initial')
                    ->default('-'),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn(int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'in_stock',
                        'warning' => 'low_stock',
                        'danger' => 'out_of_stock',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        default => ucfirst($state),
                    }),

                Tables\Columns\IconColumn::make('is_discontinued')
                    ->label('Discontinued')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                    ]),

                Tables\Filters\TernaryFilter::make('is_discontinued')
                    ->label('Discontinued')
                    ->placeholder('All variants')
                    ->trueLabel('Discontinued only')
                    ->falseLabel('Active only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Variant')
                    ->modalHeading('Create New Product Variant')
                    ->modalWidth('xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        // SKU will be generated in the creating event
                        return $data;
                    })
                    ->after(function ($record) {
                        // Update product's base_sku if not set
                        $product = $record->product;
                        if (!$product->base_sku) {
                            $product->base_sku = $this->generateBaseSku($product->name);
                            $product->save();
                        }

                        // Generate final SKU for the variant
                        $record->sku = $this->generateFinalSku($product, $record);
                        $record->save();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Product Variant')
                    ->modalWidth('xl'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create First Variant')
                    ->modalHeading('Create New Product Variant')
                    ->modalWidth('xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        return $data;
                    })
                    ->after(function ($record) {
                        // Update product's base_sku if not set
                        $product = $record->product;
                        if (!$product->base_sku) {
                            $product->base_sku = $this->generateBaseSku($product->name);
                            $product->save();
                        }

                        // Generate final SKU for the variant
                        $record->sku = $this->generateFinalSku($product, $record);
                        $record->save();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Generate variant SKU in the form for preview
     */
    protected function generateVariantSku(Forms\Get $get, Forms\Set $set): void
    {
        $product = $this->ownerRecord;

        if (!$product || !$product->name) {
            return;
        }

        // Generate base SKU if not exists
        $baseSku = $product->base_sku ?: $this->generateBaseSku($product->name);

        // Generate variant code
        $variantCode = $this->generateVariantCode(
            $get('size'),
            $get('color'),
            $get('material'),
            $get('variant_initial')
        );

        if ($variantCode) {
            // Get next increment number
            $latestVariant = ProductVariant::where('product_id', $product->id)
                ->where('sku', 'like', $baseSku . '-' . $variantCode . '-%')
                ->orderBy('sku', 'desc')
                ->first();

            $incrementNumber = 1;
            if ($latestVariant && preg_match('/-(\d{4})$/', $latestVariant->sku, $matches)) {
                $incrementNumber = intval($matches[1]) + 1;
            }

            $sku = sprintf('%s-%s-%04d', $baseSku, $variantCode, $incrementNumber);
        } else {
            // No variant attributes, use base SKU only
            $sku = $baseSku;
        }

        $set('sku', $sku);
    }

    /**
     * Generate base SKU from product name
     */
    protected function generateBaseSku(string $productName): string
    {
        $words = explode(' ', $productName);
        $sku = '';

        foreach ($words as $word) {
            $cleanWord = trim($word);
            if (!empty($cleanWord)) {
                $sku .= strtoupper(substr($cleanWord, 0, 1));
            }
        }

        return $sku;
    }

    /**
     * Generate variant code from attributes
     */
    protected function generateVariantCode(?string $size, ?string $color, ?string $material, ?string $variantInitial): string
    {
        $code = '';

        // Size - take as-is
        if (!empty($size)) {
            $code .= $size;
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

    /**
     * Generate final SKU for a variant
     */
    protected function generateFinalSku(Model $product, ProductVariant $variant): string
    {
        // Generate base SKU if not exists
        $baseSku = $product->base_sku ?: $this->generateBaseSku($product->name);

        // Generate variant code
        $variantCode = $this->generateVariantCode(
            $variant->size,
            $variant->color,
            $variant->material,
            $variant->variant_initial
        );

        if ($variantCode) {
            // Get next increment number
            $latestVariant = ProductVariant::where('product_id', $product->id)
                ->where('id', '!=', $variant->id)
                ->where('sku', 'like', $baseSku . '-' . $variantCode . '-%')
                ->orderBy('sku', 'desc')
                ->first();

            $incrementNumber = 1;
            if ($latestVariant && preg_match('/-(\d{4})$/', $latestVariant->sku, $matches)) {
                $incrementNumber = intval($matches[1]) + 1;
            }

            return sprintf('%s-%s-%04d', $baseSku, $variantCode, $incrementNumber);
        }

        // No variant attributes, use base SKU only
        return $baseSku;
    }
}
