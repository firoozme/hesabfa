<?php

namespace App\Filament\Company\Pages;

use Filament\Pages\Page;
use App\Models\Plan;
use App\Services\Payment\SubscriptionPaymentService;
use Filament\Notifications\Notification;

class PricingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'اشتراک';
    protected static ?string $title = 'اشتراک';
    protected static string $view = 'filament.company.pages.pricing-page';

    public function plans()
    {
        return Plan::where('is_active', true)->get();
    }
// app/Filament/Company/Pages/PricingPage.php
public function mount()
{
    if (session()->has('notification')) {
        $notification = session('notification');
        Notification::make()
            ->title($notification['title'])
            ->body($notification['message'])
            ->{$notification['type']}()
            ->send();
    }
}
    public function selectPlan($planId, SubscriptionPaymentService $paymentService)
    {
        $company = auth('company')->user();
        if (!$company) {
            Notification::make()
                ->title('خطا')
                ->body('لطفاً ابتدا وارد شوید.')
                ->danger()
                ->send();
            return redirect()->route('filament.company.auth.login');
        }

        $plan = Plan::find($planId);
        if (!$plan || !$plan->is_active) {
            Notification::make()
                ->title('خطا')
                ->body('پلن موردنظر یافت نشد یا غیرفعال است.')
                ->danger()
                ->send();
            return;
        }

        return $paymentService->createSubscriptionAndPayment($company, $planId);
    }

}