<?php

namespace App\Filament\Exports;

use App\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [

            ExportColumn::make('name')
             ->label('نام'),
            ExportColumn::make('barcode')
             ->label('بارکد'),
            ExportColumn::make('selling_price')
             ->label('قیمت فروش'),
            ExportColumn::make('purchase_price')
             ->label('قیمت خرید'),
            ExportColumn::make('inventory')
             ->label('موجودی'),
            ExportColumn::make('minimum_order')
             ->label('حداقل سفارش'),
            ExportColumn::make('lead_time')
             ->label('زمان انتظار')
             ->suffix('روز'),
            ExportColumn::make('reorder_point')
             ->label('نقطه سفارش'),
            ExportColumn::make('sales_tax')
             ->label('مالیات فروش')
             ->formatStateUsing(fn (string $state) => (int)$state. ' درصد '),
            ExportColumn::make('purchase_tax')
             ->label('مالیات خرید')
             ->formatStateUsing(fn (string $state) => (int)$state. ' درصد '),
            ExportColumn::make('type')
             ->label('نوع')
             ->formatStateUsing(fn (string $state) => ($state == 'Goods') ? 'کالا' : 'خدمات' ),
            ExportColumn::make('unit.name')
             ->label('واحد مالیاتی'),
            ExportColumn::make('tax.title')
             ->label('نوع مالیات'),
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
