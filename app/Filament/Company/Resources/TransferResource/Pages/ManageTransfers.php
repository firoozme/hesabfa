<?php

namespace App\Filament\Company\Resources\TransferResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Company\Resources\TransferResource;

class ManageTransfers extends ManageRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function(array $data){
                $data['company_id'] = auth('company')->id();
                $data['amount'] = str_replace( ',', '', $data['amount']);
                return $data;
            }),
        ];
    }
}
