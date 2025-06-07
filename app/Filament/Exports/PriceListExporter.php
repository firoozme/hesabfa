<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PriceListExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [

            ExportColumn::make('name')
             ->label('عنوان لیست'),
            ExportColumn::make('start_date')
             ->label('تاریخ شروع'),
            ExportColumn::make('end_date')
             ->label('تاریخ اتمام'),
            ExportColumn::make('purchase_price')
             ->label('قیمت خرید'),
            ExportColumn::make('id')
             ->label('لینک')
             ->formatStateUsing(fn (string $state) => route('price.list',['record'=> $state ])),
           
            ExportColumn::make('created_at_jalali')
             ->label('تاریخ ایجاد'),

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
