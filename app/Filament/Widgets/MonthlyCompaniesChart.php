<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MonthlyCompaniesChart extends ChartWidget
{
    protected static ?string $heading = 'تعداد شرکت‌های ثبت‌شده در هر ماه';

    protected function getType(): string
    {
        return 'line'; // نوع نمودار خطی
    }
    protected static bool $isLazy = false;
    // protected int | string | array $columnSpan = 'full';
    protected function getData(): array
    {
       $chartData = Cache::remember('monthly_companies_chart', 3600, function () {
        $data = Trend::query(Company::whereYear('created_at', now()->year))
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear()
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'شرکت‌ها',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate)->all(),
                    'borderColor' => '#4CAF50',
                    'fill' => false,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => verta($value->date)->format('F'))->all(),
        ];
    });

    return $chartData;
    }

    public static function canView(): bool
    {
        return Auth::user()?->can('chart_view');
    }
}
