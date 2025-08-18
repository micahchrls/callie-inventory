<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductSubCategoryResource\Pages;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSubCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductSubCategoryResource extends Resource
{
    protected static ?string $model = ProductSubCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Sub Categories';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Product Management';

    // Role-based access control - staff can only view, owners can manage
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('categories.view');
    }

    public static function canView($record): bool
    {
        return auth()->check() && auth()->user()->can('categories.view');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('categories.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('categories.edit');
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('categories.delete');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->check() && auth()->user()->can('categories.delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return ProductCategory::create($data)->getKey();
                    }),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
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
            'index' => Pages\ListProductSubCategories::route('/'),
            'create' => Pages\CreateProductSubCategory::route('/create'),
            'view' => Pages\ViewProductSubCategory::route('/{record}'),
            'edit' => Pages\EditProductSubCategory::route('/{record}/edit'),
        ];
    }
}
