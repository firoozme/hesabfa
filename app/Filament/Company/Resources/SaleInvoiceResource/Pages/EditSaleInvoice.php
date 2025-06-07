<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use Filament\Actions;
use App\Models\Product;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Company\Resources\SaleInvoiceResource;

class EditSaleInvoice extends EditRecord
{
    protected static string $resource = SaleInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        $invoice = $this->record;

        foreach ($invoice->items as $item) {
            Product::where('id',$item->product_id)->update([
                'selling_price' => $item->unit_price
            ]);
        }
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
