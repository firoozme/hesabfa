<?php

namespace App\Filament\Exports;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Person;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class InvoiceExporter extends Exporter
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [

            ExportColumn::make('number')
             ->label('شماره فاکتور'),
            ExportColumn::make('date_jalali')
             ->label('تاریخ'),
            ExportColumn::make('person.fullname')
             ->label('تأمین‌کننده'),
            ExportColumn::make('title')
             ->label('عنوان')


        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your product export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        $body = '';
        return $body;
    }
}
