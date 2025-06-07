<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\Company;
use App\Models\Customer;
use Filament\PanelProvider;
use Filament\Enums\ThemeMode;
use App\Models\CompanySetting;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Auth\Company\Otp;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Company\Profile;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Pages\Auth\Company\Login;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Company\Pages\PricingPage;
use App\Filament\Pages\Auth\Company\Register;
use App\Filament\Company\Widgets\AccountWidget;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Http\Middleware\CheckSubscriptionExpiration;
use App\Services\Payment\SubscriptionPaymentService;
use App\Filament\Company\Pages\SubscriptionDashboard;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class CompanyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('company')
            ->path('company')
            ->login(Login::class)
            ->registration(Register::class)
            ->profile(Profile::class)
            ->authGuard('company')
            ->darkMode() // فعال کردن پشتیبانی از حالت دارک
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->discoverResources(in: app_path('Filament/Company/Resources'), for: 'App\\Filament\\Company\\Resources')
            ->discoverPages(in: app_path('Filament/Company/Pages'), for: 'App\\Filament\\Company\\Pages')
            ->pages([
                Pages\Dashboard::class,
                PricingPage::class,
                SubscriptionDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                CheckSubscriptionExpiration::class
            ])
            
            ->authMiddleware([
                Authenticate::class,
            ])
            ->spa()
            ->topNavigation(function(){
                return $this->getMenuPosition();
            }) // تنظیم موقعیت منو پویا
            ->font('Yekan', url: asset('css/fonts.css'));

            
    }



    /**
     * دریافت موقعیت منو
     */
    protected function getMenuPosition(): bool
    {
        $user = auth('company')->user(); // استفاده از گارد company
        $setting = $user?->settings; // فرض بر این است که رابطه settings تعریف شده است

        return $setting?->menu_position === 'top'; // اگر top باشد true، در غیر این صورت false (برای sidebar)
    }
}