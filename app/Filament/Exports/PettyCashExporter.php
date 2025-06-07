<?php

namespace App\Filament\Exports;

use App\Models\Product;
use App\Models\PettyCash;
use Illuminate\Support\HtmlString;
use Filament\Actions\Exports\Exporter;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class PettyCashExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [

            ExportColumn::make('accounting_code')
                ->label('کد حسابداری')
                ->getStateUsing(fn ($record) => $record->accounting_code ? $record->accounting_code : '-'),
            ExportColumn::make('name')
                ->label('نام حساب'),
            ExportColumn::make('balance')
                ->label('موجودی')
                ->getStateUsing(function(Model $record){
                    // محاسبه موجودی
                    $balance = $record->incomingTransfers()->sum('amount') - $record->outgoingTransfers()->sum('amount');

                     if ($balance < 0) {
                        $balance = -$balance;
                    } 
                    return  number_format($balance);
                }),
            ExportColumn::make('account_type')
                ->label('نوع حساب')
                ->formatStateUsing(function ($state) {
         
                        return 'تنخواه';
                    
                }),
           
            ExportColumn::make('created_at_jalali')
                ->label('تاریخ ایجاد')
                ->getStateUsing(fn ($record) => $record->created_at ? verta($record->created_at)->format('Y/m/d H:i:s') : '-'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }
        $body='';

        return $body;
    }
}
