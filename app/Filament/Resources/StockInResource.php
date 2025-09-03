<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockInResource\Pages;
use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use App\Models\StockIn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockInResource extends Resource
{
    protected static ?string $model = StockIn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Product::where('name', 'like', "%{$search}%")
                        ->orWhere('base_sku', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($query) use ($search) {
                            $query->where('sku', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name
                    )
                    ->required(),
                Forms\Components\Select::make('product_variant_id')
                    ->relationship('productVariant', 'sku')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => ProductVariant::where('sku', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('sku', 'id')
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => ProductVariant::find($value)?->sku
                    )
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
                Tables\Columns\TextColumn::make('reason'),
                Tables\Columns\TextColumn::make('total_quantity'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockIns::route('/'),
            'reports' => Pages\StockInReports::route('/reports'),
        ];
    }
}
