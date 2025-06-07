<?php

namespace App\Filament\Company\Pages;

use Filament\Tables;
use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Tables\Table;
use App\Models\Installment;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Concerns\InteractsWithTable;

class InstallmentStatusReport extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.installment-status-report';
    protected static ?string $navigationLabel = 'گزارش وضعیت اقساط';
    protected static ?string $pluralLabel     = 'گزارش وضعیت اقساط';
    protected static ?string $title           = 'گزارش وضعیت اقساط';
    protected static ?string $navigationGroup = 'فروش';


    public function table(Table $table): Table
    {
        return $table
		 ->emptyStateHeading('اقساطی وجود ندارد')
         ->query(Installment::query()->whereHas('installmentSale.invoice', function ($query) {
            $query->where('company_id', auth()->user('company')->id);
        }))
            ->columns([
                Tables\Columns\TextColumn::make('installmentSale.invoice.title')
                    ->label('فاکتور')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ قسط')
                    ->money('IRR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date_jalali')
                    ->label('تاریخ سررسید')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'pending' => 'در انتظار',
                        'paid' => 'پرداخت‌شده',
                    })
                    ->color(fn(string $state) => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                    })
                    ->sortable(),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'pending' => 'در انتظار',
                        'paid' => 'پرداخت‌شده',
                    ]),
                // فیلتر فاکتور
                Tables\Filters\SelectFilter::make('invoice_id')
                    ->label('فاکتور')
                    ->options(function () {
                        return Invoice::where('type', 'sale')
                        ->where('company_id', auth()->user('company')->id)
                            ->pluck('number', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->whereHas('installmentSale', function ($query) use ($data) {
                                $query->where('invoice_id', $data['value']);
                            });
                        }
                    }),
                
                // فیلتر خریدار
                Tables\Filters\SelectFilter::make('person_id')
                    ->label('خریدار')
                    ->options(function () {
                        return \App\Models\Person::where('company_id', auth()->user('company')->id)->pluck('fullname', 'id')->toArray(); // فرض بر وجود مدل Customer
                    })
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->whereHas('installmentSale.invoice', function ($query) use ($data) {
                                $query->where('person_id', $data['value']);
                            });
                        }
                    }),
                ], layout: FiltersLayout::AboveContent)
                ->filtersFormColumns(3)
            
            ->actions([
                Tables\Actions\Action::make('view_sale')
                    ->icon('heroicon-o-eye')
                    ->label('مشاهده فروش')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('جزئیات فروش اقساطی')
                    ->modalSubmitAction(false) // دکمه "ثبت" را حذف می‌کند چون فقط برای مشاهده است
                    ->modalCancelActionLabel('بستن')
                    ->form([
                        TextInput::make('invoice_number')
                            ->label('شماره فاکتور')
                            ->default(fn(Installment $record) => $record->installmentSale->invoice->number ?? 'نامشخص')
                            ->disabled(),
                        TextInput::make('total_amount')
                            ->label('مبلغ کل')
                            ->default(fn(Installment $record) => $record->installmentSale->total_amount ?? 0)
                            ->suffix('ریال')
                            ->disabled()
                            ->formatStateUsing(fn($state) => number_format($state, 0, '.', ',')),
                        TextInput::make('prepayment')
                            ->label('پیش‌پرداخت')
                            ->default(fn(Installment $record) => $record->installmentSale->prepayment ?? 0)
                            ->suffix('ریال')
                            ->disabled()
                            ->formatStateUsing(fn($state) => number_format($state, 0, '.', ',')),
                        TextInput::make('installment_count')
                            ->label('تعداد اقساط')
                            ->default(fn(Installment $record) => $record->installmentSale->installment_count ?? 0)
                            ->disabled(),
                        TextInput::make('annual_interest_rate')
                            ->label('نرخ بهره سالانه')
                            ->default(fn(Installment $record) => $record->installmentSale->annual_interest_rate ?? 0)
                            ->suffix('%')
                            ->disabled(),
                        TextInput::make('start_date')
                            ->label('تاریخ شروع اقساط')
                            ->default(fn(Installment $record) => \Carbon\Carbon::parse($record->installmentSale->start_date)->toJalali()->format('Y/m/d'))
                            ->disabled(),
                    ]),
            ]);
    }
    protected static ?int $navigationSort = 6;
}
