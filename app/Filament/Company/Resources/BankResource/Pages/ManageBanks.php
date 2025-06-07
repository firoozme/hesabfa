<?php

namespace App\Filament\Company\Resources\BankResource\Pages;

use App\Filament\Company\Resources\BankResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBanks extends ManageRecords
{
    protected static string $resource = BankResource::class;

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
