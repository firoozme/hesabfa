<?php

namespace App\Filament\Company\Resources\InvoiceResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use App\Filament\Exports\InvoiceExporter;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Company\Resources\InvoiceResource;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('خروجی اکسل')
                ->color('success')
                ->modalHeading('گرفتن خروجی ')
                ->icon('heroicon-o-arrow-up-tray')
                ->exporter(InvoiceExporter::class)
                ->formats([
                    ExportFormat::Xlsx,
                ])
                ->fileDisk('export'),
            Actions\CreateAction::make(),
            // Action::make('return')
            // ->label('ایجاد برگشت خرید')
            // ->color('danger')
            // ->url(fn()=> route('filament.company.resources.purchase-return-invoices.create'))
        ];
    }
}
