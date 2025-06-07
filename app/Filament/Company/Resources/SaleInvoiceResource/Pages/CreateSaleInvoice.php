<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use Filament\Actions;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Company\Resources\SaleInvoiceResource;

class CreateSaleInvoice extends CreateRecord
{
    protected static string $resource = SaleInvoiceResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth('company')->user()->id;
        $data['type'] = 'sale';

        return $data;
    }
    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $invoice = $this->record;

            // اطمینان از اینکه فاکتور حداقل یک آیتم دارد
            if ($invoice->items->isEmpty()) {
                throw new \Exception('فاکتور باید حداقل یک آیتم داشته باشد.');
            }
            foreach ($invoice->items as $item) {
                
                Product::where('id',$item->product_id)->update([
                    'selling_price' => $item->unit_price
                ]);
            }
        });
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
