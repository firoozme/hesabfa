<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\PersonType;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PersonTypeResource\Pages;
use App\Filament\Resources\PersonTypeResource\RelationManagers;

class PersonTypeResource extends Resource
{
    protected static ?string $model = PersonType::class;
    protected static ?string $navigationLabel = 'نوع شخص';
    protected static ?string $pluralLabel = 'نوع شخص';
    protected static ?string $label = 'نوع شخص';
    protected static ?string $navigationGroup = 'اشخاص';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                ->label('عنوان')
                    ->required()
                    ->unique(ignoreRecord: true,modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('deleted_at', null);
                    })
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('title')
            ->label('عنوان')
            ->searchable()
            ->sortable(),
        ])
        ->defaultSort('created_at','desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManagePersonTypes::route('/'),
        ];
    }


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('person_type_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('person_type_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('person_type_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('person_type_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('person_type_delete');
    }
}
