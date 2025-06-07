<?php

namespace App\Filament\Company\Widgets;

use Filament\Tables;
use App\Models\Check;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CheckWarning extends BaseWidget
{
    protected static ?int $sort = 100; // عدد کمتر یعنی اولویت بالاتر

    protected function getTableHeading(): string
    {
        return 'چک‌های نزدیک به سررسید (تایک ماه آینده)';
        }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Check::query()
                ->where('company_id', auth('company')->user()->id) // فرض: چک‌ها به شرکت ربط دارن
                ->where('due_date', '>=', now())
                ->where('due_date', '<=', now()->addDays(30)) // چک‌هایی که تا 7 روز آینده سررسید دارن
                ->whereIn('status', ['in_progress', 'overdue']) // فقط چک‌های در حال اجرا یا سررسید گذشته
                ->orderBy('due_date', 'asc')
            )
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('serial_number')
                ->label('شماره صیاد')
                ->searchable(),

            \Filament\Tables\Columns\TextColumn::make('bank')
                ->label('بانک')
                ->searchable(),
            \Filament\Tables\Columns\TextColumn::make('amount')
                ->label('مبلغ')
                ->money('IRR', locale: 'fa')
                ->sortable(),
            \Filament\Tables\Columns\TextColumn::make('due_date_jalali')
                ->label('تاریخ سررسید')
                ->sortable(),
            \Filament\Tables\Columns\TextColumn::make('status_label')
                ->label('وضعیت')
                ->badge(),
            \Filament\Tables\Columns\TextColumn::make('type')
                ->label('نوع')
                ->badge()
                ->color(fn($state) => $state === 'receivable' ? 'success' : 'danger')
                ->formatStateUsing(fn($state) => $state === 'receivable' ? 'دریافتی' : 'پرداختی'),
            ]);
    }

    public static function canView(): bool
{
    return Check::query()
    ->where('company_id', auth('company')->user()->id) // فرض: چک‌ها به شرکت ربط دارن
    ->where('due_date', '>=', now())
    ->where('due_date', '<=', now()->addDays(30)) // چک‌هایی که تا 7 روز آینده سررسید دارن
    ->whereIn('status', ['in_progress', 'overdue']) // فقط چک‌های در حال اجرا یا سررسید گذشته
    ->orderBy('due_date', 'asc')->count();
}
}
