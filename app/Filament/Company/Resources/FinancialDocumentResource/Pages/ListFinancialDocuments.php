<?php

namespace App\Filament\Company\Resources\FinancialDocumentResource\Pages;

use App\Filament\Company\Resources\FinancialDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialDocuments extends ListRecords
{
    protected static string $resource = FinancialDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
