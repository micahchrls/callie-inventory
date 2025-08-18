<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;

class StockoutSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.stockout-summary-widget';

    public array $stats = [];

    public string $date = '';

    public ?string $platform = null;

    public function mount(array $stats, string $date, ?string $platform): void
    {
        $this->stats = $stats;
        $this->date = $date;
        $this->platform = $platform;
    }

    public function getFormattedDate(): string
    {
        return Carbon::parse($this->date)->format('F j, Y');
    }

    public function getFormattedValue(): string
    {
        $value = $this->stats['total_value'] ?? 0;

        return '$'.number_format($value, 2);
    }
}
