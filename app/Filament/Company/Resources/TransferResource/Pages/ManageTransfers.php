<?php

namespace App\Filament\Company\Resources\TransferResource\Pages;

use Filament\Actions;
use Filament\Actions\ExportAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Exports\TransferExporter;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\Exports\Enums\ExportFormat;
use App\Filament\Company\Resources\TransferResource;

class ManageTransfers extends ManageRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
            ->label('خروجی اکسل')
            ->color('success')
            ->modalHeading('گرفتن خروجی ')
            ->icon('heroicon-o-arrow-up-tray')
            ->exporter(TransferExporter::class)
            ->formats([
                ExportFormat::Xlsx,
            ])
            ->fileName('انتقال_'.verta())
            ->fileDisk('public'),
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function(array $data){
                $data['company_id'] = auth('company')->id();
                $data['amount'] = str_replace( ',', '', $data['amount']);
                return $data;
            }),
        ];
    }
}
