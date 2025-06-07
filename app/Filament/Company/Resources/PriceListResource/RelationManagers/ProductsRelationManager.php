<?php

namespace App\Filament\Company\Resources\PriceListResource\RelationManagers;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $modelLabel = 'محصول';
    protected static ?string $pluralModelLabel = 'محصولات';
    protected static ?string $title = 'محصولات';
    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
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
                ImageColumn::make('image')
                ->label('عکس ')
                ->circular()
                ->extraImgAttributes(['loading' => 'lazy'])
                ->checkFileExistence(false)
                // ->default(fn(Product $record) => file_exists(asset('upload/'.$record->image))  ?  asset('upload/'.$record->image) : asset('upload/photo_placeholder.png') )

                ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                ->label('عنوان محصول'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                ->multiple()
                ->preloadRecordSelect()

            ])
            ->actions([
                Tables\Actions\DetachAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

}
