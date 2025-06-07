<?php
namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Bank;
use App\Models\Fund;
use Filament\Tables;
use App\Models\Check;
use App\Models\Income;
use Filament\Forms\Get;
use App\Models\Transfer;
use Filament\Forms\Form;
use App\Models\PettyCash;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\IncomeReceipt;
use App\Models\IncomeCategory;
use Filament\Resources\Resource;
use App\Models\CompanyBankAccount;
use App\Models\AccountingTransaction;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use App\Filament\Company\Resources\IncomeResource\Pages;

class IncomeResource extends Resource
{
    protected static ?string $model = Income::class;

    protected static ?string $navigationIcon  = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'درآمدها';
    protected static ?string $pluralLabel     = 'درآمدها';
    protected static ?string $label           = 'درآمد';
    protected static ?string $navigationGroup = 'فروش';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                SelectTree::make('income_category_id')
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
                    ->mask(RawJs::make(<<<'JS'
                        $money($input)
                    JS))
                    ->hidden(fn($context)=>$context==='edit')
                    ->dehydrateStateUsing(function($state){
                        return(float)str_replace(',','',$state);
                    })
                    ->required()
                    ->suffix('ریال'),
                Forms\Components\Textarea::make('description')
                    ->label('شرح')
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                ->label('دسته')
                ->getStateUsing(function (Income $record) {
                    // dump($firstItem->created_at);
                    return $record && $record->income_category_id ? $record->category->title : '-';
                })
                ->color('danger')
                ->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('مبلغ')->money('IRR'),
                Tables\Columns\TextColumn::make('description')->label('شرح')->default('-'),
                Tables\Columns\TextColumn::make('total_received')->label('دریافت شده')->money('IRR'),
                Tables\Columns\TextColumn::make('remaining_amount')->label('باقیمانده')->money('IRR'),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(function ($state) {
                        if ($state == 'pending') {
                            return 'منتظر دریافت';
                        } else {
                            return 'دریافت شد';

                        }
                    })
                    ->color(function ($state) {
                        if ($state == 'pending') {
                            return 'warning';
                        } else {
                            return 'success';

                        }
                    }),
            ])
            ->defaultSort('created_at','desc')
            ->filters([
                // اضافه کردن فیلتر دسته
                Tables\Filters\SelectFilter::make('category')
                    ->label('دسته‌بندی')
                    ->options(function () {
                        return IncomeCategory::pluck('title', 'id')->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('receipts', function ($query) use ($data) {
                                $query->where('income_category_id', $data['value']);
                            });
                        }
                    })
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('receive')
                    ->label('دریافت')
                    ->color('warning')
                    ->form([
                        Repeater::make('receipts')
                            // ->addable(false)
                            ->label('روش‌های دریافت')
                            ->schema([
                                Forms\Components\Select::make('receivable_type')
                                    ->label('روش دریافت')
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
                                    ->hint(function (Get $get) {
                                        $method = $get('receivable_type');
                                        $accountId = $get('receivable_id');
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
                                    ->hidden(fn(Get $get) => $get('receivable_type') === 'App\Models\Check' || !$get('receivable_type'))
                                    ->required(fn(Get $get) => $get('receivable_type') !== 'App\Models\Check'),
                                Forms\Components\TextInput::make('amount')
                                    ->label('مبلغ دریافتی')
                                    ->suffix('ریال')
                                    ->default(fn(Income $record) => $record->remaining_amount)
                                    ->required()
                                    ->rules([
                                        fn(Get $get, Income $record) => function (string $attribute, $value, callable $fail) use ($get, $record) {
                                            $amount = (float) str_replace(',', '', $value);
                                            if ($amount <= 0) {
                                                $fail("مبلغ باید بزرگ‌تر از صفر باشد.");
                                            }
                                            $totalReceived = collect($get('../../receipts'))->sum(fn($item) => (float) str_replace(',', '', $item['amount'] ?? 0));
                                            $remainingAmount = $record->remaining_amount;
                                            if ($totalReceived > $remainingAmount) {
                                                $fail("مجموع مبالغ دریافتی بیشتر از مبلغ باقیمانده درآمد (" . number_format($remainingAmount) . " ریال) است.");
                                            }
                                        },
                                    ])
                                    ->live(onBlur: true)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state)),
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
                            ->columns(2)
                            ->defaultItems(1),
                    ])
                    ->action(function (array $data, Income $record) {
                        $totalReceived = 0;
                        foreach ($data['receipts'] as $receiptData) {
                            $receiptData['amount'] = (float) str_replace(',', '', $receiptData['amount']);
                            $totalReceived += $receiptData['amount'];

                            if ($receiptData['receivable_type'] === 'App\Models\Check') {
                                $check = Check::create([
                                    'serial_number' => $receiptData['serial_number'],
                                    'payer' => null, // اگر درآمد از شخص خاصی نیست، می‌تونید تغییر بدید
                                    'bank' => $receiptData['bank'],
                                    'branch' => $receiptData['branch'],
                                    'amount' => $receiptData['amount'],
                                    'date_received' => $receiptData['date_received'],
                                    'due_date' => $receiptData['due_date'],
                                    'status' => 'in_progress',
                                    'type' => 'receivable', // چک دریافتی
                                    'company_id' => auth()->user('company')->id,
                                    'checkable_id' => $record->id,
                                    'checkable_type' => 'App\Models\Income',
                                ]);
                                $receiptData['receivable_id'] = $check->id;
                            } else {
                                $account = $receiptData['receivable_type']::findOrFail($receiptData['receivable_id']);
                                $account->incomingTransfers()->create([
                                    'amount' => $receiptData['amount'],
                                    'description' => 'دریافت درآمد #' . $record->id,
                                    'company_id' => auth()->user('company')->id,
                                    'transaction_type' => 'payment',
                                    'destination_type' => $receiptData['receivable_type'],
                                    'destination_id' => $receiptData['receivable_id'],
                                ]);
                            }

                            $receipt = IncomeReceipt::create([
                                'income_id' => $record->id,
                                'receivable_type' => $receiptData['receivable_type'],
                                'receivable_id' => $receiptData['receivable_id'],
                                'amount' => $receiptData['amount'],
                                'reference_number' => $receiptData['reference_number'] ?? '',
                                'commission' => $receiptData['commission'],
                                
                            ]);

                            // ثبت تراکنش در جدول transfers
                            // Transfer::create([
                            //     'accounting_auto' => 'auto',
                            //     'reference_number' => $receiptData['reference_number'] ?? null,
                            //     'transfer_date' => $receiptData['date_received'] ?? now(), // استفاده از تاریخ دریافت چک
                            //     'amount' => $receiptData['amount'],
                            //     'description' => "دریافت درآمد #{$record->id}",
                            //     'company_id' => auth()->user('company')->id,
                            //     'destination_type' => $receiptData['receivable_type'],
                            //     'destination_id' => $receiptData['receivable_id'],
                            //     'transaction_type' => 'payment',
                            //     'paymentable_type' => IncomeReceipt::class,
                            //     'paymentable_id' => $receipt->id,
                            // ]);
                        }
                        // dd($record->remaining_amount);
                        // if ($record->remaining_amount <= 0) {
                        //     $record->update(['status' => 'received']);
                        // }
                        if ($record->total_received <= $record->receipts()->sum('amount')) {
                            $record->update(['status' => 'received']);
                        }

                        Notification::make()
                            ->title('دریافت با موفقیت ثبت شد')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('دریافت درآمد')
                    ->modalSubmitActionLabel('ثبت دریافت')
                    ->visible(fn(Income $record) => $record->status !== 'received'),

                    // Detail Button
                    Tables\Actions\Action::make('details')
                    ->label('جزئیات')
                    ->color('warning')
                    ->icon('heroicon-o-information-circle')
                    ->modalHeading(fn(Income $record) => 'جزئیات')
                    ->modalContent(function (Income $record) {
                        $receives = $record->receipts()->with('receivable')->get();
                        // $items = $record->items()->get(); // دریافت آیتم‌ها
                        // if ($payments->isEmpty()) {
                        //     return view('filament.components.no-payments', ['message' => 'پرداختی برای این هزینه ثبت نشده است.']);
                        // }

                        return view('filament.components.receives-details', [
                            'receives' => $receives,
                        ]);
                    })
                    ->modalSubmitAction(false) // دکمه ثبت را حذف می‌کنیم چون فقط نمایش است
                    ->modalCancelActionLabel('بستن'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIncomes::route('/'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit'   => Pages\EditIncome::route('/{record}/edit'),
        ];
    }
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
    }
}
