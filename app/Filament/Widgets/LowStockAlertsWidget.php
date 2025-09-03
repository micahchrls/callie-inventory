<?php

namespace App\Filament\Widgets;

use App\Models\Product\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LowStockAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Low Stock & Critical Alerts';
    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'Out of Stock',
                        'low_stock' => 'Low Stock',
                        default => ucwords(str_replace('_', ' ', $state)),
                    }),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Current Stock')
                    ->sortable()
                    ->numeric()
                    ->alignEnd()
                    ->color(function ($record) {
                        if ($record->quantity_in_stock <= 0) return 'danger';
                        if ($record->quantity_in_stock <= $record->reorder_level) return 'warning';
                        return 'success';
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('reorder_level')
                    ->label('Reorder Level')
                    ->sortable()
                    ->numeric()
                    ->alignEnd()
                    ->color('info'),

                Tables\Columns\TextColumn::make('shortage')
                    ->label('Units Short')
                    ->sortable()
                    ->numeric()
                    ->alignEnd()
                    ->formatStateUsing(function ($record) {
                        $shortage = max(0, $record->reorder_level - $record->quantity_in_stock);
                        return $shortage > 0 ? $shortage : '-';
                    })
                    ->color(function ($record) {
                        $shortage = max(0, $record->reorder_level - $record->quantity_in_stock);
                        if ($shortage >= $record->reorder_level) return 'danger';
                        if ($shortage > 0) return 'warning';
                        return 'gray';
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('days_of_stock')
                    ->label('Days of Stock')
                    ->formatStateUsing(function ($record) {
                        if ($record->avg_daily_sales <= 0) {
                            return $record->quantity_in_stock > 0 ? '∞' : '0';
                        }

                        $days = round($record->quantity_in_stock / $record->avg_daily_sales, 1);
                        return $days . ' days';
                    })
                    ->color(function ($record) {
                        if ($record->avg_daily_sales <= 0) return 'gray';

                        $days = $record->quantity_in_stock / $record->avg_daily_sales;
                        if ($days <= 3) return 'danger';
                        if ($days <= 7) return 'warning';
                        return 'success';
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Last Restocked')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->placeholder('Never')
                    ->color(function ($record) {
                        if (!$record->last_restocked_at) return 'gray';

                        $daysSince = now()->diffInDays($record->last_restocked_at);
                        if ($daysSince > 60) return 'danger';
                        if ($daysSince > 30) return 'warning';
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->formatStateUsing(function ($record) {
                        if ($record->quantity_in_stock <= 0) return 'CRITICAL';
                        if ($record->avg_daily_sales > 0) {
                            $days = $record->quantity_in_stock / $record->avg_daily_sales;
                            if ($days <= 3) return 'URGENT';
                            if ($days <= 7) return 'HIGH';
                        }
                        return 'MEDIUM';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if ($record->quantity_in_stock <= 0) return 'danger';
                        if ($record->avg_daily_sales > 0) {
                            $days = $record->quantity_in_stock / $record->avg_daily_sales;
                            if ($days <= 3) return 'danger';
                            if ($days <= 7) return 'warning';
                        }
                        return 'info';
                    }),
            ])
            ->defaultSort('priority_score', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s');
    }

    protected function getTableQuery(): Builder
    {
        $thirtyDaysAgo = now()->subDays(30);

        return ProductVariant::query()
            ->select([
                'product_variants.*',
                DB::raw('COALESCE(AVG(daily_sales.daily_quantity), 0) as avg_daily_sales'),
                DB::raw('
                    CASE
                        WHEN product_variants.quantity_in_stock <= 0 THEN 1000
                        WHEN COALESCE(AVG(daily_sales.daily_quantity), 0) > 0 AND
                             (product_variants.quantity_in_stock / COALESCE(AVG(daily_sales.daily_quantity), 1)) <= 3 THEN 100
                        WHEN COALESCE(AVG(daily_sales.daily_quantity), 0) > 0 AND
                             (product_variants.quantity_in_stock / COALESCE(AVG(daily_sales.daily_quantity), 1)) <= 7 THEN 50
                        WHEN product_variants.quantity_in_stock <= product_variants.reorder_level THEN 25
                        ELSE 0
                    END as priority_score
                ')
            ])
            ->with(['product'])
            ->leftJoin(
                DB::raw("(
                    SELECT
                        stock_outs.product_variant_id,
                        DATE(stock_outs.created_at) as sale_date,
                        SUM(stock_out_items.quantity) as daily_quantity
                    FROM stock_outs
                    JOIN stock_out_items ON stock_outs.id = stock_out_items.stock_out_id
                    WHERE stock_outs.created_at >= '{$thirtyDaysAgo->format('Y-m-d H:i:s')}'
                    GROUP BY stock_outs.product_variant_id, DATE(stock_outs.created_at)
                ) as daily_sales"),
                'product_variants.id',
                '=',
                'daily_sales.product_variant_id'
            )
            ->whereIn('product_variants.status', ['low_stock', 'out_of_stock'])
            ->groupBy([
                'product_variants.id',
                'product_variants.product_id',
                'product_variants.sku',
                'product_variants.quantity_in_stock',
                'product_variants.reorder_level',
                'product_variants.status',
                'product_variants.size',
                'product_variants.color',
                'product_variants.material',
                'product_variants.variant_initial',
                'product_variants.additional_attributes',
                'product_variants.last_restocked_at',
                'product_variants.created_at',
                'product_variants.updated_at',
                'product_variants.deleted_at'
            ]);
    }
}
