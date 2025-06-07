<?php

namespace App\Filament\Company\Resources\ProductCategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $label = 'محصولات';
    protected static ?string $title = 'محصولات';
    protected static ?string $pluralLabel     = 'محصولات';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image')
                    ->label('عکس ')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->checkFileExistence(false)
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->label('عنوان')
                    ->sortable()
                    ->searchable(),
               
                Tables\Columns\TextColumn::make('barcode')
                    ->label('بارکد')
                    ->searchable(),
                    Tables\Columns\TextColumn::make('selling_price')
                    ->label('قیمت فروش')
                    ->sortable(),
                    Tables\Columns\TextColumn::make('purchase_price')
                    ->label('قیمت خرید')
                    ->sortable()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
