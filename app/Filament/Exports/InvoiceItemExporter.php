<?php

namespace App\Filament\Exports;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\ProductUnit;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class InvoiceItemExporter extends Exporter
{
    protected static ?string $model = InvoiceItem::class;

    public $invoice;

    protected function query($query)
    {
        if (request()->has('invoice_id')) {
            $this->invoice = Invoice::find(request()->get('invoice_id'));
            // dd($this->invoice );
            return InvoiceItem::where('id', request()->get('invoice_id'));
        }

        return InvoiceItem::query();
    }
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('invoice.number')
            ->label('شماره فاکتور')
            ->formatStateUsing(fn($state) => $state ?? ''),
            ExportColumn::make('product.name')
            ->label('نام محصول'),
            ExportColumn::make('description')
            ->label('شرح'),
            ExportColumn::make('product.unit.name')
            ->label('واحد'),
            // ->formatStateUsing(function($state){
            //     $unit = ProductUnit::find($state);
            //     return $unit->name;
            // }),
            ExportColumn::make('quantity')
            ->label('تعداد'),
            ExportColumn::make('unit_price')
            ->label('قیمت واحد'),
            ExportColumn::make('sum_price')
            ->label('جمع'),
            ExportColumn::make('discount')
            ->label('تخفیف به درصد'),
            ExportColumn::make('discount_price')
            ->label('مبلغ تخفیف'),
            ExportColumn::make('tax')
            ->label('مالیات به درصد'),
            ExportColumn::make('tax_price')
            ->label('مبلغ مالیات'),
            ExportColumn::make('total_price')
            ->label('جمع کل'),
            ExportColumn::make('created_at_jalali')
            ->label('تاریخ ایجاد فاکتور'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your invoice item export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

$body = 'محصولات فاکتور ';

        return $body;
    }
}
