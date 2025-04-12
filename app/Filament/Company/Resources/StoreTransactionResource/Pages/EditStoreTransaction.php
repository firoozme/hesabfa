<?php

namespace App\Filament\Company\Resources\StoreTransactionResource\Pages;

use App\Filament\Company\Resources\StoreTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreTransaction extends EditRecord
{
    protected static string $resource = StoreTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
