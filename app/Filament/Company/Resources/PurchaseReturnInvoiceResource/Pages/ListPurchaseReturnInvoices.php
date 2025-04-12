<?php

namespace App\Filament\Company\Resources\PurchaseReturnInvoiceResource\Pages;

use App\Filament\Company\Resources\PurchaseReturnInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseReturnInvoices extends ListRecords
{
    protected static string $resource = PurchaseReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
