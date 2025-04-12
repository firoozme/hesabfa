<?php

namespace App\Filament\Company\Resources\InstallmentSaleResource\Pages;

use App\Filament\Company\Resources\InstallmentSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInstallmentSale extends CreateRecord
{
    protected static string $resource = InstallmentSaleResource::class;
    
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return InstallmentSaleResource::createRecord($data);
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
}
