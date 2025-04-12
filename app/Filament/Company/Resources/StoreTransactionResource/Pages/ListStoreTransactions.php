<?php

namespace App\Filament\Company\Resources\StoreTransactionResource\Pages;

use App\Filament\Company\Resources\StoreTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreTransactions extends ListRecords
{
    protected static string $resource = StoreTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make()->label('ایجاد تراکنش'),
        ];
    }
}
