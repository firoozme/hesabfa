<?php

namespace App\Filament\Company\Resources\ProductTypeResource\Pages;

use App\Filament\Company\Resources\ProductTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProductTypes extends ManageRecords
{
    protected static string $resource = ProductTypeResource::class;

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
