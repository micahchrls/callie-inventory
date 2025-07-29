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
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Products';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Product Management';

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

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Stock Keeping Unit - unique identifier for this product'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product Categorization')
                    ->schema([
                        Forms\Components\Select::make('product_category_id')
                            ->label('Category')
                            ->options(ProductCategory::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('product_sub_category_id', null)),

                        Forms\Components\Select::make('product_sub_category_id')
                            ->label('Sub Category')
                            ->options(fn (Forms\Get $get): array =>
                                ProductSubCategory::where('product_category_id', $get('product_category_id'))
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Forms\Get $get): bool => !$get('product_category_id')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Product')
                            ->default(true)
                            ->helperText('Inactive products are hidden from operations'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Product Notes')
                            ->rows(3)
                            ->placeholder('Add any notes about this product...')
                            ->helperText('Special features, materials, care instructions, etc.'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Product Variations')
                    ->schema([
                        Forms\Components\Placeholder::make('variants_info')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->exists) {
                                    return 'No variations available. Product variations will appear here after the product is created.';
                                }

                                $variants = $record->variants()->get();
                                
                                if ($variants->isEmpty()) {
                                    return 'No variations found for this product.';
                                }

                                $content = '<div class="space-y-3">';
                                foreach ($variants as $variant) {
                                    $variationName = $variant->variation_name ?: 'Standard';
                                    if (!$variant->variation_name) {
                                        $attributes = array_filter([
                                            $variant->size,
                                            $variant->color,
                                            $variant->material,
                                            $variant->weight,
                                        ]);
                                        if (!empty($attributes)) {
                                            $variationName = implode(' | ', $attributes);
                                        }
                                    }

                                    $statusColor = match($variant->status) {
                                        'in_stock' => 'green',
                                        'low_stock' => 'orange',
                                        'out_of_stock' => 'red',
                                        'discontinued' => 'gray',
                                        default => 'blue'
                                    };

                                    $statusText = ucwords(str_replace('_', ' ', $variant->status));
                                    $activeIcon = $variant->is_active ? '✅' : '❌';

                                    $content .= '<div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">';
                                    $content .= '<div>';
                                    $content .= '<div class="font-medium">' . $variationName . '</div>';
                                    $content .= '<div class="text-sm text-gray-600">SKU: ' . ($variant->sku ?: 'N/A') . '</div>';
                                    $content .= '</div>';
                                    $content .= '<div class="text-right">';
                                    $content .= '<div class="font-medium">Stock: ' . number_format($variant->quantity_in_stock) . '</div>';
                                    $content .= '<div class="text-sm">';
                                    $content .= '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-' . $statusColor . '-100 text-' . $statusColor . '-800">' . $statusText . '</span>';
                                    $content .= ' ' . $activeIcon;
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                }
                                $content .= '</div>';

                                return new \Illuminate\Support\HtmlString($content);
                            })
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

                Tables\Columns\TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Total Stock')
                    ->state(fn ($record) => $record->getTotalStock())
                    ->sortable(false)
                    ->badge()
                    ->color(fn ($record): string => $record->getProductStatusColor()),

                Tables\Columns\TextColumn::make('product_status')
                    ->label('Status')
                    ->state(fn ($record) => $record->getProductStatusText())
                    ->badge()
                    ->color(fn ($record): string => $record->getProductStatusColor()),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->state(fn ($record) => '$' . number_format($record->getTotalValue(), 2))
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                Tables\Actions\Action::make('manage_inventory')
                    ->label('Manage Stock')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('info')
                    ->url(fn (Product $record): string => InventoryResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('manage_variants')
                    ->label('Manage Variants')
                    ->icon('heroicon-o-cube')
                    ->color('success')
                    ->url(fn (Product $record): string => ProductVariantResource::getUrl('index', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Products Found')
            ->emptyStateDescription('Start by creating your first product.')
            ->emptyStateIcon('heroicon-o-cube');
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
