<?php

namespace App\Filament\Company\Resources\CompanyBankAccountResource\Pages;

use Filament\Actions;
use App\Models\Capital;
use App\Models\Transfer;
use App\Models\Transaction;
use App\Models\CompanyBankAccount;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Company\Resources\CompanyBankAccountResource;

class ManageCompanyBankAccounts extends ManageRecords
{
    protected static string $resource = CompanyBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function (array $data): array {
                $data['company_id'] = auth('company')->id();
                return $data;
            })
            ->after(function ($data, CompanyBankAccount $record) {
                // ثبت موجودی اولیه به‌عنوان تراکنش
                if ($data['balance'] > 0) {
                    // ثبت تراکنش برای پرداخت فاکتور
                        Transfer::create([
                            'accounting_auto' => 'auto',
                            'reference_number' => 'INIT-'.mt_rand(10000,99999),
                            'transfer_date' => now(),
                            'amount' => $data['balance'],
                            'description' => "موجودی اولیه",
                            'company_id' => auth()->user('company')->id,

                            // اطلاعات مبدا (حسابی که از آن پرداخت شده)
                            'source_id' => null,
                            'source_type' => Capital::class,

                            // اطلاعات مقصد (می‌تواند همان حساب مبدا باشد یا نوع دیگری)
                            'destination_id' => $record->id,
                            'destination_type' => CompanyBankAccount::class,

                            // نوع تراکنش: پرداخت
                            'transaction_type' => 'init',

                            // ارتباط با فاکتور
                            'paymentable_id' => null,
                            'paymentable_type' => null,
                    ]);

                    
                }
            }),
            

        ];
    }
}
