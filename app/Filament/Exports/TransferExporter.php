<?php
namespace App\Filament\Exports;

use App\Models\Fund;
use App\Models\Transfer;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class TransferExporter extends Exporter
{
    protected static ?string $model = Transfer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('reference_number')
                ->label('کد حسابداری'),
            ExportColumn::make('transfer_date_jalali')
                ->label('تاریخ انتقال'),
            ExportColumn::make('amount')
                ->label('مبلغ انتقال')
                ->formatStateUsing(fn($state) => number_format($state) . ' ریال'),
            ExportColumn::make('source.name')
                ->label('حساب مبدأ'),
            ExportColumn::make('destination.name')
                ->label('حساب مقصد'),
            ExportColumn::make('description')
                ->label('توضیحات'),
            ExportColumn::make('created_at_jalali')
                ->label('تاریخ ثبت'),
            ExportColumn::make('source_type')
                ->label('نوع حساب مبدأ')
                ->formatStateUsing(function ($state) {
                    return match ($state) {
                        'App\Models\CompanyBankAccount' => 'بانک',
                        'App\Models\Fund'               => 'صندوق',
                        'App\Models\PettyCash'          => 'تنخواه',
                        default                         => '-',
                    };
                }),
            ExportColumn::make('destination_type')
                ->label('نوع حساب مقصد')
                ->formatStateUsing(function ($state) {
                    return match ($state) {
                        'App\Models\CompanyBankAccount' => 'بانک',
                        'App\Models\Fund'               => 'صندوق',
                        'App\Models\PettyCash'          => 'تنخواه',
                        default                         => '-',
                    };
                }),
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
