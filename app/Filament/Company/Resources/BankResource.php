<?php

namespace App\Filament\Company\Resources;

use stdClass;
use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\BankResource\Pages;
use App\Filament\Company\Resources\BankResource\RelationManagers;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;
    protected static ?string $navigationLabel = 'تعریف بانک';
    protected static ?string $pluralLabel = 'بانک ها';
    protected static ?string $label = 'بانک';
    protected static ?string $navigationGroup = 'بانکداری';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth('company')->user()->id);
    }
    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('name')
            ->label('عنوان')
                ->required()
                ->unique(ignoreRecord: true,modifyRuleUsing: function (Unique $rule) {
                    return $rule->where('deleted_at', null)->where('company_id', auth('company')->user()->id);
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
                Tables\Columns\TextColumn::make('name')
                ->label('عنوان')
                ->searchable()
                ->sortable(),
            ])
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
            'index' => Pages\ManageBanks::route('/'),
        ];
    }
    protected static ?int $navigationSort = 30;

}
