<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Product\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class InventoryResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?string $modelLabel = 'Inventory Item';

    protected static ?string $pluralModelLabel = 'Inventory';

    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('product_name')
                                    ->label('Product Name')
                                    ->content(fn($record) => $record && $record->product ? $record->product->name : 'N/A')
                                    ->extraAttributes(['class' => 'text-lg font-semibold']),

                                Forms\Components\Placeholder::make('sku')
                                    ->label('SKU')
                                    ->content(fn($record) => $record ? $record->sku : 'N/A')
                                    ->extraAttributes(['class' => 'font-mono']),

                                Forms\Components\Placeholder::make('category')
                                    ->label('Category')
                                    ->content(function ($record) {
                                        if (!$record || !$record->product || !$record->product->productCategory) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<span class="text-gray-500 dark:text-gray-400">No category assigned</span>'
                                            );
                                        }

                                        return new \Illuminate\Support\HtmlString(
                                            '<span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' .
                                            'bg-primary-50 text-primary-700 ring-primary-600/20 ' .
                                            'dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">' .
                                            htmlspecialchars($record->product->productCategory->name) .
                                            '</span>'
                                        );
                                    }),

                                Forms\Components\Placeholder::make('subcategory')
                                    ->label('Sub Category')
                                    ->content(function ($record) {
                                        if (!$record || !$record->product || !$record->product->productSubCategory) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<span class="text-gray-500 dark:text-gray-400">No subcategory assigned</span>'
                                            );
                                        }

                                        return new \Illuminate\Support\HtmlString(
                                            '<span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' .
                                            'bg-success-50 text-success-700 ring-success-600/20 ' .
                                            'dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">' .
                                            htmlspecialchars($record->product->productSubCategory->name) .
                                            '</span>'
                                        );
                                    }),
                            ]),

                        Forms\Components\Placeholder::make('description')
                            ->label('Description')
                            ->content(fn($record) => $record && $record->product && $record->product->description ? $record->product->description : 'No description available')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stock Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quantity_in_stock')
                                    ->label('Current Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1)
                                    ->suffixIcon('heroicon-m-cube')
                                    ->extraAttributes(['class' => 'text-xl font-bold'])
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
                                    ->suffixIcon('heroicon-m-exclamation-triangle')
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

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'in_stock' => 'In Stock',
                                        'low_stock' => 'Low Stock',
                                        'out_of_stock' => 'Out of Stock',
                                        'discontinued' => 'Discontinued',
                                    ])
                                    ->default('in_stock')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-m-signal'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('location')
                                    ->label('Storage Location')
                                    ->content('Not set')
                                    ->extraAttributes(['class' => 'text-gray-500']),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->default(true)
                                    ->helperText('Inactive items are hidden from operations'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Restock History')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('last_restocked_at')
                                    ->label('Last Restocked')
                                    ->displayFormat('M d, Y H:i')
                                    ->prefixIcon('heroicon-m-clock')
                                    ->placeholder('Never restocked'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Last Updated')
                                    ->content(fn($record) => $record && $record->updated_at ? $record->updated_at->format('M d, Y H:i') : 'N/A'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Variant Details')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Placeholder::make('variation_name_display')
                                    ->label('Variant Name')
                                    ->content(fn($record) => $record && $record->variation_name ? $record->variation_name : 'Standard variant'),

                                Forms\Components\Placeholder::make('size_display')
                                    ->label('Size')
                                    ->content(fn($record) => $record && $record->size ? $record->size : 'N/A'),

                                Forms\Components\Placeholder::make('color_display')
                                    ->label('Color')
                                    ->content(fn($record) => $record && $record->color ? $record->color : 'N/A'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('material_display')
                                    ->label('Material')
                                    ->content(fn($record) => $record && $record->material ? $record->material : 'N/A'),

                                Forms\Components\Placeholder::make('weight_display')
                                    ->label('Weight')
                                    ->content(fn($record) => $record && $record->weight ? $record->weight : 'N/A'),

                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Created')
                                    ->content(fn($record) => $record && $record->created_at ? $record->created_at->format('M d, Y') : 'N/A'),
                            ]),

                        Forms\Components\KeyValue::make('additional_attributes')
                            ->label('Additional Attributes')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->visible(fn($record) => $record && !empty($record->additional_attributes))
                            ->addable(false)
                            ->deletable(false)
                            ->editableKeys(false)
                            ->editableValues(false),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Inventory Notes')
                            ->rows(3)
                            ->placeholder('Add any notes about this inventory item...')
                            ->helperText('Special handling instructions, supplier info, etc.')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Stock Movement History')
                    ->schema([
                        Forms\Components\Placeholder::make('stock_movements')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->exists) {
                                    return 'Stock movement history will appear here after the item is created.';
                                }

                                $movements = $record->stockMovements()
                                    ->with('user')
                                    ->orderBy('created_at', 'desc')
                                    ->limit(10)
                                    ->get();

                                if ($movements->isEmpty()) {
                                    return 'No stock movements recorded yet.';
                                }

                                $content = '<div class="space-y-3">';
                                foreach ($movements as $movement) {
                                    $changeColor = $movement->quantity_change > 0 ? 'green' : 'red';
                                    $changeIcon = $movement->quantity_change > 0 ? '↗️' : '↘️';
                                    $userName = $movement->user ? $movement->user->name : 'System';

                                    $content .= '<div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">';
                                    $content .= '<div>';
                                    $content .= '<div class="font-medium">' . $movement->movement_type_display . ' ' . $changeIcon . '</div>';
                                    $content .= '<div class="text-sm text-gray-600">' . $movement->created_at->format('M d, Y H:i') . ' by ' . $userName . '</div>';
                                    if ($movement->reason) {
                                        $content .= '<div class="text-sm text-gray-500">' . $movement->reason . '</div>';
                                    }
                                    $content .= '</div>';
                                    $content .= '<div class="text-right">';
                                    $content .= '<div class="font-medium text-' . $changeColor . '-600">';
                                    $content .= ($movement->quantity_change > 0 ? '+' : '') . number_format($movement->quantity_change);
                                    $content .= '</div>';
                                    $content .= '<div class="text-sm text-gray-500">';
                                    $content .= number_format($movement->quantity_before) . ' → ' . number_format($movement->quantity_after);
                                    $content .= '</div>';
                                    $content .= '</div>';
                                    $content .= '</div>';
                                }
                                $content .= '</div>';

                                if ($movements->count() === 10) {
                                    $content .= '<div class="text-center mt-3">';
                                    $content .= '<a href="/admin/stock-movements?tableFilters[product_variant_id][value]=' . $record->id . '" class="text-blue-600 hover:text-blue-800 text-sm">';
                                    $content .= 'View all stock movements →';
                                    $content .= '</a>';
                                    $content .= '</div>';
                                }

                                return new \Illuminate\Support\HtmlString($content);
                            }),
                    ])
                    ->visible(fn($record) => $record && $record->exists)
                    ->collapsible(),
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

                        return !empty($attributes) ? implode(' | ', $attributes) : 'Standard';
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
                    ->color(fn($record) => match (true) {
                        $record->quantity_in_stock <= 0 => 'danger',
                        $record->quantity_in_stock <= $record->reorder_level => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn($state) => number_format($state)),

                // Reorder Level Column (Display Only)
                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder At')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => number_format($state))
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
                    ->color(fn(string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        'discontinued' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

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
                // Product Classification Filters
                Tables\Filters\SelectFilter::make('product.product_category_id')
                    ->label('Category')
                    ->relationship('product.productCategory', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->native(false),

                Tables\Filters\SelectFilter::make('product.product_sub_category_id')
                    ->label('Sub-Category')
                    ->relationship('product.productSubCategory', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        'discontinued' => 'Discontinued',
                    ])
                    ->multiple()
                    ->native(false),

                // Product Attributes Filters
                Tables\Filters\SelectFilter::make('material')
                    ->label('Material')
                    ->options(function () {
                        return ProductVariant::query()
                            ->whereNotNull('material')
                            ->where('material', '!=', '')
                            ->distinct()
                            ->pluck('material', 'material')
                            ->sort()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->native(false)
                    ->visible(fn() => ProductVariant::whereNotNull('material')->where('material', '!=', '')->exists()),

                Tables\Filters\SelectFilter::make('color')
                    ->label('Color')
                    ->options(function () {
                        return ProductVariant::query()
                            ->whereNotNull('color')
                            ->where('color', '!=', '')
                            ->distinct()
                            ->pluck('color', 'color')
                            ->sort()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->native(false)
                    ->visible(fn() => ProductVariant::whereNotNull('color')->where('color', '!=', '')->exists()),

                Tables\Filters\SelectFilter::make('size')
                    ->label('Size')
                    ->options(function () {
                        return ProductVariant::query()
                            ->whereNotNull('size')
                            ->where('size', '!=', '')
                            ->distinct()
                            ->pluck('size', 'size')
                            ->sort()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->native(false)
                    ->visible(fn() => ProductVariant::whereNotNull('size')->where('size', '!=', '')->exists()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active items only')
                    ->falseLabel('Inactive items only')
                    ->placeholder('All items')
                    ->native(false),

                // Date Filters
                Tables\Filters\Filter::make('last_restocked')
                    ->label('Last Restocked')
                    ->form([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                            'lg' => 2,
                        ])
                            ->schema([
                                Forms\Components\DatePicker::make('restocked_from')
                                    ->label('From Date')
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->placeholder('Select start date')
                                    ->suffixIcon('heroicon-m-calendar-days')
                                    ->extraAttributes([
                                        'class' => 'text-base',
                                    ])
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                    ]),
                                Forms\Components\DatePicker::make('restocked_until')
                                    ->label('To Date')
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->placeholder('Select end date')
                                    ->suffixIcon('heroicon-m-calendar-days')
                                    ->extraAttributes([
                                        'class' => 'text-base',
                                    ])
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(2),

                Tables\Filters\Filter::make('updated_date')
                    ->label('Updated Date')
                    ->form([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                            'lg' => 2,
                        ])
                            ->schema([
                                Forms\Components\DatePicker::make('updated_from')
                                    ->label('From Date')
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->placeholder('Select start date')
                                    ->suffixIcon('heroicon-m-calendar-days')
                                    ->extraAttributes([
                                        'class' => 'text-base',
                                    ])
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                    ]),
                                Forms\Components\DatePicker::make('updated_until')
                                    ->label('To Date')
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->placeholder('Select end date')
                                    ->suffixIcon('heroicon-m-calendar-days')
                                    ->extraAttributes([
                                        'class' => 'text-base',
                                    ])
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(2),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->label('Filters')
                    ->icon('heroicon-o-funnel')
                    ->button()
                    ->outlined()
            )
            ->actions([
                Tables\Actions\Action::make('stock_in')
                    ->label('Stock In')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('success')
                    ->modalHeading(fn($record) => $record->product->name)
                    ->form([
                        Forms\Components\Section::make('Stock In Details')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Quantity In Stock')
                                            ->disabled()
                                            ->default(fn($record) => $record->quantity_in_stock),

                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->disabled()
                                            ->default(fn($record) => $record->reorder_level),
                                    ]),
                            ]),

                        Forms\Components\Section::make('Stock In Form')
                            ->schema([
                                Forms\Components\Repeater::make('stock_in_items')
                                    ->label('Stock In Items')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([

                                                Forms\Components\TextInput::make('quantity_in')
                                                    ->label('Quantity In')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                Forms\Components\Select::make('reason')
                                                    ->label('Reason for Stock In')
                                                    ->options([
                                                        'returned' => 'Returned to Supplier',
                                                        'return_to_callie' => 'Return to Callie',
                                                        'restock' => 'Restock',
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

                                                Forms\Components\Textarea::make('notes')
                                                    ->label('Notes')
                                                    ->rows(2),
                                            ])
                                    ])
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, ProductVariant $record): void {
                        try {
                            $results = $record->stockIn($data['stock_in_items']); // stock_in_items from the repeater

                            foreach ($results as $result) {
                                Notification::make()
                                    ->title('Stock In Successful')
                                    ->body("Added {$result['quantity_in']} units to {$record->product->name} (Reason: {$result['reason']})")
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {

                            Notification::make()
                                ->title('Stock In Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->slideOver()
                    ->modalWidth(MaxWidth::ThreeExtraLarge),

                // STOCK OUT
                Tables\Actions\Action::make('stock_out')
                    ->label('Stock Out')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('danger')
                    ->modalHeading(fn($record) => $record->product->name)
                    ->form([
                        Forms\Components\Section::make('Stock Out Details')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Quantity In Stock')
                                            ->disabled()
                                            ->default(fn($record) => $record->quantity_in_stock),

                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->disabled()
                                            ->default(fn($record) => $record->reorder_level),
                                    ]),
                            ]),

                        Forms\Components\Section::make('Stock Out Form')
                            ->schema([
                                Forms\Components\Repeater::make('stock_out_items')
                                    ->label('Stock Out Items')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('platform')
                                                    ->label('Platform')
                                                    ->options([
                                                        'shopee' => 'Shopee',
                                                        'tiktok' => 'TikTok',
                                                        'bazar' => 'Bazar',
                                                        'others' => 'Other',
                                                    ])
                                                    ->required(),

                                                Forms\Components\TextInput::make('quantity_out')
                                                    ->label('Quantity Out')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),

                                                Forms\Components\Select::make('reason')
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

                                                Forms\Components\Textarea::make('notes')
                                                    ->label('Notes')
                                                    ->rows(2),
                                            ])
                                    ])
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (array $data, ProductVariant $record): void {
                        try {
                            $results = $record->stockOut($data['stock_out_items']); // stock_out_items is from the repeater

                            foreach ($results as $result) {
                                Notification::make()
                                    ->title('Stock Out Successful')
                                    ->body("Removed {$result['quantity_out']} units from {$record->product->name} (Platform: {$result['platform']})")
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Stock Out Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->slideOver()
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->visible(fn($record) => $record->quantity_in_stock > 0),

                // Removed manual edit action as requested

                Tables\Actions\ViewAction::make()
                    ->label('View Details'),
            ], Tables\Enums\ActionsPosition::BeforeColumns)
            ->headerActions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make('export_inventory')
                        ->label('Export Inventory')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('inventory-export-' . date('Y-m-d-H-i'))
                                ->withColumns([
                                    Column::make('sku')->heading('SKU'),
                                    Column::make('product.name')->heading('Product Name'),
                                    Column::make('variation_name')->heading('Variation Name')
                                        ->formatStateUsing(function ($record) {
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

                                            return !empty($attributes) ? implode(' - ', $attributes) : 'Standard';
                                        }),
                                    Column::make('product.productCategory.name')->heading('Category'),
                                    Column::make('product.productSubCategory.name')->heading('Sub Category')
                                        ->formatStateUsing(function ($record) {
                                            return $record->product->productSubCategory ? $record->product->productSubCategory->name : '-';
                                        }),
                                    Column::make('quantity_in_stock')->heading('Current Stock'),
                                    Column::make('reorder_level')->heading('Reorder Level'),
                                    Column::make('status')->heading('Status')
                                        ->formatStateUsing(function ($state) {
                                            return ucwords(str_replace('_', ' ', $state));
                                        }),
                                    Column::make('is_active')->heading('Active')
                                        ->formatStateUsing(function ($state) {
                                            return $state ? 'Yes' : 'No';
                                        }),
                                    Column::make('last_restocked_at')->heading('Last Restocked')
                                        ->formatStateUsing(function ($state) {
                                            return $state ? $state->format('M d, Y') : 'Never';
                                        }),
                                    Column::make('notes')->heading('Notes')
                                        ->formatStateUsing(function ($state) {
                                            return $state ?: '-';
                                        }),
                                ]),
                        ]),

                    BulkAction::make('bulk_restock')
                        ->label('🔄 Bulk Restock')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('action')
                                ->label('Stock Action')
                                ->options([
                                    'set' => '📝 Set stock to specific amount',
                                    'add' => '➕ Add to current stock',
                                    'subtract' => '➖ Remove from current stock',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),

                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(1),

                            Forms\Components\Checkbox::make('update_restock_date')
                                ->label('Update last restocked date to now')
                                ->default(true),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            foreach ($records as $record) {
                                $record->adjustStock($data['quantity'], $data['action'], 'Bulk stock update');
                            }

                            if ($data['update_restock_date']) {
                                $records->each->update(['last_restocked_at' => now()]);
                            }

                            Notification::make()
                                ->title('✅ Stock Updated')
                                ->body("Stock updated for {$records->count()} items")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_update_status')
                        ->label('🏷️ Update Status')
                        ->icon('heroicon-o-tag')
                        ->color('warning')
                        ->form([
                            Forms\Components\Section::make('Status Update Configuration')
                                ->description('Update the status and activity state for selected inventory items')
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('New Status')
                                        ->options([
                                            'in_stock' => '✅ In Stock - Items are available for sale',
                                            'low_stock' => '⚠️ Low Stock - Running low, needs restocking',
                                            'out_of_stock' => '❌ Out of Stock - Currently unavailable',
                                            'discontinued' => '🚫 Discontinued - No longer sold',
                                        ])
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->helperText('This will update the inventory status for all selected items'),

                                    Forms\Components\Toggle::make('update_active_status')
                                        ->label('Also update active status')
                                        ->helperText('Enable this to also change whether items are active or inactive')
                                        ->live()
                                        ->default(false),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Set as Active')
                                        ->helperText('Active items are visible and available for operations')
                                        ->visible(fn(Forms\Get $get) => $get('update_active_status'))
                                        ->default(function (Forms\Get $get) {
                                            return $get('status') !== 'discontinued';
                                        }),

                                    Forms\Components\Textarea::make('reason')
                                        ->label('Reason for status change (optional)')
                                        ->placeholder('e.g., Inventory audit completed, Seasonal discontinuation, Stock refresh...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $updates = ['status' => $data['status']];
                            $updatedCount = 0;

                            if ($data['update_active_status']) {
                                $updates['is_active'] = $data['is_active'];
                            }

                            foreach ($records as $record) {
                                $record->update($updates);
                                $updatedCount++;
                            }

                            $statusText = match ($data['status']) {
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock',
                                'out_of_stock' => 'Out of Stock',
                                'discontinued' => 'Discontinued',
                            };

                            $message = "Status updated to '{$statusText}' for {$updatedCount} items";
                            if ($data['update_active_status']) {
                                $activeText = $data['is_active'] ? 'active' : 'inactive';
                                $message .= " and set as {$activeText}";
                            }

                            Notification::make()
                                ->title('🏷️ Status Updated Successfully')
                                ->body($message)
                                ->success()
                                ->persistent()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Update Status')
                        ->modalDescription('This will update the status for all selected inventory items. This action cannot be undone.')
                        ->modalSubmitActionLabel('Update Status'),

                    BulkAction::make('bulk_update_reorder_levels')
                        ->label('🎯 Update Reorder Levels')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Section::make('Reorder Level Configuration')
                                ->description('Set automatic reorder thresholds for selected inventory items')
                                ->schema([
                                    Forms\Components\Select::make('reorder_action')
                                        ->label('Reorder Level Action')
                                        ->options([
                                            'set' => '📝 Set to specific amount',
                                            'increase' => '➕ Increase by amount',
                                            'decrease' => '➖ Decrease by amount',
                                            'percentage_of_stock' => '📊 Set as percentage of current stock',
                                        ])
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                                            $set('value', null);
                                        }),

                                    Forms\Components\TextInput::make('value')
                                        ->label(function (Forms\Get $get) {
                                            return match ($get('reorder_action')) {
                                                'set' => 'Set reorder level to',
                                                'increase' => 'Increase reorder level by',
                                                'decrease' => 'Decrease reorder level by',
                                                'percentage_of_stock' => 'Percentage of current stock',
                                                default => 'Value'
                                            };
                                        })
                                        ->helperText(function (Forms\Get $get) {
                                            return match ($get('reorder_action')) {
                                                'set' => 'Each item will have this exact reorder level',
                                                'increase' => 'This amount will be added to current reorder levels',
                                                'decrease' => 'This amount will be subtracted from current reorder levels',
                                                'percentage_of_stock' => 'Reorder level = current stock × (percentage ÷ 100)',
                                                default => 'Enter the value for the reorder level operation'
                                            };
                                        })
                                        ->numeric()
                                        ->required()
                                        ->minValue(function (Forms\Get $get) {
                                            return $get('reorder_action') === 'percentage_of_stock' ? 1 : 0;
                                        })
                                        ->maxValue(function (Forms\Get $get) {
                                            return $get('reorder_action') === 'percentage_of_stock' ? 100 : null;
                                        })
                                        ->step(function (Forms\Get $get) {
                                            return $get('reorder_action') === 'percentage_of_stock' ? 0.1 : 1;
                                        })
                                        ->suffix(function (Forms\Get $get) {
                                            return $get('reorder_action') === 'percentage_of_stock' ? '%' : 'units';
                                        })
                                        ->placeholder(function (Forms\Get $get) {
                                            return match ($get('reorder_action')) {
                                                'set' => 'e.g., 10',
                                                'increase' => 'e.g., 5',
                                                'decrease' => 'e.g., 3',
                                                'percentage_of_stock' => 'e.g., 20.5',
                                                default => 'Enter value'
                                            };
                                        }),

                                    Forms\Components\Textarea::make('reason')
                                        ->label('Reason for change (optional)')
                                        ->placeholder('e.g., Seasonal demand change, Supply chain update, Business policy change...')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $updatedCount = 0;
                            $action = $data['reorder_action'];
                            $value = $data['value'];

                            foreach ($records as $record) {
                                $newReorderLevel = match ($action) {
                                    'set' => max(0, (int)$value),
                                    'increase' => max(0, $record->reorder_level + (int)$value),
                                    'decrease' => max(0, $record->reorder_level - (int)$value),
                                    'percentage_of_stock' => max(0, (int)ceil($record->quantity_in_stock * ($value / 100))),
                                };

                                $record->update(['reorder_level' => $newReorderLevel]);
                                $updatedCount++;
                            }

                            $actionText = match ($action) {
                                'set' => "set to {$value} units",
                                'increase' => "increased by {$value} units",
                                'decrease' => "decreased by {$value} units",
                                'percentage_of_stock' => "set to {$value}% of current stock",
                            };

                            Notification::make()
                                ->title('🎯 Reorder Levels Updated Successfully')
                                ->body("Reorder levels {$actionText} for {$updatedCount} items")
                                ->success()
                                ->persistent()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Update Reorder Levels')
                        ->modalDescription('This will update reorder levels for all selected items. Items will trigger low stock alerts when their quantity drops to or below this level.')
                        ->modalSubmitActionLabel('Update Reorder Levels'),

                    BulkAction::make('bulk_update_attributes')
                        ->label('🏷️ Update Attributes')
                        ->icon('heroicon-o-tag')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('attribute_field')
                                ->label('Attribute to Update')
                                ->options([
                                    'size' => 'Size',
                                    'color' => 'Color',
                                    'material' => 'Material',
                                    'weight' => 'Weight',
                                    'variation_name' => 'Variation Name',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),

                            Forms\Components\TextInput::make('attribute_value')
                                ->label(function (Forms\Get $get) {
                                    return match ($get('attribute_field')) {
                                        'size' => 'New Size',
                                        'color' => 'New Color',
                                        'material' => 'New Material',
                                        'weight' => 'New Weight',
                                        'variation_name' => 'New Variation Name',
                                        default => 'New Value',
                                    };
                                })
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            foreach ($records as $record) {
                                $record->update([
                                    $data['attribute_field'] => $data['attribute_value'],
                                ]);
                            }

                            $fieldName = match ($data['attribute_field']) {
                                'size' => 'Size',
                                'color' => 'Color',
                                'material' => 'Material',
                                'weight' => 'Weight',
                                'variation_name' => 'Variation Name',
                                default => 'Attribute',
                            };

                            Notification::make()
                                ->title('🏷️ Attributes Updated')
                                ->body("{$fieldName} updated to '{$data['attribute_value']}' for {$records->count()} items")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_update_notes')
                        ->label('📝 Update Notes')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('notes_action')
                                ->label('Notes Action')
                                ->options([
                                    'replace' => '📝 Replace existing notes',
                                    'append' => '➕ Append to existing notes',
                                    'prepend' => '⬆️ Prepend to existing notes',
                                    'clear' => '🗑️ Clear all notes',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),

                            Forms\Components\Textarea::make('notes_text')
                                ->label('Notes Text')
                                ->rows(3)
                                ->maxLength(500)
                                ->visible(fn(Forms\Get $get) => $get('notes_action') !== 'clear'),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            foreach ($records as $record) {
                                $currentNotes = $record->notes ?? '';

                                $newNotes = match ($data['notes_action']) {
                                    'replace' => $data['notes_text'] ?? '',
                                    'append' => $currentNotes . ($currentNotes ? "\n" : '') . ($data['notes_text'] ?? ''),
                                    'prepend' => ($data['notes_text'] ?? '') . ($currentNotes ? "\n" : '') . $currentNotes,
                                    'clear' => '',
                                    default => $currentNotes,
                                };

                                $record->update(['notes' => $newNotes]);
                            }

                            $actionText = match ($data['notes_action']) {
                                'replace' => 'replaced',
                                'append' => 'appended to',
                                'prepend' => 'prepended to',
                                'clear' => 'cleared from',
                                default => 'updated for',
                            };

                            Notification::make()
                                ->title('📝 Notes Updated')
                                ->body("Notes {$actionText} {$records->count()} items")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('quantity_in_stock', 'asc')
            ->deferLoading();
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
            'index' => Pages\ListInventory::route('/'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
            'view' => Pages\ViewInventory::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return ProductVariant::where('quantity_in_stock', '<=', 0)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return ProductVariant::where('quantity_in_stock', '<=', 0)->count() > 0 ? 'danger' : null;
    }
}
