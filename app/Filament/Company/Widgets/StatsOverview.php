<?php

namespace App\Filament\Company\Widgets;

use App\Models\Fund;
use App\Models\Person;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PettyCash;
use App\Models\BankAccount;
use App\Models\CompanyBankAccount;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
             Stat::make('تعداد فاکتورها', Invoice::where('company_id',auth()->user('company')->id)->count()),
            // Stat::make('مجموع پرداخت ها', number_format(Payment::whereBetween('created_at', [now()->subYear(), now()])->sum('amount')). ' ریال ' ),
            Stat::make('تعداد مشتریان', Person::where('company_id',auth()->user('company')->id)->count()),
            Stat::make('تعداد تنخواه', PettyCash::where('company_id',auth()->user('company')->id)->count()),
            Stat::make('حساب بانکی', CompanyBankAccount::where('company_id',auth()->user('company')->id)->count()),
            Stat::make('تعداد صندوق', Fund::where('company_id',auth()->user('company')->id)->count()),

        ];
    }
}
