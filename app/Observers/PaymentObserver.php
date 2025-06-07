<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transfer;
use App\Models\Transaction;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        
        // "invoice_id" => "1"
        // "paymentable_type" => "App\Models\PettyCash"
        // "paymentable_id" => "1"
        // "amount" => "8000000"
        // "updated_at" => "2025-02-23 23:14:25"
        // "created_at" => "2025-02-23 23:14:25"
        // "id" => 17

       // ثبت تراکنش برای پرداخت فاکتور
       Transfer::create([
        'accounting_auto' => 'auto',
        'reference_number' => 'PAYMENT-'.mt_rand(10000,99999),
        'transfer_date' => now(),
        'amount' => $payment->amount,
        'description' => "پرداخت فاکتور شماره {$payment->invoice_id}",
        'company_id' => auth()->user('company')->id,

        // اطلاعات مبدا (حسابی که از آن پرداخت شده)
        'source_id' => $payment->type=='payment' ? $payment->paymentable_id : null,
        'source_type' => $payment->paymentable_type,

        // اطلاعات مقصد (می‌تواند همان حساب مبدا باشد یا نوع دیگری)
        'destination_id' => $payment->type=='payment' ? null : $payment->paymentable_id,
        'destination_type' => $payment->paymentable_type,

        // نوع تراکنش: پرداخت
        'transaction_type' => 'payment',

        // ارتباط با فاکتور
        'paymentable_id' => $payment->invoice_id,
        'paymentable_type' => Invoice::class,
    ]);
    //  ثبت تراکنش برای پرداخت فاکتور در جدول transactions
    //  Transaction::create([
    //     'account_id'   => $payment->paymentable_id, // حساب مقصد (صندوق، تنخواه و ...)
    //     'account_type' => $payment->paymentable_type,
    //     'debit'        => $payment->amount, // مبلغ پرداختی (برداشت از حساب)
    //     'credit'       => 0, // هیچ واریزی
    //     'transaction_type' => 'debit', // نوع تراکنش: برداشت
    //     'description'  => "پرداخت فاکتور شماره {$payment->invoice_id}",
    // ]);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}
