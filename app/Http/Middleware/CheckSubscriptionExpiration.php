<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class CheckSubscriptionExpiration
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('company')->check()) {
            $company = auth('company')->user();
            $activeSubscription = $company->subscriptions()
            ->latest()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->first();

            // Check Subscription Expired
            if (!$activeSubscription && !$request->routeIs('filament.company.pages.pricing-page', 'filament.company.auth.register', 'filament.company.auth.login', 'subscription.payment.callback') && !$request->isMethod('post')) {
                return redirect()->route('filament.company.pages.pricing-page')->with([
                    'notification' => [
                        'title' => 'خطا',
                        'message' => 'اشتراک شما منقضی شده است. لطفاً پلن جدیدی انتخاب کنید.',
                        'type' => 'danger',
                    ],
                ]);
            }

            // Redirect if Expired
            if(!session()->has('subscription_expiration_notified')){
                if ($activeSubscription && abs($activeSubscription->ends_at->diffInDays(now())) <= 2) {
                    Notification::make()
                        ->title('هشدار')
                        ->body('اشتراک شما تا کمتر از دو روز دیگر منقضی می‌شود. <a href="' . route('filament.company.pages.pricing-page') . '">برای تمدید کلیک کنید.</a>')
                        ->persistent()
                        ->danger()
                        ->send();
                }
                session()->put('subscription_expiration_notified', true);

            }
        }

        return $next($request);
    }
}