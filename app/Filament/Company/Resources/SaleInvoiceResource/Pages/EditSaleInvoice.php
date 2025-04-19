<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use App\Filament\Company\Resources\SaleInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaleInvoice extends EditRecord
{
    protected static string $resource = SaleInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
