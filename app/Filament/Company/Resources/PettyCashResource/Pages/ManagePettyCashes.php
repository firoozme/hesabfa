<?php

namespace App\Filament\Company\Resources\PettyCashResource\Pages;

use App\Filament\Company\Resources\PettyCashResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePettyCashes extends ManageRecords
{
    protected static string $resource = PettyCashResource::class;

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
