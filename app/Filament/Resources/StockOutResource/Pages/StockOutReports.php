<?php

namespace App\Filament\Resources\StockOutResource\Pages;

use App\Filament\Resources\StockOutResource;
use App\Filament\Resources\StockOutResource\Widgets\PlatformStockOutStatsWidget;
use App\Models\StockOut;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StockOutReports extends ListRecords
{
    protected static string $resource = StockOutResource::class;

    protected static ?string $title = 'Stock Out Report';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    public ?string $date = null;

    public function mount(): void
    {
        $this->date = request()->get('date', now()->format('Y-m-d'));
    }

    public function getTitle(): string
    {
        return 'Stock Out Report for '.Carbon::parse($this->date)->format('F j, Y');
    }

    protected function getTableQuery(): Builder
    {
        $targetDate = Carbon::parse($this->date);

        return StockOut::query()
            ->with(['product', 'productVariant', 'user', 'stockOutItems'])
            ->whereDate('created_at', $targetDate->format('Y-m-d'))
            ->orderBy('created_at', 'desc');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('product.name')
                ->label('Product')
                ->searchable()
                ->sortable()
                ->default('N/A'),

            TextColumn::make('productVariant.sku')
                ->label('Variant SKU')
                ->searchable()
                ->sortable()
                ->default('N/A'),

            TextColumn::make('total_quantity')
                ->label('Quantity')
                ->numeric()
                ->sortable()
                ->formatStateUsing(fn ($state) => '-'.number_format($state))
                ->badge()
                ->color('danger'),

            TextColumn::make('platform')
                ->label('Platform')
                ->getStateUsing(function (StockOut $record): string {
                    // Get the primary platform from stock out items
                    $platforms = $record->stockOutItems->pluck('platform')->filter()->unique();
                    if ($platforms->isEmpty()) {
                        return 'Others';
                    }

                    return $platforms->count() > 1 ? 'Multiple' : $platforms->first();
                })
                ->formatStateUsing(fn ($state) => Str::title($state))
                ->badge()
                ->color(fn (string $state): string => match (strtolower($state)) {
                    'tiktok' => 'danger',
                    'shopee' => 'warning',
                    'bazar' => 'info',
                    'multiple' => 'gray',
                    default => 'secondary',
                })
                ->sortable(),

            TextColumn::make('reason')
                ->label('Reason')
                ->formatStateUsing(fn ($state) => Str::title(str_replace('_', ' ', $state)))
                ->badge()
                ->color('info')
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Time')
                ->dateTime('h:i A')
                ->sortable(),

            TextColumn::make('user.name')
                ->label('User')
                ->default('System')
                ->sortable(),
        ];
    }

    protected function getTableGroups(): array
    {
        return [
            Group::make('reason')
                ->label('Reason')
                ->getTitleFromRecordUsing(fn (StockOut $record): string => Str::title(str_replace('_', ' ', $record->reason)))
                ->collapsible(),

            Group::make('platform')
                ->label('Platform')
                ->getTitleFromRecordUsing(function (StockOut $record): string {
                    $platforms = $record->stockOutItems->pluck('platform')->filter()->unique();
                    if ($platforms->isEmpty()) {
                        return 'Others';
                    }

                    return $platforms->count() > 1 ? 'Multiple Platforms' : Str::title($platforms->first());
                })
                ->collapsible(),
        ];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No Stock Out Records';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'No stock out transactions were found for '.Carbon::parse($this->date)->format('F j, Y');
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-archive-box-x-mark';
    }

    protected function isPaginated(): bool
    {
        return false;
    }

    protected function getDefaultTableGrouping(): ?string
    {
        return 'reason';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_calendar')
                ->label('Back to Calendar')
                ->icon('heroicon-o-calendar-days')
                ->url(route('filament.admin.pages.dashboard'))
                ->color('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlatformStockOutStatsWidget::make(['date' => $this->date]),
        ];
    }
}
