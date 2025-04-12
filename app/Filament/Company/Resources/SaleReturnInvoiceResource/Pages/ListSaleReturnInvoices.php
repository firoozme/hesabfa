<?php

namespace App\Filament\Company\Resources\SaleReturnInvoiceResource\Pages;

use App\Filament\Company\Resources\SaleReturnInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSaleReturnInvoices extends ListRecords
{
    protected static string $resource = SaleReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
