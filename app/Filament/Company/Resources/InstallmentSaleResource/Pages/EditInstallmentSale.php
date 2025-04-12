<?php

namespace App\Filament\Company\Resources\InstallmentSaleResource\Pages;

use App\Filament\Company\Resources\InstallmentSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstallmentSale extends EditRecord
{
    protected static string $resource = InstallmentSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
}
