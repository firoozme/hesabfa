<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use App\Filament\Company\Resources\SaleInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ExportAction;
use App\Filament\Exports\InvoiceExporter;

class ListSaleInvoices extends ListRecords
{
    protected static string $resource = SaleInvoiceResource::class;

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
                ->fileDisk('public'),
            Actions\CreateAction::make(),
        ];
    }
}
