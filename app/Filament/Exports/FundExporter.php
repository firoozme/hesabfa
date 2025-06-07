<?php

namespace App\Filament\Exports;

use App\Models\Fund;
use App\Models\Product;
use App\Models\PettyCash;
use Illuminate\Support\HtmlString;
use Filament\Actions\Exports\Exporter;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class FundExporter extends Exporter
{
    protected static ?string $model = Fund::class;

    public static function getColumns(): array
    {
        return [

            ExportColumn::make('accounting_code')
                ->label('کد حسابداری'),
            ExportColumn::make('name')
                ->label('نام صندوق'),
            ExportColumn::make('switch_number')
                ->label('شماره سوئیچ پرداخت'),
            ExportColumn::make('terminal_number')
                ->label('شماره ترمینال پرداخت'),
            ExportColumn::make('merchant_number')
                ->label('شماره پذیرنده فروشگاهی'),
            ExportColumn::make('description')
                ->label('توضیحات'),
            ExportColumn::make('created_at_jalali')
                ->label('تاریخ ایجاد'),
            ExportColumn::make('balance')
                ->label('موجودی')
                ->formatStateUsing(function ($record) {
                    // محاسبه موجودی همانند جدول FundResource
                    $balance = $record->incomingTransfers()->sum('amount') - $record->outgoingTransfers()->sum('amount');
                    return number_format($balance) . ' ریال';
                }),
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
