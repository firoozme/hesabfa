<?php

namespace App\Filament\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\PersonTax;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PersonTaxResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PersonTaxResource\RelationManagers;

class PersonTaxResource extends Resource
{
    protected static ?string $model = PersonTax::class;
    protected static ?string $navigationLabel = 'نوع مالیات';
    protected static ?string $pluralLabel = 'نوع مالیات';
    protected static ?string $label = 'نوع مالیات';
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
            Tables\Columns\TextColumn::make('#')
                ->state(static function (HasTable $livewire, stdClass $rowLoop): string {
                    return (string) (
                        $rowLoop->iteration +
                        ($livewire->getTableRecordsPerPage() * (
                            $livewire->getTablePage() - 1
                        ))
                    );
                }),
            Tables\Columns\TextColumn::make('title')
            ->label('عنوان')
            ->searchable()
            ->sortable(),
            Tables\Columns\TextColumn::make('company.fullname')
            ->label('شرکت')
            ->searchable()
            ->sortable(),
        ])
        ->defaultSort('created_at','desc')

        ->filters([
            SelectFilter::make('status')
                    ->label('شرکت')
                    ->relationship('company','fullname')
        ], layout: FiltersLayout::AboveContent)
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
            'index' => Pages\ManagePersonTaxes::route('/'),
        ];
    }


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('person_tax_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('person_tax_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('person_tax_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('person_tax_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('person_tax_delete');
    }
}
