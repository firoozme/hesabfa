<?php

namespace App\Filament\Company\Resources\PaymentResource\Pages;

use App\Filament\Company\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
