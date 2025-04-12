<?php

namespace App\Filament\Company\Resources\FinancialDocumentResource\Pages;

use App\Filament\Company\Resources\FinancialDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialDocument extends CreateRecord
{
    protected static string $resource = FinancialDocumentResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user('comnpany')->id;

        return $data;
    }
}
