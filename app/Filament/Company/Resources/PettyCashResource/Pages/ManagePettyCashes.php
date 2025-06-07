<?php

namespace App\Filament\Company\Resources\PettyCashResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Exports\PettyCashExporter;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Company\Resources\PettyCashResource;

class ManagePettyCashes extends ManageRecords
{
    protected static string $resource = PettyCashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
            ->label('خروجی اکسل')
            ->color('success')
            ->modalHeading('گرفتن خروجی ')
            ->icon('heroicon-o-arrow-up-tray')
            ->exporter(PettyCashExporter::class)
            ->formats([
                ExportFormat::Xlsx,
            ])
            ->fileName('تنخواه گردان_'.verta())
            ->fileDisk('public'),
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $data['company_id'] = auth('company')->id();
                return $data;
            }),
        ];
    }
}
