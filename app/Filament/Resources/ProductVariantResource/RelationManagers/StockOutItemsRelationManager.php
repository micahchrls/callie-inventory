<?php

namespace App\Filament\Resources\ProductVariantResource\RelationManagers;

use App\Enums\Platform;
use App\Models\StockOutItem;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockOutItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockOutItems';

    protected static ?string $title = 'Stock Out History';

    protected static ?string $modelLabel = 'Stock Out Item';

    protected static ?string $pluralModelLabel = 'Stock Out Items';

    protected static ?string $icon = 'heroicon-o-arrow-down-circle';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Stock Out Summary by Platform')
                    ->description('View stock out items organized by platform')
                    ->icon('heroicon-m-chart-bar')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema($this->getPlatformSections()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('platform')
            ->columns([
                TextColumn::make('stockOut.created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tiktok' => 'pink',
                        'shopee' => 'orange',
                        'bazar' => 'blue',
                        'others' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string =>
                        Platform::from($state)->label()
                    )
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Quantity Out')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => '-' . number_format($state)),

                TextColumn::make('stockOut.reason')
                    ->label('Reason')
                    ->badge()
                    ->color('secondary')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'sale' => 'Sale/Order',
                            'damaged' => 'Damaged Goods',
                            'expired' => 'Expired Product',
                            'lost' => 'Lost/Missing',
                            'promotion' => 'Promotional Giveaway',
                            'sample' => 'Sample/Demo',
                            'return_to_supplier' => 'Return to Supplier',
                            'quality_issue' => 'Quality Issue',
                            'theft' => 'Theft/Shrinkage',
                            'adjustment' => 'Inventory Adjustment',
                            'other' => 'Other',
                            default => ucwords(str_replace('_', ' ', $state ?? 'Unknown')),
                        };
                    }),

                TextColumn::make('stockOut.user.name')
                    ->label('Processed By')
                    ->placeholder('System')
                    ->searchable(),

                TextColumn::make('note')
                    ->label('Notes')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 30 ? $state : null;
                    })
                    ->placeholder('-')
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options(Platform::options())
                    ->multiple(),

                SelectFilter::make('reason')
                    ->label('Reason')
                    ->options([
                        'sale' => 'Sale/Order',
                        'damaged' => 'Damaged Goods',
                        'expired' => 'Expired Product',
                        'lost' => 'Lost/Missing',
                        'promotion' => 'Promotional Giveaway',
                        'sample' => 'Sample/Demo',
                        'return_to_supplier' => 'Return to Supplier',
                        'quality_issue' => 'Quality Issue',
                        'theft' => 'Theft/Shrinkage',
                        'adjustment' => 'Inventory Adjustment',
                        'other' => 'Other',
                    ])
                    ->relationship('stockOut', 'reason'),
            ])
            ->defaultSort('stockOut.created_at', 'desc')
            ->emptyStateHeading('No stock out records found')
            ->emptyStateDescription('This product variant has no stock out history yet.')
            ->emptyStateIcon('heroicon-o-arrow-down-circle');
    }

    protected function getPlatformSections(): array
    {
        $sections = [];

        foreach (Platform::cases() as $platform) {
            $sections[] = $this->createPlatformSection($platform);
        }

        return $sections;
    }

    protected function createPlatformSection(Platform $platform): Section
    {
        return Section::make($platform->label() . ' Stock Outs')
            ->icon($this->getPlatformIcon($platform))
            ->schema([
                $this->getPlatformSummary($platform),
                $this->getPlatformRecentItems($platform),
            ])
            ->collapsible()
            ->collapsed();
    }

    protected function getPlatformIcon(Platform $platform): string
    {
        return match ($platform) {
            Platform::TIKTOK => 'heroicon-m-play',
            Platform::SHOPEE => 'heroicon-m-shopping-bag',
            Platform::BAZAR => 'heroicon-m-building-storefront',
            Platform::OTHERS => 'heroicon-m-ellipsis-horizontal',
        };
    }

    protected function getPlatformSummary(Platform $platform): TextEntry
    {
        return TextEntry::make('platform_summary_' . $platform->value)
            ->label('Total Stock Out')
            ->getStateUsing(function ($record) use ($platform) {
                $total = $this->getRelationshipQuery()
                    ->where('platform', $platform->value)
                    ->sum('quantity');

                return $total > 0 ? number_format($total) . ' units' : 'No stock outs';
            })
            ->badge()
            ->color(fn ($state) => str_contains($state, 'No stock') ? 'success' : 'danger')
            ->icon('heroicon-m-chart-bar');
    }

    protected function getPlatformRecentItems(Platform $platform): TextEntry
    {
        return TextEntry::make('platform_recent_' . $platform->value)
            ->label('Recent Activity')
            ->getStateUsing(function ($record) use ($platform) {
                $recent = $this->getRelationshipQuery()
                    ->with(['stockOut' => ['user']])
                    ->where('platform', $platform->value)
                    ->latest('created_at')
                    ->limit(3)
                    ->get();

                if ($recent->isEmpty()) {
                    return 'No recent activity';
                }

                return $recent->map(function ($item) {
                    $date = $item->stockOut->created_at->format('M d, Y');
                    $quantity = number_format($item->quantity);
                    $reason = match ($item->stockOut->reason) {
                        'sale' => 'Sale',
                        'damaged' => 'Damaged',
                        'expired' => 'Expired',
                        'lost' => 'Lost',
                        'promotion' => 'Promotion',
                        'sample' => 'Sample',
                        'return_to_supplier' => 'Return',
                        'quality_issue' => 'Quality',
                        'theft' => 'Theft',
                        'adjustment' => 'Adjustment',
                        'other' => 'Other',
                        default => 'Unknown',
                    };

                    return "• {$date}: -{$quantity} ({$reason})";
                })->join("\n");
            })
            ->html()
            ->color('gray');
    }

    protected function getRelationshipQuery(): Builder
    {
        return $this->getOwnerRecord()
            ->stockOutItems()
            ->with(['stockOut' => ['user']]);
    }
}
