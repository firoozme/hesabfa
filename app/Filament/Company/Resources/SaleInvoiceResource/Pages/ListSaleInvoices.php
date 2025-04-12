<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use App\Filament\Company\Resources\SaleInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSaleInvoices extends ListRecords
{
    protected static string $resource = SaleInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
