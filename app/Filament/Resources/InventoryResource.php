<?php

namespace App\Filament\Resources;

use App\Filament\Exports\InventoryExporter;
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
use Illuminate\Support\HtmlString;
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
                Forms\Components\Grid::make(2)
                    ->schema([
                        // Left Column: Combined Product Information & Variant Details
                        Forms\Components\Section::make('Product Information & Variants')
                            ->description('Detailed information about the product and its variations')
                            ->schema([
                                // Product Information
                                Forms\Components\Fieldset::make('Product Details')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Placeholder::make('product_name')
                                                    ->label('Product Name')
                                                    ->content(fn ($record) => $record && $record->product ? $record->product->name : 'N/A')
                                                    ->extraAttributes(['class' => 'text-lg font-semibold']),

                                                Forms\Components\Placeholder::make('sku')
                                                    ->label('SKU')
                                                    ->content(fn ($record) => $record ? $record->sku : 'N/A')
                                                    ->extraAttributes(['class' => 'font-mono']),

                                                Forms\Components\Placeholder::make('category')
                                                    ->label('Category')
                                                    ->content(function ($record) {
                                                        if (! $record || ! $record->product || ! $record->product->productCategory) {
                                                            return new HtmlString(
                                                                '<span class="text-gray-500 dark:text-gray-400">No category assigned</span>'
                                                            );
                                                        }

                                                        return new HtmlString(
                                                            '<span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset '.
                                                            'bg-primary-50 text-primary-700 ring-primary-600/20 '.
                                                            'dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30">'.
                                                            htmlspecialchars($record->product->productCategory->name).
                                                            '</span>'
                                                        );
                                                    }),

                                                Forms\Components\Placeholder::make('subcategory')
                                                    ->label('Sub Category')
                                                    ->content(function ($record) {
                                                        if (! $record || ! $record->product || ! $record->product->productSubCategory) {
                                                            return new HtmlString(
                                                                '<span class="text-gray-500 dark:text-gray-400">No subcategory assigned</span>'
                                                            );
                                                        }

                                                        return new HtmlString(
                                                            '<span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset '.
                                                            'bg-success-50 text-success-700 ring-success-600/20 '.
                                                            'dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">'.
                                                            htmlspecialchars($record->product->productSubCategory->name).
                                                            '</span>'
                                                        );
                                                    }),
                                            ]),

                                        Forms\Components\Placeholder::make('description')
                                            ->label('Description')
                                            ->content(fn ($record) => $record && $record->product && $record->product->description ? $record->product->description : 'No description available')
                                            ->columnSpanFull(),
                                    ]),

                                // Variant Information
                                Forms\Components\Fieldset::make('Variant Details')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Placeholder::make('variation_name_display')
                                                    ->label('Variant Name')
                                                    ->content(function ($record) {
                                                        $variantName = $record && $record->variation_name ? $record->variation_name : 'Standard variant';

                                                        return new HtmlString(
                                                            '<span class="text-base font-semibold text-gray-900 dark:text-gray-100">'.
                                                            htmlspecialchars($variantName).
                                                            '</span>'
                                                        );
                                                    }),

                                                Forms\Components\Placeholder::make('size_display')
                                                    ->label('Size')
                                                    ->content(function ($record) {
                                                        $size = $record && $record->size ? $record->size : 'N/A';
                                                        $isAvailable = $record && $record->size;

                                                        return new HtmlString(
                                                            '<span class="'.($isAvailable ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'text-sm text-gray-500 dark:text-gray-400').'">'.
                                                            htmlspecialchars($size).
                                                            '</span>'
                                                        );
                                                    }),

                                                Forms\Components\Placeholder::make('color_display')
                                                    ->label('Color')
                                                    ->content(function ($record) {
                                                        $color = $record && $record->color ? $record->color : 'N/A';
                                                        $isAvailable = $record && $record->color;

                                                        return new HtmlString(
                                                            '<span class="'.($isAvailable ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'text-sm text-gray-500 dark:text-gray-400').'">'.
                                                            htmlspecialchars($color).
                                                            '</span>'
                                                        );
                                                    }),
                                            ]),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Placeholder::make('material_display')
                                                    ->label('Material')
                                                    ->content(function ($record) {
                                                        $material = $record && $record->material ? $record->material : 'N/A';
                                                        $isAvailable = $record && $record->material;

                                                        return new HtmlString(
                                                            '<span class="'.($isAvailable ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'text-sm text-gray-500 dark:text-gray-400').'">'.
                                                            htmlspecialchars($material).
                                                            '</span>'
                                                        );
                                                    }),

                                                Forms\Components\Placeholder::make('variant_initial')
                                                    ->label('Variant Initial')
                                                    ->content(function ($record) {
                                                        $variantInitial = $record && $record->variant_initial ? $record->variant_initial : 'N/A';
                                                        $isAvailable = $record && $record->variant_initial;

                                                        return new HtmlString(
                                                            '<span class="'.($isAvailable ? 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'text-sm text-gray-500 dark:text-gray-400').'">'.
                                                            htmlspecialchars($variantInitial).
                                                            '</span>'
                                                        );
                                                    }),

                                                Forms\Components\Placeholder::make('created_at')
                                                    ->label('Created')
                                                    ->content(function ($record) {
                                                        if ($record && $record->created_at) {
                                                            return new HtmlString(
                                                                '<div class="flex items-center gap-2">'.
                                                                '<svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">'.
                                                                '<path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>'.
                                                                '</svg>'.
                                                                '<span class="text-sm font-medium text-gray-700 dark:text-gray-300">'.
                                                                $record->created_at->format('M d, Y').
                                                                '</span>'.
                                                                '</div>'
                                                            );
                                                        }

                                                        return new HtmlString(
                                                            '<span class="text-sm text-gray-500 dark:text-gray-400">N/A</span>'
                                                        );
                                                    }),
                                            ]),

                                        Forms\Components\KeyValue::make('additional_attributes')
                                            ->label('Additional Attributes')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpanFull()
                                            ->visible(fn ($record) => $record && ! empty($record->additional_attributes))
                                            ->addable(false)
                                            ->deletable(false)
                                            ->editableKeys(false)
                                            ->editableValues(false),
                                    ]),
                            ])
                            ->columnSpan(1),

                        // Right Column: Stock Information
                        Forms\Components\Section::make('Stock Information')
                            ->description('Current stock levels and inventory management')
                            ->schema([
                                Forms\Components\Grid::make(1)
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
                                                $currentStatus = $get('status');

                                                // Only auto-update status if it's not manually set to discontinued
                                                if ($currentStatus !== 'discontinued') {
                                                    if ($state <= 0) {
                                                        $set('status', 'out_of_stock');
                                                    } elseif ($state <= $reorderLevel) {
                                                        $set('status', 'low_stock');
                                                    } else {
                                                        $set('status', 'in_stock');
                                                    }
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
                                                $currentStatus = $get('status');

                                                // Only auto-update status if it's not manually set to discontinued
                                                if ($currentStatus !== 'discontinued') {
                                                    if ($currentStock <= 0) {
                                                        $set('status', 'out_of_stock');
                                                    } elseif ($currentStock <= $state) {
                                                        $set('status', 'low_stock');
                                                    } else {
                                                        $set('status', 'in_stock');
                                                    }
                                                }
                                            }),

                                        Forms\Components\Select::make('status')
                                            ->label('Stock Status')
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
                            ])
                            ->columnSpan(1),
                    ])
                    ->columnSpanFull(),
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
                        ]);

                        return ! empty($attributes) ? implode(' | ', $attributes) : 'Standard';
                    })
                    ->searchable([
                        'size',
                        'color',
                        'material',
                    ])
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
                    ->native(false)
                    ->query(function ($query, array $data) {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->where(function ($query) use ($data) {
                            foreach ($data['values'] as $status) {
                                switch ($status) {
                                    case 'out_of_stock':
                                        $query->orWhere('quantity_in_stock', '<=', 0);
                                        break;
                                    case 'low_stock':
                                        $query->orWhere(function ($subQuery) {
                                            $subQuery->where('quantity_in_stock', '>', 0)
                                                     ->whereColumn('quantity_in_stock', '<=', 'reorder_level');
                                        });
                                        break;
                                    case 'in_stock':
                                        $query->orWhere(function ($subQuery) {
                                            $subQuery->where('quantity_in_stock', '>', 0)
                                                     ->whereColumn('quantity_in_stock', '>', 'reorder_level');
                                        });
                                        break;
                                    case 'discontinued':
                                        $query->orWhere('status', 'discontinued');
                                        break;
                                }
                            }
                        });
                    }),

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
                    ->visible(fn () => ProductVariant::whereNotNull('material')->where('material', '!=', '')->exists()),

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
                    ->visible(fn () => ProductVariant::whereNotNull('color')->where('color', '!=', '')->exists()),

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
                    ->visible(fn () => ProductVariant::whereNotNull('size')->where('size', '!=', '')->exists()),
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
                fn (Tables\Actions\Action $action) => $action
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
                    ->modalHeading(fn ($record) => $record->product->name)
                    ->form([
                        Forms\Components\Section::make('Stock In Details')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Quantity In Stock')
                                            ->disabled()
                                            ->default(fn ($record) => $record->quantity_in_stock),

                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->disabled()
                                            ->default(fn ($record) => $record->reorder_level),
                                    ]),
                            ]),

                        Forms\Components\Section::make('Stock In Form')
                            ->schema([
                                Forms\Components\Repeater::make('stock_in_items')
                                    ->label('Stock In Items')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('stock_in_date')
                                                    ->label('Stock In Date & Time')
                                                    ->default(fn () => now())
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('M d, Y H:i')
                                                    ->format('Y-m-d H:i:s')
                                                    ->seconds(false)
                                                    ->timezone('Asia/Manila')
                                                    ->live()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('quantity_in')
                                                    ->label('Quantity In')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required()
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('reason')
                                                    ->label('Reason for Stock In')
                                                    ->options([
                                                        'returned' => 'Returned to Supplier',
                                                        'return_to_callie' => 'Return to Callie',
                                                        'restock' => 'Restock',
                                                        'other' => 'Other',
                                                    ])
                                                    ->default('restock')
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        if ($state === 'other') {
                                                            $set('show_custom_reason', true);
                                                        } else {
                                                            $set('show_custom_reason', false);
                                                            $set('custom_reason', null);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                Forms\Components\Textarea::make('notes')
                                                    ->label('Notes')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
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
                    ->modalHeading(fn ($record) => $record->product->name)
                    ->form([
                        Forms\Components\Section::make('Stock Out Details')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_stock')
                                            ->label('Quantity In Stock')
                                            ->disabled()
                                            ->default(fn ($record) => $record->quantity_in_stock),

                                        Forms\Components\TextInput::make('reorder_level')
                                            ->label('Reorder Level')
                                            ->disabled()
                                            ->default(fn ($record) => $record->reorder_level),
                                    ]),
                            ]),

                        Forms\Components\Section::make('Stock Out Form')
                            ->schema([
                                Forms\Components\Repeater::make('stock_out_items')
                                    ->label('Stock Out Items')
                                    ->schema([
                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                Forms\Components\ToggleButtons::make('platform')
                                                    ->label('Select Platform')
                                                    ->options([
                                                        'shopee' => 'Shopee',
                                                        'tiktok' => 'TikTok',
                                                        'bazaar' => 'Bazaar',
                                                        'others' => 'Others',
                                                    ])
                                                    ->icons([
                                                        'shopee' => 'heroicon-o-shopping-bag',
                                                        'tiktok' => 'heroicon-o-play',
                                                        'bazaar' => 'heroicon-o-building-storefront',
                                                        'others' => 'heroicon-o-ellipsis-horizontal',
                                                    ])
                                                    ->colors([
                                                        'shopee' => 'warning',
                                                        'tiktok' => 'danger',
                                                        'bazaar' => 'info',
                                                        'others' => 'gray',
                                                    ])
                                                    ->inline()
                                                    ->grouped()
                                                    ->columnSpanFull()
                                                    ->required(),
                                            ]),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('stock_out_date')
                                                    ->label('Stock Out Date & Time')
                                                    ->default(fn () => now())
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('M d, Y H:i')
                                                    ->format('Y-m-d H:i:s')
                                                    ->seconds(false)
                                                    ->timezone('Asia/Manila')
                                                    ->live()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('quantity_out')
                                                    ->label('Quantity Out')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required()
                                                    ->columnSpan(1),

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
                                                    ->default('sold') //
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        if ($state === 'other') {
                                                            $set('show_custom_reason', true);
                                                        } else {
                                                            $set('show_custom_reason', false);
                                                            $set('custom_reason', null);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                Forms\Components\Textarea::make('notes')
                                                    ->label('Notes')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
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
                    ->visible(fn ($record) => $record->quantity_in_stock > 0),

                // Removed manual edit action as requested

                Tables\Actions\ViewAction::make()
                    ->label('View Details'),

                Tables\Actions\Action::make('manage_stock')
                    ->label('Manage Stock')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('info')
                    ->url(fn (ProductVariant $record): string => static::getUrl('edit', ['record' => $record]))
                    ->tooltip('Manage stock levels and product details'),
            ], Tables\Enums\ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make('export_inventory')
                        ->label('Export Inventory')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('inventory-export-'.date('Y-m-d-H-i'))
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
                                            ]);

                                            return ! empty($attributes) ? implode(' - ', $attributes) : 'Standard';
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
        $outOfStockCount = ProductVariant::where('quantity_in_stock', '<=', 0)->count();
        $lowStockCount = ProductVariant::whereRaw('quantity_in_stock > 0 AND quantity_in_stock <= reorder_level')->count();

        $totalCount = $outOfStockCount + $lowStockCount;

        return $totalCount > 0 ? $totalCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $outOfStockCount = ProductVariant::where('quantity_in_stock', '<=', 0)->count();
        $lowStockCount = ProductVariant::whereRaw('quantity_in_stock > 0 AND quantity_in_stock <= reorder_level')->count();

        // Red badge if there are any out-of-stock items
        if ($outOfStockCount > 0) {
            return 'danger';
        }

        // Yellow badge if there are only low-stock items
        if ($lowStockCount > 0) {
            return 'warning';
        }

        // No badge if no issues
        return null;
    }
}
