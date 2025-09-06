<?php

namespace App\Filament\Pages;

use App\Exports\StockInReportsExport;
use App\Models\StockIn;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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

    public ?string $timePeriod = 'day';

    public function mount(): void
    {
        $this->date = request()->get('date', now()->format('Y-m-d'));
        $this->timePeriod = request()->get('period', 'day');
    }

    public function getTitle(): string
    {
        $dateRange = $this->getDateRangeForPeriod();
        $periodLabel = $this->getPeriodLabel();

        return 'Stock In Report - '.$periodLabel;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->emptyStateHeading('No Stock In Records')
            ->emptyStateDescription('No stock in transactions were found for '.$this->getPeriodLabel())
            ->emptyStateIcon('heroicon-o-archive-box-x-mark');
    }

    protected function getTableQuery(): Builder
    {
        $dateRange = $this->getDateRangeForPeriod();

        return StockIn::query()
            ->with(['product', 'productVariant', 'user'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
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
                ->label('Date/Time')
                ->dateTime($this->timePeriod === 'day' ? 'h:i A' : 'M j, Y h:i A')
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
            Action::make('export_stock_in_report')
                ->label('Export Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('export_period')
                        ->label('Export Period')
                        ->options([
                            'day' => 'Daily Report',
                            'week' => 'Weekly Report',
                            'month' => 'Monthly Report',
                            'year' => 'Yearly Report',
                        ])
                        ->default($this->timePeriod)
                        ->required(),
                ])
                ->action(function (array $data) {
                    return $this->exportReport($data['export_period']);
                }),

            Action::make('change_period')
                ->label('Change Period')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->form([
                    Select::make('period')
                        ->label('Time Period')
                        ->options([
                            'day' => 'Daily Report',
                            'week' => 'Weekly Report',
                            'month' => 'Monthly Report',
                            'year' => 'Yearly Report',
                        ])
                        ->default($this->timePeriod)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->timePeriod = $data['period'];
                    $this->resetTable();
                }),

            Action::make('back_to_calendar')
                ->label('Back to Calendar')
                ->icon('heroicon-o-calendar-days')
                ->url(route('filament.admin.pages.dashboard'))
                ->color('gray'),
        ];
    }

    protected function getDateRangeForPeriod(): array
    {
        $baseDate = Carbon::parse($this->date);

        return match ($this->timePeriod) {
            'day' => [
                'start' => $baseDate->copy()->startOfDay(),
                'end' => $baseDate->copy()->endOfDay(),
            ],
            'week' => [
                'start' => $baseDate->copy()->startOfWeek(),
                'end' => $baseDate->copy()->endOfWeek(),
            ],
            'month' => [
                'start' => $baseDate->copy()->startOfMonth(),
                'end' => $baseDate->copy()->endOfMonth(),
            ],
            'year' => [
                'start' => $baseDate->copy()->startOfYear(),
                'end' => $baseDate->copy()->endOfYear(),
            ],
            default => [
                'start' => $baseDate->copy()->startOfDay(),
                'end' => $baseDate->copy()->endOfDay(),
            ],
        };
    }

    protected function getPeriodLabel(): string
    {
        $baseDate = Carbon::parse($this->date);

        return match ($this->timePeriod) {
            'day' => $baseDate->format('F j, Y'),
            'week' => 'Week of '.$baseDate->startOfWeek()->format('M j').' - '.$baseDate->endOfWeek()->format('M j, Y'),
            'month' => $baseDate->format('F Y'),
            'year' => $baseDate->format('Y'),
            default => $baseDate->format('F j, Y'),
        };
    }

    protected function exportReport($period)
    {
        // Temporarily change the period for export
        $originalPeriod = $this->timePeriod;
        $this->timePeriod = $period;

        $dateRange = $this->getDateRangeForPeriod();
        $periodLabel = $this->getPeriodLabel();

        // Generate filename
        $filename = 'stock-in-report-'.strtolower(str_replace([' ', ','], '-', $periodLabel)).'-'.now()->format('Y-m-d-His').'.xlsx';

        // Restore original period
        $this->timePeriod = $originalPeriod;

        // Use the existing StockInReportsExport class with correct syntax
        return Excel::download(
            new StockInReportsExport(
                $period,
                $dateRange['start'],
                $dateRange['end'],
                $periodLabel
            ),
            $filename
        );
    }
}
