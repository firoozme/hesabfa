<?php

namespace App\Filament\Company\Resources\StoreResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use App\Filament\Exports\StoreExporter;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Company\Resources\StoreResource;

class ManageStores extends ManageRecords
{
    protected static string $resource = StoreResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['latitude'] = $data['location']['lat'];
                    $data['longitude'] = $data['location']['lng'];
                    unset($data['location']);
                    $data['company_id'] = auth('company')->id();
                    return $data;
                }),
            ExportAction::make()
                ->label('خروجی اکسل')
                ->color('success')
                ->modalHeading('گرفتن خروجی ')
                ->icon('heroicon-o-arrow-up-tray')
                ->exporter(StoreExporter::class)
                ->formats([
                    ExportFormat::Xlsx,
                ])
                ->fileDisk('public'),
        ];
    }
}
