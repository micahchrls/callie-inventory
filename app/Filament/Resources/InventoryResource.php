<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use App\Models\Product\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Layout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use App\Imports\ProductInventoryImport;
use Maatwebsite\Excel\Facades\Excel;

class InventoryResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?string $modelLabel = 'Inventory Item';

    protected static ?string $pluralModelLabel = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(2),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Stock Management')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('quantity_in_stock')
                                    ->label('Current Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1)
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
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
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
                                    ->native(false),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Item')
                            ->default(true)
                            ->helperText('Inactive items are hidden from operations'),

                        Forms\Components\DateTimePicker::make('last_restocked_at')
                            ->label('Last Restocked')
                            ->displayFormat('M d, Y H:i')
                            ->helperText('When was this item last restocked?'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Inventory Notes')
                            ->rows(3)
                            ->placeholder('Add any notes about this inventory item...')
                            ->helperText('Special handling, supplier info, etc.'),
                    ])
                    ->columns(1),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 40 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Stock')
                    ->sortable()
                    ->alignCenter()
                    ->size('lg')
                    ->weight('bold')
                    ->color(fn($record) => $record->getProductStatusColor())
                    ->formatStateUsing(fn($state) => number_format($state)),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder At')
                    ->sortable()
                    ->alignCenter()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => number_format($state)),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'in_stock',
                        'warning' => 'low_stock',
                        'danger' => 'out_of_stock',
                        'secondary' => 'discontinued',
                    ])
                    ->formatStateUsing(fn($state) => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),


                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                // Quick Action Filters (Always visible for immediate access)
                Tables\Filters\Filter::make('low_stock_alert')
                    ->label('⚠️ Low Stock Alert')
                    ->query(fn(Builder $query): Builder => $query->whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0'))
                    ->toggle(),

                Tables\Filters\Filter::make('out_of_stock_alert')
                    ->label('🚨 Out of Stock')
                    ->query(fn(Builder $query): Builder => $query->where('quantity_in_stock', '<=', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('needs_restock')
                    ->label('🔄 Needs Restock')
                    ->query(fn(Builder $query): Builder => $query->whereRaw('quantity_in_stock <= reorder_level'))
                    ->toggle(),

                // Product Classification Filters
                Tables\Filters\SelectFilter::make('product_category_id')
                    ->label('Category')
                    ->relationship('productCategory', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->native(false),

                Tables\Filters\SelectFilter::make('product_sub_category_id')
                    ->label('Sub-Category')
                    ->relationship('productSubCategory', 'name')
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
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('restocked_from')
                                    ->label('From Date'),
                                Forms\Components\DatePicker::make('restocked_until')
                                    ->label('To Date'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['restocked_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('last_restocked_at', '>=', $date)
                            )
                            ->when(
                                $data['restocked_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('last_restocked_at', '<=', $date)
                            );
                    }),

                Tables\Filters\Filter::make('updated_date')
                    ->label('Updated Date')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('updated_from')
                                    ->label('From Date'),
                                Forms\Components\DatePicker::make('updated_until')
                                    ->label('To Date'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['updated_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('updated_at', '>=', $date)
                            )
                            ->when(
                                $data['updated_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('updated_at', '<=', $date)
                            );
                    }),
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
                Tables\Actions\Action::make('quick_restock')
                    ->label('Quick Restock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Add Quantity')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->step(1),
                        Forms\Components\Checkbox::make('update_restock_date')
                            ->label('Update restocked date')
                            ->default(true),
                    ])
                    ->action(function (array $data, Product $record): void {
                        $record->adjustStock($data['quantity'], 'add');

                        if ($data['update_restock_date']) {
                            $record->update(['last_restocked_at' => now()]);
                        }

                        Notification::make()
                            ->title('Stock Updated')
                            ->body("Added {$data['quantity']} units to {$record->name}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Manage Stock'),

                Tables\Actions\ViewAction::make()
                    ->label('View Details'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import_inventory')
                    ->label('Import Inventory')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('success')
                    ->form([
                        Forms\Components\FileUpload::make('import_file')
                            ->label('Excel File')
                            ->required()
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->maxSize(5120) // 5MB
                            ->helperText('Upload an Excel file (.xlsx or .xls) with inventory data. Maximum file size: 5MB')
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('import_format')
                            ->label('Expected Format')
                            ->content('Your Excel file should have these columns: Product Name, Variation Name, SKU, Stock Qty (Shipped), Stock Out (Shipped), Category, Sub-Category')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $import = new ProductInventoryImport();
                            Excel::import($import, $data['import_file']);

                            $summary = $import->getImportSummary();

                            // Create detailed notification message
                            $message = "Import completed successfully!\n";
                            $message .= "• Created: {$summary['imported']} products\n";
                            $message .= "• Updated: {$summary['updated']} products\n";

                            if ($summary['skipped'] > 0) {
                                $message .= "• Skipped: {$summary['skipped']} rows\n";
                            }

                            $notificationColor = 'success';
                            $notificationIcon = '✅';

                            if (!empty($summary['errors'])) {
                                $message .= "\n⚠️ Errors encountered:\n";
                                foreach (array_slice($summary['errors'], 0, 5) as $error) {
                                    $message .= "• {$error}\n";
                                }
                                if (count($summary['errors']) > 5) {
                                    $message .= "... and " . (count($summary['errors']) - 5) . " more errors";
                                }
                                $notificationColor = 'warning';
                                $notificationIcon = '⚠️';
                            }

                            Notification::make()
                                ->title("{$notificationIcon} Inventory Import Complete")
                                ->body($message)
                                ->color($notificationColor)
                                ->duration(10000) // Show for 10 seconds
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('❌ Import Failed')
                                ->body("Error importing inventory: {$e->getMessage()}")
                                ->danger()
                                ->duration(8000)
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make('export_inventory')
                        ->label('Export Inventory')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('inventory-report-' . date('Y-m-d-H-i'))
                                ->withColumns([
                                    Column::make('sku')->heading('SKU'),
                                    Column::make('name')->heading('Product Name'),
                                    Column::make('productCategory.name')->heading('Category'),
                                    Column::make('productSubCategory.name')->heading('Sub Category'),
                                    Column::make('quantity_in_stock')->heading('Current Stock'),
                                    Column::make('reorder_level')->heading('Reorder Level'),
                                    Column::make('status')->heading('Status'),
                                    Column::make('is_active')->heading('Active'),
                                    Column::make('last_restocked_at')->heading('Last Restocked'),
                                    Column::make('notes')->heading('Notes'),
                                ])
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
                                $record->adjustStock($data['quantity'], $data['action']);

                                if ($data['update_restock_date']) {
                                    $record->update(['last_restocked_at' => now()]);
                                }
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
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'in_stock' => 'In Stock',
                                    'low_stock' => 'Low Stock',
                                    'out_of_stock' => 'Out of Stock',
                                    'discontinued' => 'Discontinued',
                                ])
                                ->required()
                                ->native(false),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Set as Active')
                                ->helperText('Leave unchecked to keep current status'),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $updates = ['status' => $data['status']];
                            if (isset($data['is_active'])) {
                                $updates['is_active'] = $data['is_active'];
                            }

                            foreach ($records as $record) {
                                $record->update($updates);
                            }

                            Notification::make()
                                ->title('🏷️ Status Updated')
                                ->body("Status updated for {$records->count()} items")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('quantity_in_stock', 'asc')
            ->poll('30s') // Auto-refresh every 30 seconds
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
        return Product::where('quantity_in_stock', '<=', 0)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Product::where('quantity_in_stock', '<=', 0)->count() > 0 ? 'danger' : null;
    }
}
