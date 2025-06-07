<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class BankAccountExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [

            ExportColumn::make('accounting_code')
            ->label('کد حسابداری'),
        ExportColumn::make('name')
            ->label('عنوان حساب'),
        ExportColumn::make('bank.name')
            ->label('بانک'),
        ExportColumn::make('card_number')
            ->label('شماره کارت'),
        ExportColumn::make('account_number')
            ->label('شماره حساب')
            ->formatStateUsing(fn ($state) => $state ?? '-'),
        ExportColumn::make('iban')
            ->label('شماره شبا')
            ->formatStateUsing(fn ($state) => $state ?? '-'),
        ExportColumn::make('account_holder')
            ->label('نام صاحب حساب')
            ->formatStateUsing(fn ($state) => $state ?? '-'),
        ExportColumn::make('pos_number')
            ->label('شماره پوز')
            ->formatStateUsing(fn ($state) => $state ?? '-'),
        ExportColumn::make('description')
            ->label('توضیحات')
            ->formatStateUsing(fn ($state) => $state ?? '-'),
        ExportColumn::make('created_at_jalali')
            ->label('تاریخ ایجاد'),
        ExportColumn::make('balance')
            ->label('موجودی')
            ->formatStateUsing(fn ($state, $record) => number_format($record->incomingTransfers()->sum('amount') - $record->outgoingTransfers()->sum('amount')) . ' ریال'),

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
