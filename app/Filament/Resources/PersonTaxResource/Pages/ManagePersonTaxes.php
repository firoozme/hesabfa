<?php

namespace App\Filament\Resources\PersonTaxResource\Pages;

use App\Filament\Resources\PersonTaxResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePersonTaxes extends ManageRecords
{
    protected static string $resource = PersonTaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
