<?php

namespace App\Filament\Company\Resources\FinancialDocumentResource\Pages;

use App\Filament\Company\Resources\FinancialDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinancialDocument extends EditRecord
{
    protected static string $resource = FinancialDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
