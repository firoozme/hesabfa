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
    public function boot(): void
    {
        Notifications::alignment(Alignment::End);
        Notifications::verticalAlignment(VerticalAlignment::End);
        Model::unguard();

        Table::$defaultDateDisplayFormat = 'l j F';
        Table::$defaultDateTimeDisplayFormat = 'l j F H:i:s';
        Infolist::$defaultDateDisplayFormat = 'l j F';
        Infolist::$defaultDateTimeDisplayFormat = 'l j F H:i:s';

        Payment::observe(PaymentObserver::class);

        

    }
}
