<?php

namespace App\Filament\Company\Resources\PriceListResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ExportAction;
use App\Filament\Exports\PriceListExporter;
use App\Filament\Company\Resources\PriceListResource;
use Filament\Actions\Exports\Enums\ExportFormat;

class ListPriceLists extends ListRecords
{
    protected static string $resource = PriceListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make()
            ->label('خروجی اکسل')
            ->color('success')
            ->modalHeading('گرفتن خروجی')
            ->icon('heroicon-o-arrow-up-tray')
            ->exporter(PriceListExporter::class)
            ->formats([
                ExportFormat::Xlsx,
            ])
            ->fileDisk('public'),
        ];
    }
}
