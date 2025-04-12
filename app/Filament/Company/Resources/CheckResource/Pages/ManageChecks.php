<?php

namespace App\Filament\Company\Resources\CheckResource\Pages;

use App\Filament\Company\Resources\CheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageChecks extends ManageRecords
{
    protected static string $resource = CheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

}
