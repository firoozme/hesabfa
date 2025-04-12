<?php

namespace App\Filament\Exports;

use App\Models\Store;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StoreExporter extends Exporter
{
    protected static ?string $model = Store::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('title')
            ->label('نام انبار'),
            ExportColumn::make('description')
            ->label('توضیحات'),
            ExportColumn::make('location')
            ->label('لوکیشن'),
            ExportColumn::make('address')
            ->label('آدرس'),
            ExportColumn::make('phone_number')
            ->label('شماره تماس'),
            ExportColumn::make('created_at_jalali')
            ->label('تاریخ ایجاد'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your store export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        $body ='';
        return $body;
    }
}
