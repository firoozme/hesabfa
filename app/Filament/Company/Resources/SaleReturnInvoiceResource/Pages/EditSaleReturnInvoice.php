<?php

namespace App\Filament\Company\Resources\SaleReturnInvoiceResource\Pages;

use App\Filament\Company\Resources\SaleReturnInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaleReturnInvoice extends EditRecord
{
    protected static string $resource = SaleReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
