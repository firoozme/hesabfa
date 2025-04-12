<?php
namespace App\Traits;

use App\Models\Ledger;
use App\Models\Transaction;

trait HasBalance
{
    public function getBalance()
    {
        return Transaction::where('account_id', $this->id)
                     ->where('account_type', get_class($this))
                     ->selectRaw('SUM(debit) - SUM(credit) as balance')
                     ->value('balance') ?? 0;
    }
}
