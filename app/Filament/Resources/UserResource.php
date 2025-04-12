<?php

namespace App\Filament\Resources;

use stdClass;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\Eloquent\Builder;
use Rawilk\FilamentPasswordInput\Password;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'کاربر';
    protected static ?string $pluralModelLabel = 'کاربران';
    public static function getNavigationGroup(): ?string
    {
        return __(config('filament-spatie-roles-permissions.navigation_section_group', 'filament-spatie-roles-permissions::filament-spatie.section.roles_and_permissions'));
    }
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('firstname')
                ->label('نام')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('lastname')
                ->label('نام خانوادگی')
                ->required()
                ->maxLength(255),
            Select::make('roles')
                 ->label('نقش')
                 ->required()
                 ->multiple()
                ->relationship('roles', 'name')
                ->hidden(fn($record)=> isset($record->username) && ($record->username=='admin'))
                ->preload(),
            Forms\Components\TextInput::make('username')
            ->label('نام کاربری')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true,modifyRuleUsing: function (Unique $rule) {
                    return $rule->where('deleted_at', null);
                })
                ->hidden(fn($record)=> isset($record->username) && ($record->username=='admin')),
            Password::make('password')
            ->label('رمزعبور')
                ->password()
                ->required()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create')
                ->maxLength(10)
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('#')->state(
                static function (HasTable $livewire, stdClass $rowLoop): string {
                    return (string) (
                        $rowLoop->iteration +
                        ($livewire->getTableRecordsPerPage() * (
                            $livewire->getTablePage() - 1
                        ))
                    );
                }
            ),
            Tables\Columns\TextColumn::make('firstname')
                ->label('نام')
                ->searchable(),
            Tables\Columns\TextColumn::make('lastname')
            ->label('نام خانوادگی')
                ->searchable(),
            Tables\Columns\TextColumn::make('username')
            ->label('نام کاربری')
                ->searchable(),
            Tables\Columns\TextColumn::make('roles.name')
            ->label('نقش')
            ->default(fn($record) => ($record->username == 'admin') ? 'مدیرکل': '' ),
            Tables\Columns\TextColumn::make('created_at_jalali')
            ->label('تاریخ عضویت')
                ->sortable()

        ])

        ->filters([
            //
        ])
        ->actions([

            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
            ->hidden(fn($record) =>   (auth()->user()->id == $record->id) || ($record->username == 'admin')  ),
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
            'index' => Pages\ManageUsers::route('/'),
        ];
    }


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('user_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('user_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('user_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('user_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('user_delete');
    }
}
