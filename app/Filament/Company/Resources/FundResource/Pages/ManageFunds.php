<?php

namespace App\Filament\Company\Resources\FundResource\Pages;

use App\Filament\Company\Resources\FundResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFunds extends ManageRecords
{
    protected static string $resource = FundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $data['company_id'] = auth('company')->id();
                return $data;
            }),
        ];
    }
}
