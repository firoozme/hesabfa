<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Bank;
use App\Models\Fund;
use Filament\Tables;
use App\Models\Check;
use App\Models\Income;
use App\Models\Invoice;
use Filament\Forms\Get;
use App\Models\Transfer;
use Filament\Forms\Form;
use App\Models\PettyCash;
use Illuminate\View\View;
use Filament\Tables\Table;
use App\Models\Installment;
use Filament\Support\RawJs;
use App\Models\IncomeReceipt;
use App\Models\InstallmentSale;
use Filament\Resources\Resource;
use App\Models\CompanyBankAccount;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\InstallmentSaleResource\Pages;
use App\Filament\Company\Resources\InstallmentSaleResource\RelationManagers;

class InstallmentSaleResource extends Resource
{
    protected static ?string $model = InstallmentSale::class;
    protected static ?string $navigationLabel = 'فروش اقساطی';
    protected static ?string $pluralLabel     = 'فروش‌های اقساطی';
    protected static ?string $label           = 'فروش اقساطی';
    protected static ?string $navigationGroup = 'فروش';
    protected static ?string $navigationIcon  = 'heroicon-o-chart-pie';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->label('فاکتور فروش')
                    ->options(
                        Invoice::where('type','sale')
                        ->where('company_id',auth()->user('company')->id)
                        ->get()
                        ->mapWithKeys(function ($invoice) {
                            return [$invoice->id => "فاکتور ". $invoice->number .' - '. number_format($invoice->total_amount).'ریال '];
                        })->toArray()
                        )
                    ->required(),
                Forms\Components\TextInput::make('prepayment')
                    ->label('مبلغ پیش‌پرداخت')
                    ->suffix('ریال')
                    ->default(0)
                    ->mask(RawJs::make(<<<'JS'
                    $money($input)
                    JS))
                    ->dehydrateStateUsing(function ($state) {
                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                    }),
                Forms\Components\TextInput::make('installment_count')
                    ->label('تعداد اقساط')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Forms\Components\TextInput::make('annual_interest_rate')
                    ->label('نرخ بهره سالانه')
                    ->suffix('%')
                    ->default(0)
                    ->mask(RawJs::make(<<<'JS'
                    $money($input)
                    JS))
                    ->dehydrateStateUsing(function ($state) {
                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                    })
                    ->rules([
                        function (Get $get) {
                            return function (string $attribute, $value, callable $fail) use ($get) {
                                // تبدیل مقدار به عدد
                                $quantity = self::cleanNumber($value);
                    
                                // چک کردن بزرگ‌تر از 100
                                if ($quantity >= 100) {
                                    $fail("نرخ بهره  باید کوچکتر از 100 باشد.");
                                }
                    
                    
                            };
                        },
                    ]),
                    Forms\Components\TextInput::make('payment_interval')
                    ->label('فاصله پرداختی (روز)')
                    ->numeric()
                    ->required()
                    ->default(30) // پیش‌فرض 30 روز
                    ->minValue(1)
                    ->helperText('فاصله بین هر قسط به روز (مثلاً 30 برای ماهانه)'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('تاریخ شروع اقساط')
                    ->jalali()
                    ->default(now())
                    ->required(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.number')->label('فاکتور فروش'),
                Tables\Columns\TextColumn::make('invoice.person.fullname') // اضافه کردن نام خریدار
                ->label('نام خریدار')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->label('مبلغ کل')->money('IRR'),
                Tables\Columns\TextColumn::make('prepayment')->label('پیش‌پرداخت')->money('IRR'),
                Tables\Columns\TextColumn::make('installment_count')->label('تعداد اقساط'),
                Tables\Columns\TextColumn::make('paid_installments')
                    ->label('اقساط پرداخت‌شده')
                    ->getStateUsing(fn(InstallmentSale $record) => $record->installments()->where('status', 'paid')->count()),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('باقیمانده')
                    ->money('IRR')
                    ->getStateUsing(fn(InstallmentSale $record) => $record->total_amount - $record->installments()->where('status', 'paid')->sum('amount') - $record->prepayment),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pay_installment')
                    ->color('success')
                    ->label('پرداخت قسط')
                    ->form([
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,

                        ])
                            ->schema([
                                Forms\Components\Select::make('installment_id')
                                ->label('قسط')
                                ->options(function (InstallmentSale $record) {
                                    return $record->installments()->where('status', 'pending')->pluck('due_date', 'id')->map(fn($date) => \Carbon\Carbon::parse($date)->toJalali()->format('Y/m/d'));
                                })
                                ->required(),
                            Forms\Components\Select::make('receivable_type')
                                ->label('روش پرداخت')
                                ->options([
                                    'App\Models\CompanyBankAccount' => 'حساب بانکی',
                                    'App\Models\PettyCash' => 'تنخواه',
                                    'App\Models\Fund' => 'صندوق',
                                    'App\Models\Check' => 'چک',
                                ])
                                ->live()
                                ->required(),
                            Forms\Components\TextInput::make('reference_number')
                                ->label('شماره ارجاع'),
                            Forms\Components\TextInput::make('commission')
                                ->label('کارمزد')
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(0),
                            Forms\Components\Select::make('receivable_id')
                                ->label('جزئیات')
                                ->live()
                                ->options(function (Get $get) {
                                    $receivableType = $get('receivable_type');
                                    return match ($receivableType) {
                                        'App\Models\CompanyBankAccount' => CompanyBankAccount::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                        'App\Models\PettyCash' => PettyCash::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                        'App\Models\Fund' => Fund::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                        default => [],
                                    };
                                })
                                ->hidden(fn(Get $get) => $get('receivable_type') === 'App\Models\Check' || !$get('receivable_type'))
                                ->required(fn(Get $get) => $get('receivable_type') !== 'App\Models\Check'),
                            Forms\Components\Fieldset::make('Check Details')
                                ->label('جزئیات چک')
                                ->hidden(fn(Get $get) => $get('receivable_type') !== 'App\Models\Check')
                                ->schema([
                                    Forms\Components\TextInput::make('serial_number')
                                        ->label('شماره صیاد')
                                        ->numeric()
                                        ->unique('checks')
                                        ->required(),
                                    Forms\Components\Select::make('bank')
                                        ->label('بانک')
                                        ->options(Bank::where('company_id',auth('company')->user()->id)->pluck('name', 'name')->toArray())
                                        ->required()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('add_bank')
                                                ->label('افزودن بانک')
                                                ->icon('heroicon-o-plus')
                                                ->modalHeading('ایجاد بانک جدید')
                                                ->action(function (array $data) {
                                                    $bank = Bank::create(['name' => $data['name']]);
                                                    return $bank->name;
                                                })
                                                ->form([
                                                    Forms\Components\TextInput::make('name')
                                                        ->label('نام بانک')
                                                        ->required(),
                                                ])
                                        ),
                                    Forms\Components\TextInput::make('branch')
                                        ->label('شعبه')
                                        ->required(),
                                    Forms\Components\DatePicker::make('date_received')
                                        ->label('تاریخ دریافت')
                                        ->jalali()
                                        ->required(),
                                    Forms\Components\DatePicker::make('due_date')
                                        ->label('تاریخ سررسید')
                                        ->jalali()
                                        ->afterOrEqual('date_received')
                                        ->required(),
                                ]),
                            ])
                       
                    ])
                    ->action(function (array $data, InstallmentSale $record) {
                        $installment = Installment::findOrFail($data['installment_id']);
                        $amount = $installment->amount;

                        if ($data['receivable_type'] === 'App\Models\Check') {
                            $check = Check::create([
                                'serial_number' => $data['serial_number'],
                                'payer' => null,
                                'bank' => $data['bank'],
                                'branch' => $data['branch'],
                                'amount' => $amount,
                                'date_received' => $data['date_received'],
                                'due_date' => $data['due_date'],
                                'status' => 'in_progress',
                                'type' => 'receivable',
                                'company_id' => auth()->user('company')->id,
                                'checkable_id' => $installment->id,
                                'checkable_type' => 'App\Models\Installment',
                            ]);
                            $data['receivable_id'] = $check->id;
                        }

                        $income = Income::create([
                            'income_category_id' => 1,
                            'amount' => $amount,
                            'description' => "پرداخت قسط فاکتور #{$record->invoice_id}",
                            // 'invoice_id' => $record->invoice_id,
                            'status' => 'received',
                        ]);

                        $receipt = IncomeReceipt::create([
                            'income_id' => $income->id,
                            'receivable_type' => $data['receivable_type'],
                            'receivable_id' => $data['receivable_id'],
                            'amount' => $amount,
                            'reference_number' => $data['reference_number'] ?? '',
                            'commission' => $data['commission'],
                        ]);

                        Transfer::create([
                            'accounting_auto' => 'auto',
                            'reference_number' => $data['reference_number'] ?? null,
                            'transfer_date' => $data['date_received'] ?? now(),
                            'amount' => $amount,
                            'description' => "پرداخت قسط فاکتور #{$record->invoice_id}",
                            'company_id' => auth()->user('company')->id,
                            'destination_type' => $data['receivable_type'],
                            'destination_id' => $data['receivable_id'],
                            'transaction_type' => 'payment',
                            'paymentable_type' => IncomeReceipt::class,
                            'paymentable_id' => $receipt->id,
                        ]);

                        $installment->update([
                            'status' => 'paid',
                            'income_id' => $income->id,
                        ]);

                        Notification::make()
                            ->title('پرداخت قسط با موفقیت ثبت شد')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('پرداخت قسط')
                    ->modalSubmitActionLabel('ثبت پرداخت')
                    ->visible(fn(InstallmentSale $record) => $record->installments()->where('status', 'pending')->exists()),
                    Tables\Actions\Action::make('view_installments')
                    ->label('جزئیات')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->modalHeading(fn (InstallmentSale $record) => "جزئیات اقساط فاکتور #{$record->invoice->id}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('بستن')
                    ->modalContent(fn (InstallmentSale $record): View => view(
                        'filament.resources.installment-sale.installments-modal',
                        ['installments' => $record->installments]
                    )),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstallmentSales::route('/'),
            'create' => Pages\CreateInstallmentSale::route('/create'),
            'edit' => Pages\EditInstallmentSale::route('/{record}/edit'),
        ];
    }

    public static function createRecord(array $data): InstallmentSale
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);
        $prepayment = (float) $data['prepayment'];
        $installmentCount = (int) $data['installment_count'];
        $annualInterestRate = (float) $data['annual_interest_rate'];
        $payment_interval = (float) $data['payment_interval'];
        $startDate = $data['start_date'];

        $principal = $invoice->total_amount - $prepayment;
        $monthlyInterestRate = $annualInterestRate / 12 / 100;
        if ($monthlyInterestRate > 0) {
            // محاسبه قسط با بهره
            $installmentAmountWithInterest = $principal * ($monthlyInterestRate * pow(1 + $monthlyInterestRate, $installmentCount)) / (pow(1 + $monthlyInterestRate, $installmentCount) - 1);
        } else {
            // اگه نرخ بهره صفر باشه، تقسیم ساده
            $installmentAmountWithInterest = $principal / $installmentCount;
        }
        $totalAmountWithInterest = $installmentAmountWithInterest * $installmentCount + $prepayment;

        $installmentSale = InstallmentSale::create([
            'invoice_id' => $invoice->id,
            'prepayment' => $prepayment,
            'installment_count' => $installmentCount,
            'annual_interest_rate' => $annualInterestRate,
            'start_date' => $startDate,
            'total_amount' => $totalAmountWithInterest,
        ]);
        if ($prepayment > 0) {
            $income = Income::create([
                'income_category_id' => 1,
                'amount' => $prepayment,
                'description' => "پیش‌پرداخت فاکتور #{$invoice->id}",
                // 'invoice_id' => $invoice->id,
                'status' => 'received',
            ]);

            $receipt = IncomeReceipt::create([
                'income_id' => $income->id,
                'receivable_type' => 'App\Models\Fund',
                'receivable_id' => 1,
                'amount' => $prepayment,
            ]);

            Transfer::create([
                'accounting_auto' => 'auto',
                'transfer_date' => now(),
                'amount' => $prepayment,
                'description' => "پیش‌پرداخت فاکتور #{$invoice->id}",
                'company_id' => auth()->user('company')->id,
                'destination_type' => 'App\Models\Fund',
                'destination_id' => 1,
                'transaction_type' => 'payment',
                'paymentable_type' => IncomeReceipt::class,
                'paymentable_id' => $receipt->id,
            ]);
        }

        $remainingAmount = $totalAmountWithInterest - $prepayment;
        $installmentBaseAmount = $remainingAmount / $installmentCount;
        $currentDate = \Carbon\Carbon::parse($startDate);

        for ($i = 0; $i < $installmentCount; $i++) {
                // dump($currentDate);
            Installment::create([
                'installment_sale_id' => $installmentSale->id,
                'amount' => $installmentBaseAmount,
                'due_date' => $currentDate->addDays($payment_interval)->toDateString(),
                'status' => 'pending',
            ]);
        }


        Notification::make()
            ->title('فروش اقساطی با موفقیت ثبت شد')
            ->success()
            ->send();

        return $installmentSale;
    }

    public static function cleanNumber($value)
    {
        return (float) str_replace(',', '', $value ?? 0);
    }
    protected static ?int $navigationSort = 6;


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('invoice', fn (Builder $query) => $query->where('company_id', auth()->user('company')->id));
    }
}
