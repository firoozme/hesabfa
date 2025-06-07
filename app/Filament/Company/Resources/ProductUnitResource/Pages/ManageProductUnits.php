<?php

namespace App\Filament\Company\Resources\ProductUnitResource\Pages;

use App\Filament\Company\Resources\ProductUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProductUnits extends ManageRecords
{
    protected static string $resource = ProductUnitResource::class;

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
