<?php

namespace App\Filament\Company\Resources\KardexResource\Pages;

use App\Filament\Company\Resources\KardexResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKardexes extends ListRecords
{
    protected static string $resource = KardexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    
}
