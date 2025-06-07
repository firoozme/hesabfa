<?php

namespace App\Filament\Resources\ProductUnitResource\Pages;

use App\Filament\Resources\ProductUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProductUnits extends ManageRecords
{
    protected static string $resource = ProductUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
