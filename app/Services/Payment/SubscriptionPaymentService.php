<?php

namespace App\Services\Payment;

use App\Models\Plan;
use App\Models\Company;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Traits\ZarinpalTrait;
use App\Models\SubscriptionPayment;
use Filament\Notifications\Notification;

class SubscriptionPaymentService
{
    use ZarinpalTrait;

    public function createSubscriptionAndPayment(Company $company, $planId, $paymentableType = null, $paymentableId = null)
    {
        // بررسی وجود اشتراک فعال
        $activeSubscription = Subscription::where('company_id', $company->id)
            ->latest()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        // if ($activeSubscription) {
            // Notification::make()
            //     ->title('خطا')
            //     ->body('شما یک اشتراک فعال دارید. لطفاً پس از انقضای اشتراک کنونی، پلن جدید انتخاب کنید.')
            //     ->danger()
            //     ->send();
            // return redirect()->route('filament.company.pages.pricing-page');
        // }

        $plan = Plan::findOrFail($planId);

        // ایجاد اشتراک جدید
        $subscription = Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => $plan->price == 0 ? 'active' : 'pending',
            'starts_at' => $plan->price == 0 ? now() : null,
            'ends_at' => $plan->price == 0 ? now()->addDays($plan->duration) : null,
        ]);

        if ($plan->price == 0) {
            // ثبت سند حسابداری برای پلن رایگان
            Transaction::create([
                'company_id' => $company->id,
                'debit' => 0,
                'credit' => 0,
                'description' => "فعال‌سازی پلن رایگان {$plan->name}",
                'transaction_type' => 'debit',
                'account_type' => 'App\Models\Subscription',
                'account_id' => $subscription->id,
                'financial_document_id' => null, // یا مقدار مناسب
                'transfer_id' => null, // یا مقدار مناسب
            ]);
            Notification::make()
                ->title('موفقیت')
                ->body('پلن رایگان با موفقیت فعال شد.')
                ->success()
                ->send();
            return redirect()->route('filament.company.pages.subscription-dashboard');
        }

        // تنظیمات پرداخت برای زرین‌پال
        $this->amount = $plan->price;
        $this->description = "پرداخت برای اشتراک {$plan->name}";
        $this->callbackUrl = route('subscription.payment.callback', ['subscription_id' => $subscription->id]);
        $this->metaData = [
            'subscriptionId' => $subscription->id,
            'companyId' => $company->id,
            'transactionType' => 'subscription',
        ];

        // درخواست پرداخت
        if ($this->paymentRequest()) {
            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'company_id' => $company->id,
                'amount' => $this->amount,
                'payment_gateway' => 'zarinpal',
                'authority' => $this->authority,
                'status' => 'pending',
                'description' => $this->description,
                'transaction_type' => 'subscription',
                'transaction_date' => now(),
                'paymentable_type' => $paymentableType,
                'paymentable_id' => $paymentableId,
            ]);

            return redirect($this->redirectUrl . $this->authority);
        }

        $subscription->delete();
        Notification::make()
            ->title('خطا')
            ->body($this->error ?? 'خطا در اتصال به درگاه پرداخت.')
            ->danger()
            ->send();
        return redirect()->route('filament.company.pages.pricing-page');
    }

    public function verifyPayment($authority, $subscriptionId)
    {
        $payment = SubscriptionPayment::where('authority', $authority)->firstOrFail();
        $subscription = $payment->subscription;

        $this->authority = $authority;
        $this->amount = $payment->amount;

        if ($this->paymentVerify()) {
            $subscription->update([
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays($subscription->plan->duration),
            ]);
// ثبت سند حسابداری
Transaction::create([
    'company_id' => $subscription->company_id,
    'debit' => 0,
    'credit' => $this->amount,
    'description' => "پرداخت برای اشتراک {$subscription->plan->name}",
    'transaction_type' => 'debit', // اگر نیاز به 'credit' است، جدول باید اصلاح شود
    'account_type' => 'App\Models\Subscription',
    'account_id' => $subscription->id,
    'financial_document_id' => null, // یا مقدار مناسب
    'transfer_id' => null, // یا مقدار مناسب
]);
            Notification::make()
                ->title('موفقیت')
                ->body('پرداخت با موفقیت انجام شد.')
                ->success()
                ->send();
            return redirect()->route('filament.company.pages.subscription-dashboard');
        }

        $subscription->delete();
        Notification::make()
            ->title('خطا')
            ->body($this->error ?? 'پرداخت ناموفق بود.')
            ->danger()
            ->send();
        return redirect()->route('filament.company.pages.pricing-page');
    }
}