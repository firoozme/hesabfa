<?php

namespace App\Filament\Company\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductUnit;
use Filament\Resources\Resource;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\ProductUnitResource\Pages;
use App\Filament\Company\Resources\ProductUnitResource\RelationManagers;

class ProductUnitResource extends Resource
{
    protected static ?string $model = ProductUnit::class;
    protected static ?string $navigationLabel = 'تعریف واحد شمارش';
    protected static ?string $pluralLabel = 'واحد شمارش';
    protected static ?string $label = 'واحد شمارش';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth('company')->user()->id);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('عنوان واحد')
                    ->required()
                    ->unique(ignoreRecord: true,modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('deleted_at', null)->where('company_id',auth('company')->user()->id);
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
                ->label('عنوان واحد')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('تعداد محصولات')
                    ->getStateUsing(function (ProductUnit $record): int {
                        return $record->products()->count();
                    }),
              
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
            'index' => Pages\ManageProductUnits::route('/'),
        ];
    }
    protected static ?int $navigationSort = 31;
}
