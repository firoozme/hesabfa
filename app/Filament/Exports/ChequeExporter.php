<?php
namespace App\Filament\Exports;

use App\Models\Fund;
use App\Models\Check;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class ChequeExporter extends Exporter
{
    protected static ?string $model = Check::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('serial_number')
                ->label('شماره صیاد'),
            ExportColumn::make('payer')
                ->label('پرداخت کننده')
                ->formatStateUsing(function ($state) {
                    $supplier = \App\Models\Person::find($state);
                    return $supplier ? $supplier->fullname : '-';
                }),
            ExportColumn::make('bank')
                ->label('بانک'),
            ExportColumn::make('branch')
                ->label('شعبه'),
            ExportColumn::make('amount')
                ->label('مبلغ')
                ->formatStateUsing(fn ($state) => number_format($state)),
            ExportColumn::make('date_received_jalali')
                ->label('تاریخ دریافت'),
            ExportColumn::make('due_date_jalali')
                ->label('تاریخ سررسید'),
            ExportColumn::make('status_label')
                ->label('وضعیت'),
            ExportColumn::make('type')
                ->label('نوع چک')
                ->formatStateUsing(fn ($state) => ($state == 'receivable') ? 'دریافتی' : 'پرداختی'),
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
