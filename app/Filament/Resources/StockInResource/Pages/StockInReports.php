<?php

namespace App\Filament\Resources\StockInResource\Pages;

use App\Filament\Resources\StockInResource;
use App\Models\StockIn;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StockInReports extends ListRecords
{
    protected static string $resource = StockInResource::class;

    protected static ?string $title = 'Stock In Report';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    public ?string $date = null;

    public function mount(): void
    {
        $this->date = request()->get('date', now()->format('Y-m-d'));
    }

    public function getTitle(): string
    {
        return 'Stock In Report for '.Carbon::parse($this->date)->format('F j, Y');
    }

    protected function getTableQuery(): Builder
    {
        $targetDate = Carbon::parse($this->date);

        return StockIn::query()
            ->with(['product', 'productVariant', 'user', 'stockInItems'])
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
                ->formatStateUsing(fn ($state) => '+'.number_format($state))
                ->badge()
                ->color('success'),

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
}
