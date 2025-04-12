<?php

namespace App\Filament\Company\Resources\OpeningBalanceResource\Pages;

use Filament\Actions;
use App\Models\Capital;
use App\Models\Transfer;
use App\Models\Transaction;
use App\Models\OpeningBalance;
use App\Models\FinancialDocument;
use App\Models\AccountingDocument;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Company\Resources\OpeningBalanceResource;

class ManageOpeningBalances extends ManageRecords
{
    protected static string $resource = OpeningBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['company_id'] = auth('company')->user()->id;
                    return $data;
                })
                ->after(function (OpeningBalance $record) {
                    // ثبت سند حسابداری
                    // $accountingDocument = AccountingDocument::create([
                    //     'reference' => 'OPB-' . $record->id,
                    //     'date' => $record->date,
                    //     'description' => 'تراز افتتاحیه حساب ' . class_basename($record->accountable_type) . ' #' . $record->accountable_id,
                    //     'company_id' => $record->company_id,
                    // ]);

                    // // ثبت سند مالی
                    // $financialDocument = FinancialDocument::create([
                    //     'document_number' => 'OPB-' . $record->id,
                    //     'date' => $record->date,
                    //     'description' => 'تراز افتتاحیه حساب ' . class_basename($record->accountable_type) . ' #' . $record->accountable_id,
                    //     'company_id' => $record->company_id,
                    // ]);

                  
                    // Transaction::create([
                    //     'financial_document_id' => $financialDocument->id,
                    //     'account_id' => $record->accountable_id,
                    //     'account_type' =>  $record->accountable_type,
                    //     'debit' => $record->amount > 0 ? $record->amount : 0, // اگه مثبت باشه بدهکار,
                    //     'credit' => 0,
                    //     'description' => 'تراز افتتاحیه',
                    // ]);
            
                    // Transaction::create([
                    //     'financial_document_id' => $financialDocument->id,
                    //     'account_type' =>  $record->accountable_type,
                    //     'debit' => 0,
                    //     'credit' => $record->amount > 0 ? $record->amount : 0, // اگه مثبت باشه بدهکار,
                    //     'description' => 'تراز افتتاحیه ',
                    // ]);
                    Transfer::create([
                        'accounting_auto' => 'auto',
                        'reference_number' => 'OPB-'.mt_rand(10000,99999),
                        'transfer_date' => now(),
                        'amount' => $record->amount > 0 ? $record->amount : 0,
                        'description' => "تراز افتتاحیه",
                        'company_id' => auth()->user('company')->id,

                        // اطلاعات مبدا (حسابی که از آن پرداخت شده)
                        'source_id' => null,
                        'source_type' => null,

                        // اطلاعات مقصد (می‌تواند همان حساب مبدا باشد یا نوع دیگری)
                        'destination_id' => $record->accountable_id,
                        'destination_type' => $record->accountable_type,

                        // نوع تراکنش: پرداخت
                        'transaction_type' => 'init',

                        // ارتباط با فاکتور
                        'paymentable_id' => null,
                        'paymentable_type' => null,
                ]);

                    
                }),
        ];
    }
}
