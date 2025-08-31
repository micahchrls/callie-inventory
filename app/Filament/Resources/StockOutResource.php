<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOutResource\Pages;
use App\Models\StockOut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockOutResource extends Resource
{
    protected static ?string $model = StockOut::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Forms\Components\Select::make('product_variant_id')
                    ->relationship('productVariant', 'sku')
                    ->required(),
                Forms\Components\TextInput::make('reason')
                    ->required(),
                Forms\Components\TextInput::make('total_quantity')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'success',
                        'damaged' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')
                    ->options([
                        'sale' => 'Sale',
                        'damaged' => 'Damaged',
                        'expired' => 'Expired',
                        'lost' => 'Lost',
                        'promotion' => 'Promotion',
                        'sample' => 'Sample',
                        'return_to_supplier' => 'Return to Supplier',
                        'quality_issue' => 'Quality Issue',
                        'theft' => 'Theft',
                        'adjustment' => 'Adjustment',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOuts::route('/'),
            'reports' => Pages\StockOutReports::route('/reports'),
        ];
    }
}
