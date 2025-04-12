<?php
namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\ExpenseResource\Pages;
use App\Models\AccountingCategory;
use App\Models\AccountingTransaction;
use App\Models\Bank;
use App\Models\Check;
use App\Models\CompanyBankAccount;
use App\Models\Expense;
use App\Models\Fund;
use App\Models\Payment;
use App\Models\PettyCash;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Illuminate\View\View;

class ExpenseResource extends Resource
{
    protected static ?string $model           = Expense::class;
    protected static ?string $navigationLabel = 'هزینه‌ها';
    protected static ?string $pluralLabel     = 'هزینه‌ها';
    protected static ?string $label           = 'هزینه';
    protected static ?string $navigationGroup = 'خرید';
    protected static ?string $navigationIcon  = 'heroicon-o-currency-dollar';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 3])
                    ->schema([
                        Radio::make('accounting_auto')
                            ->label('نحوه ورود شماره فاکتور')
                            ->options(['auto' => 'اتوماتیک', 'manual' => 'دستی'])
                            ->default('auto')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $expense = Expense::withTrashed()->latest()->first();
                                $id      = $expense ? (++$expense->id) : 1;
                                $state === 'auto' ? $set('number', (int) $id) : $set('number', '');
                            })
                            ->inline()
                            ->inlineLabel(false),
                        Forms\Components\TextInput::make('number')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->label('شماره فاکتور')
                            ->required()
                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                            ->default(function (Get $get) {
                                $expense = Expense::withTrashed()->latest()->first();
                                $id      = $expense ? (++$expense->id) : 1;
                                return ($get('accounting_auto') == 'auto') ? (int) $id : '';
                            })
                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                return $rule->where('deleted_at', null);
                            })
                            ->live()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')
                            ->label('تاریخ')
                            ->jalali()
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('description')
                            ->label('عنوان')
                            ->required()
                            ->columnSpanFull(),
                        Repeater::make('items')
                            ->label('جزئیات هزینه')
                        // ->addable(false)
                            ->relationship('items')
                            ->columnSpanFull()
                            ->columns(2)
                            ->deleteAction(fn($action) => $action->hidden(fn($state) => count($state) <= 1))
                            ->schema([
                                Grid::make(['default' => 2])
                                    ->schema([
                                        SelectTree::make('accounting_category_id')
                                        // ->required()
                                            ->label('دسته پدر')
                                            ->relationship('category', 'title', 'parent_id')
                                            ->searchable()
                                            ->enableBranchNode()
                                            ->placeholder('انتخاب دسته')
                                            ->withCount()
                                            ->searchable()
                                            ->emptyLabel('بدون نتیجه'),
                                        Forms\Components\TextInput::make('amount')
                                            ->label('مبلغ')
                                            ->required()
                                            ->mask(RawJs::make(<<<'JS'
                                                $money($input)
                                            JS))
                                            ->hidden(fn($context)=>$context==='edit')
                                            ->dehydrateStateUsing(function($state){
                                                return(float)str_replace(',','',$state);
                                            })
                                            ->suffix('ریال'),
                                        Forms\Components\Textarea::make('description')
                                            ->label('شرح')
                                            ->columnSpanFull()
                                            ->nullable(),
                                    ]),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('شماره'),

                Tables\Columns\TextColumn::make('description')->label('عنوان')->default('-'),
                Tables\Columns\TextColumn::make('category')
                    ->label('دسته')
                    ->getStateUsing(function (Expense $record) {
                        $firstItem = $record->items->first();
                        // dump($firstItem->created_at);
                        return $firstItem && $firstItem->accounting_category_id ? $firstItem->category->title : '-';
                    })
                    ->color('danger')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('مبلغ کل')
                    ->money('IRR')
                    ->getStateUsing(fn(Expense $record) => $record->items->sum('amount')),
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('پرداخت شده')
                    ->money('IRR')
                    ->getStateUsing(fn(Expense $record) => $record->payments()->sum('amount')),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('باقیمانده')
                    ->money('IRR')
                    ->getStateUsing(fn(Expense $record) => $record->total_amount - $record->payments()->sum('amount')),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(function ($state) {
                        if ($state == 'pending') {
                            return 'منتظر پرداخت';
                        } elseif ($state == 'paid') {
                            return 'پرداخت شده';
                        }
                    })
                    ->color(function ($state) {
                        if ($state == 'pending') {
                            return 'warning';
                        } elseif ($state == 'paid') {
                            return 'success';
                        }
                    }),
                Tables\Columns\TextColumn::make('date_jalali')->label('تاریخ'),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                // اضافه کردن فیلتر دسته
                Tables\Filters\SelectFilter::make('category')
                    ->label('دسته‌بندی')
                    ->options(function () {
                        return AccountingCategory::pluck('title', 'id')->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('items', function ($query) use ($data) {
                                $query->where('accounting_category_id', $data['value']);
                            });
                        }
                    })
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pay')
                    ->label('پرداخت')
                    ->form([

                        Repeater::make('payments')
                            ->addable(false)
                            ->label('روش‌های پرداخت')
                            ->schema([
                                Forms\Components\Select::make('paymentable_type')
                                    ->label('روش پرداخت')
                                    ->options([
                                        'App\Models\CompanyBankAccount' => 'حساب بانکی',
                                        'App\Models\PettyCash'          => 'تنخواه',
                                        'App\Models\Fund'               => 'صندوق',
                                        'App\Models\Check'              => 'چک',
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
                                Forms\Components\Select::make('paymentable_id')
                                    ->label('جزئیات')
                                    ->live()
                                    ->options(function (Get $get) {
                                        $paymentableType = $get('paymentable_type');
                                        return match ($paymentableType) {
                                            'App\Models\CompanyBankAccount' => CompanyBankAccount::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                            'App\Models\PettyCash'          => PettyCash::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                            'App\Models\Fund'               => Fund::where('company_id', auth()->user('company')->id)->pluck('name', 'id')->toArray(),
                                            default                         => [],
                                        };
                                    })
                                    ->hint(function (Get $get) {
                                        $method    = $get('paymentable_type');
                                        $accountId = $get('paymentable_id');
                                        if (! $method || ! $accountId) {
                                            return '';
                                        }
                                        $modelClass = match ($method) {
                                            'App\Models\CompanyBankAccount' => CompanyBankAccount::class,
                                            'App\Models\PettyCash'          => PettyCash::class,
                                            'App\Models\Fund'               => Fund::class,
                                            default                         => null,
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
                                    ->hidden(fn(Get $get) => $get('paymentable_type') === 'App\Models\Check' || ! $get('paymentable_type'))
                                    ->required(fn(Get $get) => $get('paymentable_type') !== 'App\Models\Check'),
                                Forms\Components\TextInput::make('amount')
                                    ->label('مبلغ پرداختی')
                                    ->suffix('ریال')
                                    ->default(fn(Expense $record) => $record->total_amount - $record->payments()->sum('amount'))
                                    ->required()
                                    ->rules([
                                        fn(Get $get, Expense $record) => function (string $attribute, $value, callable $fail) use ($get, $record) {
                                            $amount = (float) str_replace(',', '', $value);
                                            if ($amount <= 0) {
                                                $fail("مبلغ باید بزرگ‌تر از صفر باشد.");
                                            }
                                            $totalPaid       = collect($get('../../payments'))->sum(fn($item) => (float) str_replace(',', '', $item['amount'] ?? 0));
                                            $remainingAmount = $record->total_amount - $record->payments()->sum('amount');
                                            if ($totalPaid > $remainingAmount) {
                                                $fail("مجموع مبالغ پرداختی بیشتر از مبلغ باقیمانده هزینه (" . number_format($remainingAmount) . " ریال) است.");
                                            }
                                        },
                                    ])
                                    ->live(onBlur: true)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state)),
                                Forms\Components\Fieldset::make('Check Details')
                                    ->label('جزئیات چک')
                                    ->hidden(fn(Get $get) => $get('paymentable_type') !== 'App\Models\Check')
                                    ->schema([
                                        Forms\Components\TextInput::make('serial_number')
                                            ->label('شماره صیاد')
                                            ->numeric()
                                            ->unique('checks')
                                            ->required(),
                                        Forms\Components\Select::make('bank')
                                            ->label('بانک')
                                            ->options(Bank::all()->pluck('name', 'name')->toArray())
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
                            ->columns(2)
                            ->defaultItems(1),
                    ])
                    ->action(function (array $data, Expense $record) {
                        $totalPaid = 0;
                        foreach ($data['payments'] as $paymentData) {
                            $paymentData['amount'] = (float) str_replace(',', '', $paymentData['amount']);
                            $totalPaid += $paymentData['amount'];

                            if ($paymentData['paymentable_type'] === 'App\Models\Check') {
                                $check = Check::create([
                                    'serial_number'  => $paymentData['serial_number'],
                                    'payer'          => null, // اگر هزینه به شخص خاصی وصل نیست، می‌تونید این رو تغییر بدید
                                    'bank'           => $paymentData['bank'],
                                    'branch'         => $paymentData['branch'],
                                    'amount'         => $paymentData['amount'],
                                    'date_received'  => $paymentData['date_received'],
                                    'due_date'       => $paymentData['due_date'],
                                    'status'         => 'in_progress',
                                    'type'           => 'payable',
                                    'company_id'     => auth()->user('company')->id,
                                    'checkable_id'   => $record->id,
                                    'checkable_type' => 'App\Models\Expense',
                                ]);
                                $paymentData['paymentable_id'] = $check->id;
                            } else {
                                $account        = $paymentData['paymentable_type']::findOrFail($paymentData['paymentable_id']);
                                $accountBalance = $account->incomingTransfers()->sum('amount') - $account->outgoingTransfers()->sum('amount');
                                if ($accountBalance < $paymentData['amount']) {
                                    Notification::make()
                                        ->title('موجودی کافی نیست برای ' . $account->name)
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                $account->outgoingTransfers()->create([
                                    'amount'      => $paymentData['amount'],
                                    'description' => 'پرداخت هزینه ' . $record->number,
                                    'company_id'  => auth()->user('company')->id,
                                ]);
                            }

                            $payment = Payment::create([
                                'payable_type'     => 'App\Models\Expense',
                                'payable_id'       => $record->id,
                                'paymentable_type' => $paymentData['paymentable_type'],
                                'paymentable_id'   => $paymentData['paymentable_id'],
                                'amount'           => $paymentData['amount'],
                                'reference_number' => $paymentData['reference_number'] ?? '',
                                'commission'       => $paymentData['commission'],
                            ]);

                            // ثبت تراکنش حسابداری
                            $accountId = match ($paymentData['paymentable_type']) {
                                'App\Models\CompanyBankAccount' => 'App\Models\CompanyBankAccount', // حساب بانکی
                                'App\Models\PettyCash'          => 'App\Models\PettyCash',          // تنخواه
                                'App\Models\Fund'               => 'App\Models\Fund',               // صندوق
                                'App\Models\Check'              => 'App\Models\Check',              // چک
                                default                         => throw new \Exception('روش پرداخت نامعتبر'),
                            };

                            AccountingTransaction::create([
                                'expense_id' => $record->id,
                                'account_id' => $accountId,
                                'amount'     => -$paymentData['amount'], // منفی برای خروج وجه
                                'date'       => now(),
                            ]);
                        }

                        if ($record->total_amount <= $record->payments()->sum('amount')) {
                            $record->update(['status' => 'paid']);
                        }

                        Notification::make()
                            ->title('پرداخت با موفقیت ثبت شد')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('پرداخت هزینه')
                    ->modalSubmitActionLabel('ثبت پرداخت')
                    ->visible(fn(Expense $record) => $record->status !== 'paid'),
                Tables\Actions\Action::make('details')
                    ->label('جزئیات')
                    ->color('warning')
                    ->icon('heroicon-o-information-circle')
                    ->modalHeading(fn(Expense $record) => 'جزئیات  شماره ' . $record->number)
                    ->modalContent(function (Expense $record) {
                        $payments = $record->payments()->with('paymentable')->get();
                        $items = $record->items()->get(); // دریافت آیتم‌ها

                        // if ($payments->isEmpty()) {
                        //     return view('filament.components.no-payments', ['message' => 'پرداختی برای این هزینه ثبت نشده است.']);
                        // }

                        return view('filament.components.payment-details', [
                            'payments' => $payments,
                            'items' => $items,
                        ]);
                    })
                    ->modalSubmitAction(false) // دکمه ثبت را حذف می‌کنیم چون فقط نمایش است
                    ->modalCancelActionLabel('بستن'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit'   => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
    protected static ?int $navigationSort = 5;
}
