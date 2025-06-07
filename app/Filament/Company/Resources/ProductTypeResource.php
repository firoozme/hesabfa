<?php

namespace App\Filament\Company\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductType;
use Filament\Resources\Resource;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\ProductTypeResource\Pages;
use App\Filament\Company\Resources\ProductTypeResource\RelationManagers;

class ProductTypeResource extends Resource
{
    protected static ?string $model = ProductType::class;
    protected static ?string $navigationLabel = 'تعریف نوع محصول';
    protected static ?string $pluralLabel = 'نوع محصول';
    protected static ?string $label = 'نوع محصول';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth('company')->user()->id);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
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
                Tables\Columns\TextColumn::make('title')
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
            'index' => Pages\ManageProductTypes::route('/'),
        ];
    }
    protected static ?int $navigationSort = 30;

}
