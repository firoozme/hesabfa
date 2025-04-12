<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Exports\InvoiceItemExporter;
use App\Filament\Resources\InvoiceResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\InvoiceResource\RelationManagers;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationLabel = 'فاکتورها';
    protected static ?string $pluralLabel = 'فاکتورها';
    protected static ?string $label = 'فاکتور';
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
                Tables\Columns\TextColumn::make('number')->label('شماره فاکتور'),
                Tables\Columns\TextColumn::make('company.mobile')->label('شرکت'),
                Tables\Columns\TextColumn::make('title')->label('عنوان')
                ->default('-'),
                Tables\Columns\TextColumn::make('type')->label('نوع')
                ->formatStateUsing(function($state){
                    if($state == 'purchase')
                    return 'خرید';
                    elseif($state == 'purchase_return')
                    return 'برگشت خرید';
                    elseif($state == 'sale')
                    return 'فروش';
                    elseif($state == 'sale_return')
                    return 'برگشت فروش';
                    else
                    return '-';

                })
                ->color(function($state){
                    if($state == 'purchase')
                    return 'success';
                    elseif($state == 'purchase_return')
                    return 'danger';
                    elseif($state == 'sale')
                    return 'success';
                    elseif($state == 'sale_return')
                    return 'danger';
                    else
                    return '-';

                }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('جمع مبلغ')
                    ->money('irr', locale: 'fa')
                    ->getStateUsing(fn($record) => $record->items()->sum('total_price')),
                    Tables\Columns\TextColumn::make('date_jalali')->label('تاریخ'),

            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            ->actions([
              
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }


     // Role & Permissions
     public static function canViewAny(): bool
     {
         return Auth::user()?->can('invoice_view_any');
     }
 
     public static function canView(Model $record): bool
     {
         return Auth::user()?->can('invoice_view');
     }
 
     public static function canCreate(): bool
     {
         return Auth::user()?->can('invoice_create');
     }
 
     public static function canEdit(Model $record): bool
     {
         return Auth::user()?->can('invoice_update');
     }
 
     public static function canDelete(Model $record): bool
     {
         return Auth::user()?->can('invoice_delete');
     }
}
