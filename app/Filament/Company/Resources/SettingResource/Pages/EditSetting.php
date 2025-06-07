<?php

namespace App\Filament\Company\Resources\SettingResource\Pages;

use App\Filament\Company\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        // رفرش صفحه با هدایت به همان صفحه ویرایش
        $this->redirect(SettingResource::getUrl('edit', ['record' => $this->record->id]));
    }
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            // $this->getCancelFormAction(),
        ];
    }
}
