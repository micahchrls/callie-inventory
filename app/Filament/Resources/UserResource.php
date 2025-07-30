<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    // Role-based access control - only owners can manage users
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('users.view');
    }

    public static function canView($record): bool
    {
        return auth()->check() && auth()->user()->can('users.view');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('users.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('users.edit');
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('users.delete');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->check() && auth()->user()->can('users.delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Role Assignment')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->columns(2)
                            ->searchable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->colors([
                        'success' => 'owner',
                        'primary' => 'staff',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active users only')
                    ->falseLabel('Inactive users only')
                    ->native(false),
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        // Prevent deletion of the last owner
                        if ($record->hasRole('owner')) {
                            $ownerCount = User::role('owner')->where('is_active', true)->count();
                            if ($ownerCount <= 1) {
                                throw new \Exception('Cannot delete the last active owner.');
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Prevent bulk deletion that would remove all owners
                            $ownerIds = User::role('owner')->where('is_active', true)->pluck('id');
                            $recordIds = $records->pluck('id');

                            if ($ownerIds->intersect($recordIds)->count() === $ownerIds->count()) {
                                throw new \Exception('Cannot delete all active owners.');
                            }
                        }),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }
}
