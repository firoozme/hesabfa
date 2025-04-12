<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Store;
use App\Models\Person;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Product;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class AdminOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('پرسنل', User::count()),
            Stat::make('فاکتورها', Invoice::count()),
            Stat::make('نقش ها', Role::count()),
            Stat::make('شرکت ها', Company::count()),
            Stat::make('مشتریان', Person::count()),
            Stat::make('محصولات', Product::count()),
            Stat::make('انبارها', Store::count()),
        ];
    }
    public static function canView(): bool
    {
        return Auth::user()?->can('card_view');
    }
}
