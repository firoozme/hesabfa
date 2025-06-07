<?php

namespace App\Filament\Company\Resources\FundResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use App\Filament\Exports\FundExporter;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Exports\TransactionExporter;
use App\Filament\Company\Resources\FundResource;
use Filament\Actions\Exports\Enums\ExportFormat;

class ManageFunds extends ManageRecords
{
    protected static string $resource = FundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
            ->label('خروجی اکسل')
            ->color('success')
            ->modalHeading('گرفتن خروجی ')
            ->icon('heroicon-o-arrow-up-tray')
            ->exporter(FundExporter::class)
            ->formats([
                ExportFormat::Xlsx,
            ])
            ->fileName('صندوق_'.verta())
            ->fileDisk('public'),
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $data['company_id'] = auth('company')->id();
                return $data;
            }),
        ];
    }
}
