<?php

namespace App\Filament\Widgets;

use App\Models\Product\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Inventory Activity';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereNotNull('last_restocked_at')
                    ->orderBy('last_restocked_at', 'desc')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->limit(25)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 25 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('quantity_in_stock')
                    ->label('Current Stock')
                    ->alignCenter()
                    ->weight('bold')
                    ->color(fn ($record) => $record->getProductStatusColor())
                    ->formatStateUsing(fn ($state) => number_format($state)),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'in_stock',
                        'warning' => 'low_stock',
                        'danger' => 'out_of_stock',
                        'secondary' => 'discontinued',
                    ])
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('last_restocked_at')
                    ->label('Last Activity')
                    ->dateTime('M j, Y H:i')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->limit(15)
                    ->placeholder('Not specified'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->url(fn (Product $record): string => '/admin/inventory/' . $record->id),
            ])
            ->emptyStateHeading('No Recent Activity')
            ->emptyStateDescription('No inventory activities recorded yet.')
            ->emptyStateIcon('heroicon-o-clock')
            ->poll('120s'); // Refresh every 2 minutes
    }
}
