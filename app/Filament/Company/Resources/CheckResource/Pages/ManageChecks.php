<?php

namespace App\Filament\Company\Resources\CheckResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use App\Filament\Exports\ChequeExporter;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Company\Resources\CheckResource;

class ManageChecks extends ManageRecords
{
    protected static string $resource = CheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $data['company_id'] = auth('company')->id();
                return $data;
            }),
            ExportAction::make()
            ->label('خروجی اکسل')
            ->color('success')
            ->modalHeading('گرفتن خروجی ')
            ->icon('heroicon-o-arrow-up-tray')
            ->exporter(ChequeExporter::class)
            ->formats([
                ExportFormat::Xlsx,
            ])
            ->fileName('چک ها_'.verta())
            ->fileDisk('public'),
        ];
    }

}
