<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivedProductResource\Pages;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ArchivedProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $modelLabel = 'Archived Product';
    protected static ?string $pluralModelLabel = 'Archived Products';
    protected static ?string $navigationGroup = 'Product Management';
    protected static ?string $navigationLabel = 'Archived Products';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::onlyTrashed()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->onlyTrashed();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->readOnly(),

                        Forms\Components\TextInput::make('base_sku')
                            ->label('Base SKU')
                            ->maxLength(255)
                            ->readOnly(),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->readOnly(),

                        Forms\Components\Select::make('product_category_id')
                            ->label('Category')
                            ->relationship('productCategory', 'name')
                            ->readOnly(),

                        Forms\Components\Select::make('product_sub_category_id')
                            ->label('Sub Category')
                            ->relationship('productSubCategory', 'name')
                            ->readOnly(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'discontinued' => 'Discontinued',
                            ])
                            ->readOnly(),
                    ])
                    ->collapsed(),

                Forms\Components\Section::make('Archive Information')
                    ->schema([
                        Forms\Components\Placeholder::make('deleted_at')
                            ->label('Archived Date')
                            ->content(fn (Product $record): string =>
                                $record->deleted_at ? $record->deleted_at->format('M d, Y g:i A') : 'N/A'
                            ),

                        Forms\Components\Placeholder::make('variants_count')
                            ->label('Number of Variants')
                            ->content(fn (Product $record): string =>
                                $record->variants()->withTrashed()->count() . ' variants'
                            ),

                        Forms\Components\Placeholder::make('total_stock')
                            ->label('Total Stock (When Archived)')
                            ->content(fn (Product $record): string =>
                                $record->variants()->withTrashed()->sum('quantity_in_stock') . ' units'
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('base_sku')
                    ->label('Base SKU')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->placeholder('No category'),

                Tables\Columns\TextColumn::make('productSubCategory.name')
                    ->label('Sub Category')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('No sub category'),

                Tables\Columns\TextColumn::make('variants_count')
                    ->label('Variants')
                    ->getStateUsing(fn (Product $record): int =>
                        $record->variants()->withTrashed()->count()
                    )
                    ->badge()
                    ->color('secondary')
                    ->suffix(' variants'),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock (Archived)')
                    ->getStateUsing(fn (Product $record): int =>
                        $record->variants()->withTrashed()->sum('quantity_in_stock')
                    )
                    ->badge()
                    ->color('warning')
                    ->suffix(' units'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Archived Date')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->color('danger')
                    ->tooltip('Date when product was archived'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'discontinued' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_category_id')
                    ->label('Category')
                    ->relationship('productCategory', 'name')
                    ->placeholder('All Categories'),

                Tables\Filters\SelectFilter::make('product_sub_category_id')
                    ->label('Sub Category')
                    ->relationship('productSubCategory', 'name')
                    ->placeholder('All Sub Categories'),

                Tables\Filters\Filter::make('recently_archived')
                    ->label('Recently Archived (30 days)')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('deleted_at', '>=', now()->subDays(30))
                    )
                    ->indicator('Recently Archived'),

                Tables\Filters\Filter::make('has_variants')
                    ->label('Has Variants')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereHas('variants', fn (Builder $q) => $q->withTrashed())
                    )
                    ->indicator('Has Variants'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'discontinued' => 'Discontinued',
                    ])
                    ->placeholder('All Statuses'),
            ])
            ->actions([
                Tables\Actions\Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restore Product')
                    ->modalDescription('Are you sure you want to restore this product? This will make it available again in the active products list.')
                    ->modalSubmitActionLabel('Yes, Restore')
                    ->action(function (Product $record) {
                        $record->restore();

                        Notification::make()
                            ->title('Product Restored')
                            ->body("'{$record->name}' has been successfully restored.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Product $record): bool => $record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('restore')
                        ->label('Restore Selected')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Restore Selected Products')
                        ->modalDescription('Are you sure you want to restore the selected products? This will make them available again in the active products list.')
                        ->modalSubmitActionLabel('Yes, Restore All')
                        ->action(function (Collection $records) {
                            $count = $records->count();

                            foreach ($records as $record) {
                                $record->restore();
                            }

                            Notification::make()
                                ->title('Products Restored')
                                ->body("{$count} products have been successfully restored.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('deleted_at', 'desc')
            ->emptyStateHeading('No Archived Products')
            ->emptyStateDescription('There are no archived products at this time.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchivedProducts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
