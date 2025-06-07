<?php
namespace App\Filament\Company\Resources\InvoiceResource\Pages;

use App\Filament\Company\Resources\InvoiceResource;
use App\Models\Bank;
use App\Models\Check;
use App\Models\CompanyBankAccount;
use App\Models\Fund;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PettyCash;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as Act;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class Payments extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = InvoiceResource::class;
    protected static string $view = 'filament.company.resources.invoice-resource.pages.payments';

    public $record;

    protected static ?string $navigationLabel = 'پرداخت‌ها';
    protected static ?string $pluralLabel = 'پرداخت‌ها';
    protected static ?string $label = 'پرداخت';

    public function getTitle(): string
    {
        return 'مدیریت پرداخت‌ها';
    }

    public function mount(Invoice $record)
    {
        $this->record = $record;
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('هیچ پرداختی وجود ندارد')
            ->query(Payment::query()->where('payable_type','App\Models\Invoice')->where('payable_id', $this->record->id))
            ->columns([
                TextColumn::make('paymentable_type')
                    ->label('روش پرداخت')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'App\Models\CompanyBankAccount' => 'حساب بانکی',
                        'App\Models\PettyCash' => 'تنخواه',
                        'App\Models\Fund' => 'صندوق',
                        'App\Models\Check' => 'چک',
                        default => $state,
                    }),
                TextColumn::make('paymentable.name')
                    ->label('جزئیات پرداخت')
                    ->default('-'),
                TextColumn::make('amount')
                    ->label('مبلغ پرداختی')
                    ->money('irr', locale: 'fa')
                    ->summarize(Sum::make()),
                TextColumn::make('created_at_jalali')
                    ->label('تاریخ ثبت'),
            ])
            ->actions([
                EditAction::make()
                    ->label('ویرایش')
                    ->form(function (Payment $record) {
                        $isCheck = $record->paymentable_type === 'App\Models\Check';
                        $check = $isCheck ? $record->paymentable : null;

                        return [
                            Forms\Components\Fieldset::make('Payment Details')
                                ->label('جزئیات پرداخت')
                                ->schema([
                                    Select::make('paymentable_type')
                                        ->label('روش پرداخت')
                                        ->options([
                                            'App\Models\CompanyBankAccount' => 'حساب بانکی',
                                            'App\Models\PettyCash' => 'تنخواه',
                                            'App\Models\Fund' => 'صندوق',
                                            'App\Models\Check' => 'چک',
                                        ])
                                        ->default($record->paymentable_type)
                                        ->live()
                                        ->required()
                                        ->reactive(),
                                    TextInput::make('reference_number')
                                        ->label('شماره ارجاع')
                                        ->default($record->reference_number),
                                    TextInput::make('commission')
                                        ->label('کارمزد')
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->default($record->commission),
                                    Select::make('paymentable_id')
                                        ->label('جزئیات')
                                        ->live()
                                        ->options(function (Get $get) {
                                            $paymentableType = $get('paymentable_type');
                                            return match ($paymentableType) {
                                                'App\Models\CompanyBankAccount' => CompanyBankAccount::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                                'App\Models\PettyCash' => PettyCash::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                                'App\Models\Fund' => Fund::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                                default => [],
                                            };
                                        })
                                        ->default($record->paymentable_id)
                                        ->hint(function (Get $get) {
                                            $method = $get('paymentable_type');
                                            $accountId = $get('paymentable_id');
                                            if (!$method || !$accountId) {
                                                return '';
                                            }
                                            $modelClass = match ($method) {
                                                'App\Models\CompanyBankAccount' => CompanyBankAccount::class,
                                                'App\Models\PettyCash' => PettyCash::class,
                                                'App\Models\Fund' => Fund::class,
                                                default => null,
                                            };
                                            if ($modelClass) {
                                                $account = $modelClass::find($accountId);
                                                if ($account) {
                                                    $balance = $account->incomingTransfers()->sum('amount') - $account->outgoingTransfers()->sum('amount');
                                                    return "موجودی: " . number_format($balance) . " ریال";
                                                }
                                            }
                                            return '';
                                        })
                                        ->hidden(fn(Get $get) => $get('paymentable_type') === 'App\Models\Check' || !$get('paymentable_type'))
                                        ->required(fn(Get $get) => $get('paymentable_type') !== 'App\Models\Check'),
                                    TextInput::make('amount')
                                        ->label('مبلغ پرداختی')
                                        ->suffix('ریال')
                                        ->required()
                                        ->default($record->amount)
                                        ->rules([
                                            fn(Get $get) => function (string $attribute, $value, callable $fail) use ($get, $record) {
                                                $amount = (float) self::cleanNumber($value);
                                                if ($amount <= 0) {
                                                    $fail("مبلغ باید بزرگ‌تر از صفر باشد.");
                                                }
                                                $remainingAmount = $this->record->remaining_amount + $record->amount;
                                                if ($amount > $remainingAmount) {
                                                    $fail("مبلغ باقیمانده این فاکتور " . number_format($remainingAmount) . " ریال است.");
                                                }
                                            },
                                        ])
                                        
                                        ->live(onBlur: true)
                                        ->mask(RawJs::make('$money($input)'))
                                        ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state)),
                                ]),
                            Forms\Components\Fieldset::make('Check Details')
                                ->label('جزئیات چک')
                                ->hidden(fn(Get $get) => $get('paymentable_type') !== 'App\Models\Check')
                                ->schema([
                                    TextInput::make('serial_number')
                                        ->label('شماره صیاد')
                                        ->numeric()
                                        ->required()
                                        ->default($isCheck ? $check->serial_number : null),
                                    Select::make('bank')
                                        ->label('بانک')
                                        ->options(Bank::where('company_id',auth('company')->user()->id)->pluck('name', 'name')->toArray())
                                        ->required()
                                        ->default($isCheck ? $check->bank : null)
                                        ->suffixAction(
                                            Act::make('add_bank')
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
                                                ->after(function ($livewire) {
                                                    $livewire->dispatch('refreshForm');
                                                })
                                        ),
                                    TextInput::make('branch')
                                        ->label('شعبه')
                                        ->required()
                                        ->default($isCheck ? $check->branch : null),
                                    DatePicker::make('date_received')
                                        ->label('تاریخ دریافت')
                                        ->jalali()
                                        ->required()
                                        ->default($isCheck ? $check->date_received : null),
                                    DatePicker::make('due_date')
                                        ->label('تاریخ سررسید')
                                        ->jalali()
                                        ->afterOrEqual('date_received')
                                        ->required()
                                        ->default($isCheck ? $check->due_date : null),
                                ]),
                        ];
                    })
                    ->action(function (array $data, Payment $record) {
                        $data['amount'] = self::cleanNumber($data['amount']);
                        $originalAmount = $record->amount;
                        $originalPaymentableType = $record->paymentable_type;
                        $originalPaymentableId = $record->paymentable_id;

                        // مدیریت چک
                        if ($data['paymentable_type'] === 'App\Models\Check') {
                            if ($record->paymentable_type === 'App\Models\Check') {
                                $check = $record->paymentable;
                                $check->update([
                                    'serial_number' => $data['serial_number'],
                                    'bank' => $data['bank'],
                                    'branch' => $data['branch'],
                                    'amount' => $data['amount'],
                                    'date_received' => $data['date_received'],
                                    'due_date' => $data['due_date'],
                                ]);
                            } else {
                                $check = Check::create([
                                    'serial_number' => $data['serial_number'],
                                    'payer' => $this->record->person_id ?? null,
                                    'bank' => $data['bank'],
                                    'branch' => $data['branch'],
                                    'amount' => $data['amount'],
                                    'date_received' => $data['date_received'],
                                    'due_date' => $data['due_date'],
                                    'status' => 'in_progress',
                                    'type' => 'payable',
                                    'company_id' => auth()->user('company')->id,
                                    'checkable_id' => $this->record->id,
                                    'checkable_type' => 'App\Models\Invoice',
                                ]);
                                $data['paymentable_id'] = $check->id;
                            }
                        } else {
                            $account = $data['paymentable_type']::findOrFail($data['paymentable_id']);
                            $accountBalance = $account->incomingTransfers()->sum('amount') - $account->outgoingTransfers()->sum('amount');

                            // اگر روش پرداخت تغییر کرده، مبلغ قبلی رو به حساب قبلی برگردون
                            if ($originalPaymentableType !== $data['paymentable_type'] || $originalPaymentableId !== $data['paymentable_id']) {
                                if ($originalPaymentableType !== 'App\Models\Check') {
                                    $originalAccount = $originalPaymentableType::findOrFail($originalPaymentableId);
                                    $originalAccount->incomingTransfers()->create([
                                        'amount' => $originalAmount,
                                        'description' => 'بازگشت پرداخت فاکتور ' . $this->record->id . ' پس از تغییر روش پرداخت',
                                        'company_id' => auth()->user('company')->id,
                                    ]);
                                }
                            }

                            // مدیریت تفاوت مبلغ
                            $amountDifference = $data['amount'] - $originalAmount;
                            if ($amountDifference > 0) {
                                if ($accountBalance < $amountDifference) {
                                    Notification::make()
                                        ->title('موجودی کافی نیست')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                $account->outgoingTransfers()->create([
                                    'amount' => $amountDifference,
                                    'description' => 'تغییر مبلغ پرداخت فاکتور ' . $this->record->id,
                                    'company_id' => auth()->user('company')->id,
                                ]);
                            } elseif ($amountDifference < 0) {
                                $account->incomingTransfers()->create([
                                    'amount' => abs($amountDifference),
                                    'description' => 'بازگشت مبلغ اضافی پرداخت فاکتور ' . $this->record->id,
                                    'company_id' => auth()->user('company')->id,
                                ]);
                            }
                        }

                        $record->update([
                            'paymentable_type' => $data['paymentable_type'],
                            'paymentable_id' => $data['paymentable_id'],
                            'amount' => $data['amount'],
                            'reference_number' => $data['reference_number'] ?? '',
                            'commission' => $data['commission'],
                        ]);

                        Notification::make()
                            ->title('پرداخت با موفقیت ویرایش شد')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('ویرایش پرداخت')
                    ->modalSubmitActionLabel('ذخیره تغییرات')
                    ->modalCancelActionLabel('انصراف'),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        $amount = $record->amount;
                        $paymentableType = $record->paymentable_type;
                        $paymentableId = $record->paymentable_id;

                        if ($paymentableType === 'App\Models\Check') {
                            $record->paymentable->delete();
                        } else {
                            $account = $paymentableType::findOrFail($paymentableId);
                            $account->incomingTransfers()->create([
                                'amount' => $amount,
                                'description' => 'بازگشت پرداخت فاکتور ' . $this->record->id . ' پس از حذف',
                                'company_id' => auth()->user('company')->id,
                            ]);
                        }

                        $record->delete();
                        Notification::make()
                            ->title('پرداخت حذف شد')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('add_payment')
                    ->label('افزودن پرداخت')
                    ->modalHeading('ثبت پرداخت جدید')
                    ->form(function (Get $get, Set $set) {
                        return [
                            Repeater::make('payments')
                                ->label('روش‌های پرداخت')
                                ->schema([
                                    Select::make('paymentable_type')
                                        ->label('روش پرداخت')
                                        ->options([
                                            'App\Models\CompanyBankAccount' => 'حساب بانکی',
                                            'App\Models\PettyCash' => 'تنخواه',
                                            'App\Models\Fund' => 'صندوق',
                                            'App\Models\Check' => 'چک',
                                        ])
                                        ->live()
                                        ->required()
                                        ->reactive(),
                                    TextInput::make('reference_number')
                                        ->label('شماره ارجاع'),
                                    TextInput::make('commission')
                                        ->label('کارمزد')
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->default(0),
                                    Select::make('paymentable_id')
                                        ->label('جزئیات')
                                        ->live()
                                        ->options(function (Get $get) {
                                            $paymentableType = $get('paymentable_type');
                                            return match ($paymentableType) {
                                                'App\Models\CompanyBankAccount' => CompanyBankAccount::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                                'App\Models\PettyCash' => PettyCash::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                                'App\Models\Fund' => Fund::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                                default => [],
                                            };
                                        })
                                        ->hint(function (Get $get) {
                                            $method = $get('paymentable_type');
                                            $accountId = $get('paymentable_id');
                                            if (!$method || !$accountId) {
                                                return '';
                                            }
                                            $modelClass = match ($method) {
                                                'App\Models\CompanyBankAccount' => CompanyBankAccount::class,
                                                'App\Models\PettyCash' => PettyCash::class,
                                                'App\Models\Fund' => Fund::class,
                                                default => null,
                                            };
                                            if ($modelClass) {
                                                $account = $modelClass::find($accountId);
                                                if ($account) {
                                                    $balance = $account->incomingTransfers()->sum('amount') - $account->outgoingTransfers()->sum('amount');
                                                    return "موجودی: " . number_format($balance) . " ریال";
                                                }
                                            }
                                            return '';
                                        })
                                        ->hidden(fn(Get $get) => $get('paymentable_type') === 'App\Models\Check' || !$get('paymentable_type'))
                                        ->required(fn(Get $get) => $get('paymentable_type') !== 'App\Models\Check'),
                                    TextInput::make('amount')
                                        ->label('مبلغ پرداختی')
                                        ->suffix('ریال')
                                        ->required()
                                        ->rules([
                                            fn(Get $get) => function (string $attribute, $value, callable $fail) use ($get) {
                                                $amount = (float) self::cleanNumber($value);
                                                if ($amount <= 0) {
                                                    $fail("مبلغ باید بزرگ‌تر از صفر باشد.");
                                                }
                                                $totalPaid = collect($get('../../payments'))->sum(fn($item) => self::cleanNumber($item['amount'] ?? 0));
                                                $remainingAmount = $this->record->remaining_amount;
                                                if ($totalPaid > $remainingAmount) {
                                                    $fail("مجموع مبالغ پرداختی بیشتر از مبلغ باقیمانده فاکتور (" . number_format($remainingAmount) . " ریال) است.");
                                                }
                                            },
                                        ])
                                        ->default($this->record->remaining_amount)
                                        ->live(onBlur: true)
                                        ->mask(RawJs::make('$money($input)'))
                                        ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state)),
                                    Forms\Components\Fieldset::make('Check Details')
                                        ->label('جزئیات چک')
                                        ->hidden(fn(Get $get) => $get('paymentable_type') !== 'App\Models\Check')
                                        ->schema([
                                            TextInput::make('serial_number')
                                                ->label('شماره صیاد')
                                                ->numeric()
                                                ->required(),
                                            Select::make('bank')
                                                ->label('بانک')
                                                ->options(Bank::where('company_id',auth('company')->user()->id)->pluck('name', 'name')->toArray())
                                                ->required()
                                                ->suffixAction(
                                                    Act::make('add_bank')
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
                                                        ->after(function ($livewire) {
                                                            $livewire->dispatch('refreshForm');
                                                        })
                                                ),
                                            TextInput::make('branch')
                                                ->label('شعبه')
                                                ->required(),
                                            DatePicker::make('date_received')
                                                ->label('تاریخ دریافت')
                                                ->jalali()
                                                ->required(),
                                            DatePicker::make('due_date')
                                                ->label('تاریخ سررسید')
                                                ->jalali()
                                                ->afterOrEqual('date_received')
                                                ->required(),
                                        ]),
                                ])
                                ->columns(2)
                                ->defaultItems(1)
                                ->addable(false),
                        ];
                    })
                    ->action(function (array $data) {
                        $totalPaid = 0;
                        foreach ($data['payments'] as $paymentData) {
                            $paymentData['amount'] = self::cleanNumber($paymentData['amount']);
                            $totalPaid += $paymentData['amount'];

                            if ($paymentData['paymentable_type'] === 'App\Models\Check') {
                                $check = Check::create([
                                    'serial_number' => $paymentData['serial_number'],
                                    'payer' => $this->record->person_id ?? null,
                                    'bank' => $paymentData['bank'],
                                    'branch' => $paymentData['branch'],
                                    'amount' => $paymentData['amount'],
                                    'date_received' => $paymentData['date_received'],
                                    'due_date' => $paymentData['due_date'],
                                    'status' => 'in_progress',
                                    'type' => 'payable',
                                    'company_id' => auth()->user('company')->id,
                                    'checkable_id' => $this->record->id,
                                    'checkable_type' => 'App\Models\Invoice',
                                ]);
                                $paymentData['paymentable_id'] = $check->id;
                            } else {
                                $account = $paymentData['paymentable_type']::findOrFail($paymentData['paymentable_id']);
                                $accountBalance = $account->incomingTransfers()->sum('amount') - $account->outgoingTransfers()->sum('amount');
                                if ($accountBalance < $paymentData['amount']) {
                                    Notification::make()
                                        ->title('موجودی کافی نیست برای ' . $account->name)
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                // $account->outgoingTransfers()->create([
                                //     'amount' => $paymentData['amount'],
                                //     'description' => 'پرداخت فاکتور ' . $this->record->id,
                                //     'company_id' => auth()->user('company')->id,
                                // ]);
                            }

                            Payment::create([
                                'payable_type' => 'App\Models\Invoice',
                                'payable_id' => $this->record->id,
                                'paymentable_type' => $paymentData['paymentable_type'],
                                'paymentable_id' => $paymentData['paymentable_id'],
                                'amount' => $paymentData['amount'],
                                'reference_number' => $paymentData['reference_number'] ?? '',
                                'commission' => $paymentData['commission'],
                                'type' => 'payment',
                            ]);
                        }

                        Notification::make()
                            ->title('پرداخت با موفقیت ثبت شد')
                            ->success()
                            ->send();
                    })
                    ->modalSubmitActionLabel('ثبت پرداخت')
                    ->modalCancelActionLabel('انصراف'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function cleanNumber($value)
    {
        return (float) str_replace(',', '', $value ?? 0);
    }
}