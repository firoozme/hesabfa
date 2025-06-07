<?php

namespace App\Filament\Company\Resources\SettingResource\Pages;

use App\Filament\Company\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user('company')->id;

        return $data;
    }
}
