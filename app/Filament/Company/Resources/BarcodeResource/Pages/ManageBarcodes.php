<?php

namespace App\Filament\Company\Resources\BarcodeResource\Pages;

use App\Filament\Company\Resources\BarcodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBarcodes extends ManageRecords
{
    protected static string $resource = BarcodeResource::class;

    public $temporaryData = [];

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function mount(): void
    {
        parent::mount();
        $this->temporaryData = $this->temporaryData ?? [];
    }
}