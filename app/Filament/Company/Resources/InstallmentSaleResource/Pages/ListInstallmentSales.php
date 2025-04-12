<?php

namespace App\Filament\Company\Resources\InstallmentSaleResource\Pages;

use App\Filament\Company\Resources\InstallmentSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstallmentSales extends ListRecords
{
    protected static string $resource = InstallmentSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
