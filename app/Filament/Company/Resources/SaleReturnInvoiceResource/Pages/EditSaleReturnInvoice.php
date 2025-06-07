<?php

namespace App\Filament\Company\Resources\SaleReturnInvoiceResource\Pages;

use Filament\Actions;
use App\Models\Product;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Company\Resources\SaleReturnInvoiceResource;

class EditSaleReturnInvoice extends EditRecord
{
    protected static string $resource = SaleReturnInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

   
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
