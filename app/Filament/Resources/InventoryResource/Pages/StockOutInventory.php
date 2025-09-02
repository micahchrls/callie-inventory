<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Models\Product\ProductVariant;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;

class StockOutInventory extends Page
{
    protected static string $resource = InventoryResource::class;

    protected static string $view = 'filament.resources.inventory-resource.pages.stock-out-inventory';

    protected static ?string $title = 'Stock Out';

    public ?array $data = [];

    public ProductVariant $record;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->fillForm();
    }

    protected function resolveRecord(int|string $key): ProductVariant
    {
        return InventoryResource::resolveRecordRouteBinding($key);
    }

    public function getTitle(): string|Htmlable
    {
        return "Stock Out - {$this->record->product->name}";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return "Remove stock from inventory for {$this->record->sku}";
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Infolists\Components\Section::make('Product Overview')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label('Product Name')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->icon('heroicon-m-cube'),

                                Infolists\Components\TextEntry::make('sku')
                                    ->label('SKU')
                                    ->badge()
                                    ->copyable()
                                    ->copyMessage('SKU copied!')
                                    ->copyMessageDuration(1500),

                                Infolists\Components\TextEntry::make('variation_display')
                                    ->label('Variation')
                                    ->getStateUsing(function ($record) {
                                        if ($record->variation_name) {
                                            return $record->variation_name;
                                        }

                                        $attributes = array_filter([
                                            $record->size,
                                            $record->color,
                                            $record->material,
                                            $record->variant_initial,
                                        ]);

                                        return ! empty($attributes) ? implode(' | ', $attributes) : 'Standard';
                                    })
                                    ->badge()
                                    ->color('gray'),

                                Infolists\Components\TextEntry::make('product.productCategory.name')
                                    ->label('Category')
                                    ->badge()
                                    ->color('primary'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('quantity_in_stock')
                                    ->label('Current Stock')
                                    ->numeric()
                                    ->badge()
                                    ->color(fn ($record) => match (true) {
                                        $record->quantity_in_stock <= 0 => 'danger',
                                        $record->quantity_in_stock <= $record->reorder_level => 'warning',
                                        default => 'success',
                                    })
                                    ->icon('heroicon-m-cube'),

                                Infolists\Components\TextEntry::make('reorder_level')
                                    ->label('Reorder Level')
                                    ->numeric()
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-m-exclamation-triangle'),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'in_stock' => 'success',
                                        'low_stock' => 'warning',
                                        'out_of_stock' => 'danger',
                                        'discontinued' => 'gray',
                                        default => 'secondary',
                                    })
                                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->icon('heroicon-m-information-circle'),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stock Removal Details')
                    ->description('Specify the quantity to remove and provide a reason for the stock adjustment')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('current_stock')
                                    ->label('Current Stock')
                                    ->disabled()
                                    ->default($this->record->quantity_in_stock)
                                    ->suffixIcon('heroicon-m-cube')
                                    ->extraAttributes(['class' => 'text-xl font-bold'])
                                    ->helperText('Available units in inventory')
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('quantity_out')
                                    ->label('Quantity to Remove')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue($this->record->quantity_in_stock)
                                    ->helperText('Units to remove from stock')
                                    ->suffixIcon('heroicon-m-minus-circle')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state && $get('current_stock')) {
                                            $newStock = max(0, $get('current_stock') - $state);
                                            $set('new_stock', $newStock);
                                        }
                                    })
                                    ->rules([
                                        'required',
                                        'integer',
                                        'min:1',
                                        fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                            if ($value > $this->record->quantity_in_stock) {
                                                $fail("Cannot remove {$value} units. Only {$this->record->quantity_in_stock} units available.");
                                            }
                                        },
                                    ]),

                                Forms\Components\TextInput::make('new_stock')
                                    ->label('New Stock Level')
                                    ->disabled()
                                    ->default($this->record->quantity_in_stock)
                                    ->suffixIcon('heroicon-m-arrow-trending-down')
                                    ->extraAttributes(['class' => 'font-bold'])
                                    ->helperText('Stock level after removal')
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Select::make('reason_type')
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
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === 'other') {
                                    $set('show_custom_reason', true);
                                } else {
                                    $set('show_custom_reason', false);
                                    $set('custom_reason', null);
                                }
                            })
                            ->prefixIcon('heroicon-m-clipboard-document-list'),

                        Forms\Components\Textarea::make('custom_reason')
                            ->label('Custom Reason')
                            ->visible(fn (Forms\Get $get) => $get('reason_type') === 'other')
                            ->required(fn (Forms\Get $get) => $get('reason_type') === 'other')
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder('Describe the custom reason for stock removal...')
                            ->autosize(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Any additional information about this stock out operation...')
                            ->columnSpanFull()
                            ->autosize(),

                        Forms\Components\Toggle::make('create_alert')
                            ->label('Create Low Stock Alert')
                            ->helperText('Automatically create an alert if stock falls below reorder level')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->model($this->record);
    }

    protected function fillForm(): void
    {
        $this->form->fill([
            'current_stock' => $this->record->quantity_in_stock,
            'new_stock' => $this->record->quantity_in_stock,
            'create_alert' => true,
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('process_stock_out')
                ->label('Process Stock Out')
                ->color('danger')
                ->icon('heroicon-o-arrow-down-circle')
                ->size(Actions\ActionSize::Large)
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->modalHeading('Confirm Stock Removal')
                ->modalDescription(function () {
                    $data = $this->form->getState();
                    $quantityOut = $data['quantity_out'] ?? 0;
                    $newStock = $this->record->quantity_in_stock - $quantityOut;

                    return "Are you sure you want to remove {$quantityOut} units from {$this->record->product->name}? Stock will be reduced from {$this->record->quantity_in_stock} to {$newStock} units.";
                })
                ->modalSubmitActionLabel('Yes, Remove Stock')
                ->action('processStockOut')
                ->keyBindings(['mod+s']),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->outlined()
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-x-mark')
                ->keyBindings(['escape']),
        ];
    }

    public function processStockOut(): void
    {
        try {
            $data = $this->form->getState();

            $oldStock = $this->record->quantity_in_stock;
            $quantityOut = $data['quantity_out'];

            // Validate stock availability
            if ($quantityOut > $oldStock) {
                Notification::make()
                    ->title('Insufficient Stock')
                    ->body("Cannot remove {$quantityOut} units. Only {$oldStock} units available.")
                    ->danger()
                    ->persistent()
                    ->actions([
                        Actions\Action::make('adjust_quantity')
                            ->button()
                            ->color('primary')
                            ->action(function () {
                                $this->form->fill(['quantity_out' => $this->record->quantity_in_stock]);
                            }),
                    ])
                    ->send();

                return;
            }

            // Build reason text
            $reasonText = $data['reason_type'] === 'other'
                ? $data['custom_reason']
                : ucfirst(str_replace('_', ' ', $data['reason_type']));

            if (! empty($data['notes'])) {
                $reasonText .= ' - '.$data['notes'];
            }

            // Use the adjustStock method with stock_out movement type
            $this->record->adjustStock($quantityOut, 'stock_out', $reasonText, 'stock_out');

            // Check if we need to create a low stock alert
            $newStock = $this->record->fresh()->quantity_in_stock;
            $alertCreated = false;

            if ($data['create_alert'] && $newStock <= $this->record->reorder_level) {
                $alertCreated = true;
                // Here you could create a low stock alert record if you have that functionality
            }

            $notificationBody = "Successfully removed {$quantityOut} units from {$this->record->product->name}. New stock level: {$newStock} units.";

            if ($alertCreated) {
                $notificationBody .= ' Low stock alert created.';
            }

            Notification::make()
                ->title('Stock Out Completed')
                ->body($notificationBody)
                ->success()
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->actions([
                    Actions\Action::make('view_movements')
                        ->button()
                        ->url(fn (): string => "/admin/stock-movements?tableFilters[product_variant_id][value]={$this->record->id}"),
                    Actions\Action::make('view_inventory')
                        ->button()
                        ->url(fn (): string => $this->getResource()::getUrl('view', ['record' => $this->record])),
                ])
                ->persistent()
                ->send();

            // Redirect back to inventory list
            $this->redirect($this->getResource()::getUrl('index'));

        } catch (Halt $exception) {
            return;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_inventory')
                ->label('View Full Details')
                ->color('gray')
                ->outlined()
                ->icon('heroicon-o-eye')
                ->url($this->getResource()::getUrl('view', ['record' => $this->record]))
                ->keyBindings(['mod+v']),

            Actions\Action::make('stock_history')
                ->label('Stock History')
                ->color('info')
                ->outlined()
                ->icon('heroicon-o-clock')
                ->url("/admin/stock-movements?tableFilters[product_variant_id][value]={$this->record->id}")
                ->openUrlInNewTab(),

            Actions\Action::make('quick_restock')
                ->label('Quick Restock')
                ->color('success')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->form([
                    Forms\Components\TextInput::make('restock_quantity')
                        ->label('Quantity to Add')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(10),
                ])
                ->action(function (array $data): void {
                    $this->record->adjustStock($data['restock_quantity'], 'add', 'Quick restock from stock out page');

                    Notification::make()
                        ->title('Stock Added')
                        ->body("Added {$data['restock_quantity']} units")
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->quantity_in_stock <= $this->record->reorder_level),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            $this->getResource()::getUrl('index') => $this->getResource()::getBreadcrumb(),
            $this->getResource()::getUrl('view', ['record' => $this->record]) => $this->record->sku,
            '' => 'Stock Out',
        ];
    }
}
