<?php

namespace App\Filament\Company\Resources\IncomeResource\Pages;

use App\Filament\Company\Resources\IncomeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIncome extends CreateRecord
{
    protected static string $resource = IncomeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user('company')->id;

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
