<?php

namespace App\Filament\Pages;

use App\Models\StockIn;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StockInReportsDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string $view = 'filament.pages.stock-in-reports-dashboard';

    protected static ?string $title = 'Stock In Reports';

    protected static ?string $navigationLabel = 'Stock In Reports';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    public ?string $date = null;

    public function mount(): void
    {
        $this->date = request()->get('date', now()->format('Y-m-d'));
    }

    public function getTitle(): string
    {
        return 'Stock In Report for '.Carbon::parse($this->date)->format('F j, Y');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->emptyStateHeading('No Stock In Records')
            ->emptyStateDescription('No stock in transactions were found for '.Carbon::parse($this->date)->format('F j, Y'))
            ->emptyStateIcon('heroicon-o-archive-box-x-mark');
    }

    protected function getTableQuery(): Builder
    {
        $targetDate = Carbon::parse($this->date);

        return StockIn::query()
            ->with(['product', 'productVariant', 'user'])
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
