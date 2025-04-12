<?php

namespace App\Filament\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
            ->label('شناسه'),
        ExportColumn::make('invoice.number')
            ->label('شناسه فاکتور'),
        ExportColumn::make('paymentable_type')
            ->label('نوع پرداخت')
            ->formatStateUsing(fn($state) => match ($state) {
                'App\Models\BankAccount' => 'حساب بانکی',
                'App\Models\PettyCash' => 'تنخواه',
                'App\Models\Fund' => 'صندوق',
                'App\Models\Check' => 'چک',
                'App\Models\MixedPayment' => 'ترکیبی',
                default => $state,
            }),
            ExportColumn::make('paymentable_id')
            ->label('شناسه مرتبط')
            ->formatStateUsing(function ($state, $record) {
                // $state همون paymentable_id هست، $record هم کل ردیف Payment
                $paymentable = $record->paymentable; // گرفتن مدل مرتبط از رابطه پلی‌مورفیک

                return match ($record->paymentable_type) {
                    'App\Models\Check' => $paymentable->serial_number ?? $state, // مثلاً شماره چک
                    'App\Models\BankAccount' => $paymentable->name ?? $state, // نام حساب بانکی
                    'App\Models\PettyCash' => $paymentable->name ?? $state, // نام تنخواه
                    'App\Models\Fund' => $paymentable->name ?? $state, // نام صندوق
                    default => $state, // اگه مدل شناخته‌شده نبود، همون ID خام
                };
            }),
        ExportColumn::make('amount')
            ->label('مبلغ پرداخت (ریال)')
            ->formatStateUsing(fn ($state) => number_format($state)),
        ExportColumn::make('reference_number')
            ->label('شماره مرجع'),
        ExportColumn::make('cheque_due_date')
            ->label('تاریخ سررسید چک'),
        ExportColumn::make('description')
            ->label('توضیحات'),
        ExportColumn::make('created_at_jalali')
            ->label('تاریخ پرداخت'),

        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your payment export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        $body = '';
        return $body;
    }
}
