<?php

namespace App\Filament\Company\Resources\PersonResource\Pages;

use Filament\Actions;
use App\Models\Person;
use App\Models\Account;
use App\Models\PersonType;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Company\Resources\PersonResource;

class ManagePeople extends ManageRecords
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->createAnother(false)
            ->mutateFormDataUsing(function (array $data): array {
                $data['company_id'] = auth('company')->id();
                return $data;
            })
            ->after(function (Model $record, $data) {
                if ($record->types()->where('title', 'تامین کننده')->exists()) {
                    $account = Account::create([
                        'code' => $record->accounting_code,
                        'name' => 'حساب تأمین‌کننده ' . $record->fullname,
                        'type' => 'liability',
                        'company_id' => auth()->user('company')->id,
                        'balance' => 0,
                    ]);
                    $record->update(['account_id' => $account->id]);
                }
                // Runs after the form fields are saved to the database.
            })
            ,

        ];
    }

}
