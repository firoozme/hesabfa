<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            // فقط اگر کاربر از نوع User بود، چک super admin رو انجام بده
            if ($user instanceof User) {
                return $user->isSuperAdmin ? true : null;
            }
            return null; // برای مدل‌های دیگه مثل Company، چیزی برنگردون
        });
    }
}
