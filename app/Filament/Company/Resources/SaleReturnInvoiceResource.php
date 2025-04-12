<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\SaleReturnInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\SaleReturnInvoiceResource\Pages;
use App\Filament\Company\Resources\SaleReturnInvoiceResource\RelationManagers;

class SaleReturnInvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    // protected static ?string $navigationLabel = 'فاکتور فروش';
    // protected static ?string $pluralLabel = 'فاکتورهای برگشت فروش';
    // protected static ?string $label = 'فاکتور برگشت فروش';
    // protected static ?string $navigationGroup = 'فروش';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSaleReturnInvoices::route('/'),
            'create' => Pages\CreateSaleReturnInvoice::route('/create'),
            'edit' => Pages\EditSaleReturnInvoice::route('/{record}/edit'),
        ];
    }
    protected static bool $shouldRegisterNavigation = false;
}
