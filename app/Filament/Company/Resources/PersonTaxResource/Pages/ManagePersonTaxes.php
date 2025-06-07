<?php

namespace App\Filament\Company\Resources\PersonTaxResource\Pages;

use App\Filament\Company\Resources\PersonTaxResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePersonTaxes extends ManageRecords
{
    protected static string $resource = PersonTaxResource::class;

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
