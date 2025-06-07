<?php

namespace App\Traits;

use Filament\Tables\Table;

trait DefaultTableSettings
{
    protected function configureTable(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25]);
    }
}