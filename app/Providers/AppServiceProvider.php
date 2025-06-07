<?php

namespace App\Providers;

use Livewire\Livewire;
use App\Models\Payment;
use Filament\Tables\Table;
use Doctrine\DBAL\Query\From;
use Filament\Infolists\Infolist;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\Lang;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Notifications\Livewire\Notifications;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Notifications::alignment(Alignment::End);
        Notifications::verticalAlignment(VerticalAlignment::End);
        Model::unguard();

        Table::$defaultDateDisplayFormat = 'l j F';
        Table::$defaultDateTimeDisplayFormat = 'l j F H:i:s';
        Infolist::$defaultDateDisplayFormat = 'l j F';
        Infolist::$defaultDateTimeDisplayFormat = 'l j F H:i:s';

        Payment::observe(PaymentObserver::class);


        if (auth('company')->check()) {
            $company = auth('company')->user();
            $activeSubscription = $company->subscriptions()
                 ->latest()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->first();
            if (!$activeSubscription && !request()->routeIs('filament.company.pages.pricing-page', 'filament.company.auth.register', 'filament.company.auth.login')) {
               Notification::make()
                    ->title('هشدار')
                    ->body('اشتراک شما منقضی شده است. لطفاً پلن جدیدی انتخاب کنید')
                    ->persistent()
                    ->danger()
                    ->send();
                return redirect()->route('filament.company.pages.pricing-page');
            }
            if ($activeSubscription && abs($activeSubscription->ends_at->diffInDays(now())) <= 2) {
                Notification::make()
                    ->title('هشدار')
                    ->body('اشتراک شما تا کمتر از دو روز دیگر منقضی می‌شود. <a href="' . route('filament.company.pages.pricing-page') . '">برای تمدید کلیک کنید.</a>')
                    ->persistent()
                    ->danger()
                    ->send();
            }
        }
        

    }
}
