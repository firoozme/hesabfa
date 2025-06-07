<?php

namespace App\Filament\Company\Resources\TaxResource\Pages;

use App\Filament\Company\Resources\TaxResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTaxes extends ManageRecords
{
    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $data['company_id'] = auth('company')->user()->id;
                return $data;
            }),
        ];
    }
}
