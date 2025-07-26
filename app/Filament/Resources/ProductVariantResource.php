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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
                                    ->required(),
                            ]),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ProductVariant::class, 'sku', ignoreRecord: true)
                            ->maxLength(255),

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

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (ProductVariant $record): string => $record->getStockStatusColor()),

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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('action')
                            ->label('Action')
                            ->options([
                                'add' => 'Add to Stock',
                                'subtract' => 'Subtract from Stock',
                                'set' => 'Set Stock Level',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->action(function (ProductVariant $record, array $data): void {
                        $record->adjustStock($data['quantity'], $data['action']);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('bulk_stock_update')
                        ->label('Update Stock')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('action')
                                ->label('Action')
                                ->options([
                                    'add' => 'Add to Stock',
                                    'subtract' => 'Subtract from Stock',
                                    'set' => 'Set Stock Level',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->adjustStock($data['quantity'], $data['action']);
                            }
                        }),

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
