<?php

namespace App\Filament\Company\Resources\PurchaseReturnInvoiceResource\Pages;

use App\Filament\Company\Resources\PurchaseReturnInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseReturnInvoice extends EditRecord
{
    protected static string $resource = PurchaseReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
