<?php

namespace App\Filament\Company\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SubscriptionDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'داشبورد اشتراک';
    protected static ?string $title = 'داشبورد اشتراک';
    protected static string $view = 'filament.company.pages.subscription-dashboard';
    protected static ?string $slug = 'subscription-dashboard'; // اصلاح نوع به ?string

    public static function getRouteName(?string $panel = null): string
    {
        return 'filament.company.pages.subscription-dashboard';
    }
protected static bool $shouldRegisterNavigation = false;

    public function mount()
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
    }

    // public static function canAccess(): bool
    // {
    //     return auth('company')->check() && auth('company')->user()->can('viewSubscriptionDashboard');
    // }
}