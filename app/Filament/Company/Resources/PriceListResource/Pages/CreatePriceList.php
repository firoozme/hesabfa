<?php

namespace App\Filament\Company\Resources\PriceListResource\Pages;

use App\Filament\Company\Resources\PriceListResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePriceList extends CreateRecord
{
    protected static string $resource = PriceListResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
