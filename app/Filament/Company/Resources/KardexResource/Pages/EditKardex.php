<?php

namespace App\Filament\Company\Resources\KardexResource\Pages;

use App\Filament\Company\Resources\KardexResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKardex extends EditRecord
{
    protected static string $resource = KardexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
