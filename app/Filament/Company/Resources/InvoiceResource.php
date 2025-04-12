<?php

namespace App\Filament\Company\Resources;

use Closure;
use Filament\Forms;
use Filament\Tables;
use App\Models\Store;
use App\Models\Person;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Illuminate\View\View;
use App\Models\PersonType;
use Filament\Tables\Table;
use App\Models\InvoiceItem;
use App\Models\ProductUnit;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Filament\Exports\InvoiceItemExporter;
use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action as Act;
use App\Filament\Company\Resources\InvoiceResource\Pages;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationLabel = 'فاکتور خرید';
    protected static ?string $pluralLabel = 'فاکتورها';
    protected static ?string $label = 'فاکتور خرید';
    protected static ?string $navigationGroup = 'خرید';
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';


    public static function getEloquentQuery(): Builder
    {
        // return parent::getEloquentQuery()
        //     ->where('company_id', auth()->user('company')->id)
        //     ->where('type', 'purchase');
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user('company')->id);
    }

    public static function form(Form $form): Form
    {
        $supplier = PersonType::where('title', 'تامین کننده')->first();
        return $form
            ->schema([
                Grid::make(['default' => 3])
                    ->schema([
                        Radio::make('accounting_auto')
                            ->label('نحوه ورود شماره فاکتور')
                            ->options(['auto' => 'اتوماتیک', 'manual' => 'دستی'])
                            ->default('auto')
                            ->live()
                            ->afterStateUpdated(
                                function($state, callable $set){
                                    $invoice = Invoice::withTrashed()->latest()->first();
                                    $id = $invoice ? (++$invoice->id) : 1;
                                    $state === 'auto' ? $set('number', (int)$id) : $set('number', '');
                                }
                            )
                            ->inline()
                            ->inlineLabel(false),
                        Forms\Components\TextInput::make('number')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->label('شماره فاکتور')
                            ->required()
                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                            ->default(
                                function (Get $get) {
                                    $invoice = Invoice::withTrashed()->latest()->first();
                                    $id = $invoice ? (++$invoice->id) : 1;
                                    return ($get('accounting_auto') == 'auto') ? (int)$id : '';
                                }
                            )
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

                        Forms\Components\TextInput::make('title')
                            ->label('عنوان'),
                        Forms\Components\Select::make('person_id')
                            ->label('تأمین‌کننده')
                            ->searchable(['firstname','lastname'])
                            ->relationship('person', 'fullname')
                            ->options(Person::whereHas('types', fn($query) => $query->where('title', 'تامین کننده'))
                                ->pluck('fullname', 'id'))
                            ->required()
                            ->live()
                            ->suffixAction(
                                Act::make('add_Store')
                                    ->label('افزودن تامین کننده')
                                    ->icon('heroicon-o-plus')
                                    ->modalHeading('ایجاد تامین کننده جدید')
                                    ->action(function (array $data, Set $set, $livewire) {
                                        $person = Person::create([
                                            'firstname' => $data['firstname'],
                                            'lastname' => $data['lastname'],
                                            'accounting_auto' => $data['accounting_auto'],
                                            'accounting_code' => $data['accounting_code'],
                                            'company_id' => auth()->user('company')->id,
                                        ]);
                                        // dd($data);

                                        // اتصال PersonTypeها به Person
                                        $person->types()->attach($data['types']);

                                        // Create Account
                                            $account = Account::create([
                                                'code' => $person->accounting_code,
                                                'name' => 'حساب تأمین‌کننده ' . $person->fullname,
                                                'type' => 'liability',
                                                'company_id' => auth()->user('company')->id,
                                                'balance' => 0,
                                            ]);
                                            $person->update(['account_id' => $account->id]);

                                        // return $person->id; // برای آپدیت سلکت‌باکس
                                        // آپدیت سلکت‌باکس و انتخاب تامین‌کننده جدید
                                        $set('person_id', $person->id);

                                        // ارسال رویداد برای رفرش گزینه‌ها
                                        $livewire->dispatch('refresh-person-options');
                                    })
                                    ->form([
                                        Radio::make('accounting_auto')
                                            ->label('نحوه ورود کد حسابداری')
                                            ->options([
                                                'auto' => 'اتوماتیک',
                                                'manual' => 'دستی',
                                            ])
                                            ->default('auto')
                                            ->live()
                                            ->afterStateUpdated(
                                                function($state, callable $set){
                                                    $person = Person::withTrashed()->latest()->first();
                                                    $id = $person ? (++$person->id) : 1;
                                                    $state === 'auto' ? $set('accounting_code', $id) : $set('accounting_code', '');
                                                }
                                            )
                                            ->inline()
                                            ->inlineLabel(false),
                                        Forms\Components\TextInput::make('accounting_code')
                                            ->extraAttributes(['style' => 'direction:ltr'])
                                            ->label('کد حسابداری')
                                            ->required()
                                            ->afterStateHydrated(function (Get $get) {
                                                $person = Person::withTrashed()->latest()->first();
                                                $id = $person ? (++$person->id) : 1;
                                                return ($get('accounting_auto') == 'auto') ? (int)$id : '';
                                            })
                                            ->default(
                                                function (Get $get) {
                                                    $person = Person::withTrashed()->latest()->first();
                                                    $id = $person ? (++$person->id) : 1;
                                                    // dd($id);
                                                    return ($get('accounting_auto') == 'auto') ? (int)$id : '';
                                                }
                                            )
                                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                                return $rule->where('deleted_at', null);
                                            })
                                            ->live()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('firstname')
                                            ->label('نام')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('lastname')
                                            ->label('نام خانوادگی')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('types')
                                            ->label('نوع')
                                            ->options(PersonType::all()->pluck('title','id'))
                                            ->preload()
                                            ->multiple()
                                            ->required()
                                            ->live()
                                    ])
                                    ->after(function ($livewire) {
                                        $livewire->dispatch('refreshForm'); // رفرش فرم بعد از اضافه کردن
                                    })
                            ),
                        Forms\Components\Select::make('store_id')
                            ->label('انبار')
                            ->relationship('store', 'title')
                            ->live()
                            ->options(Store::where('company_id', auth()->user('company')->id)->pluck('title', 'id'))
                            ->suffixAction(
                                Act::make('add_Store')
                                    ->label('اضافه کردن انبرا')
                                    ->icon('heroicon-o-plus') // آیکون دلخواه
                                    ->modalHeading('ایجاد انبرا جدید')
                                    ->action(function (array $data) {
                                        $unit = Store::create([
                                            'title' => $data['title'],
                                            'phone_number' => $data['phone_number'],
                                            'address' => $data['address'],
                                            'company_id' => auth()->user('company')->id,
                                        ]);
                                        return $unit->id; // برای آپدیت سلکت‌باکس
                                    })
                                    ->form([
                                        Forms\Components\TextInput::make('title')
                                            ->label('عنوان')
                                           
                                            ->required()
                                            ->maxLength(255),


                                        Forms\Components\TextInput::make('phone_number')
                                            ->label('شماره تلفن')
                                            ->required()
                                            ->extraAttributes(['style' => 'direction:ltr'])
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('address')
                                            ->label('آدرس')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->after(function ($livewire) {
                                        $livewire->dispatch('refreshForm'); // رفرش فرم بعد از اضافه کردن
                                    })
                            )
                            ->required(),
                    ]),
                Forms\Components\Repeater::make('items')
                    ->label('')
                    ->relationship()
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addable(true)
                    ->deleteAction(fn($action) => $action->hidden(fn($state) => count($state) <= 1))
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'md' => 4, 'lg' => 7])
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('محصول')
                                    ->searchable()
                                    ->placeholder('انتخاب')
                                    ->loadingMessage('در حال لود ...')
                                    ->options(Product::where('company_id', auth()->user('company')->id)->pluck('name', 'id'))
                                    ->required()
                                    ->searchPrompt('تایپ کنید ...')
                                    ->noSearchResultsMessage('بدون نتیجه!')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $product = Product::find($state);
                                        $set('unit', $product ? $product->product_unit_id : null);
                                    })
                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                        // توی حالت ویرایش، وقتی فرم لود می‌شه، واحد رو از دیتابیس یا محصول تنظیم می‌کنیم
                                        if ($record && $record->unit) {
                                            $set('unit', $record->unit); // استفاده از مقدار ذخیره‌شده توی InvoiceItem
                                        } else {
                                            $product = Product::find($state);
                                            $set('unit', $product ? $product->product_unit_id : null); // گرفتن از محصول
                                        }
                                    }),
                                Forms\Components\TextInput::make('description')
                                    ->label('شرح')
                                    ->hidden(),
                                Forms\Components\Select::make('unit')
                                    ->label('واحد')
                                    ->options(ProductUnit::pluck('name', 'id'))

                                    ->disabled()
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('تعداد')
                                    ->required()
                                    ->default(0)
                                    ->rules([
                                        function (Get $get, $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                                // تبدیل مقدار به عدد
                                                $quantity = self::cleanNumber($value);

                                                // چک کردن بزرگ‌تر از صفر
                                                if ($quantity <= 0) {
                                                    $fail("تعداد باید بزرگ‌تر از صفر باشد.");
                                                }

                                                // $productId = $get('product_id');
                                                // $storeId = $get('../../store_id');
                                                // $store = Store::find($storeId);
                                                // $stock = $store->products()
                                                //     ->where('product_id', $productId)
                                                //     ->first()
                                                //     ->pivot->quantity ?? 0;
                                                // if ($stock < $value) {
                                                //     $fail("موجودی کافی نیست");
                                                // }
                                                
                                            };
                                        },
                                    ])
                                    // ->helperText(function (Get $get) {
                                    //     $storeId = $get('../../store_id');
                                    //     $productId = $get('product_id');
                                    //     if ($storeId && $productId) {
                                    //         $store = Store::find($storeId);
                                    //         $stock = $store ? $store->products()
                                    //             ->where('products.id', $productId)
                                    //             ->value('store_product.quantity') ?? 0 : 0;
                                    //         return "موجودی: $stock";
                                    //     }
                                    //     return '';
                                    // })
                                    ->live(onBlur: true)
                                    ->mask(RawJs::make(<<<'JS'
                                    $money($input)
                                    JS))
                                    ->dehydrateStateUsing(function ($state) {
                                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateCalculations($get, $set);
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('مبلغ واحد')
                                    ->suffix('ریال')
                                    ->required()
                                    ->rules([
                                        function (Get $get) {
                                            return function (string $attribute, $value, callable $fail) use ($get) {
                                                // تبدیل مقدار به عدد
                                                $quantity = self::cleanNumber($value);

                                                // چک کردن بزرگ‌تر از صفر
                                                if ($quantity <= 0) {
                                                    $fail("تعداد باید بزرگ‌تر از صفر باشد.");
                                                }
                                            };
                                        },
                                    ])
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->mask(RawJs::make(<<<'JS'
                                    $money($input)
                                    JS))
                                    ->dehydrateStateUsing(function ($state) {
                                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateCalculations($get, $set);
                                    }),
                                Forms\Components\TextInput::make('sum_price')
                                    ->label('جمع')
                                    ->readOnly()
                                    ->default(0)
                                    ->hidden()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('discount')
                                    ->label('تخفیف (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateCalculations($get, $set);
                                    }),
                                Forms\Components\TextInput::make('discount_price')
                                    ->label('مبلغ تخفیف')
                                    ->readOnly()
                                    ->hidden()
                                    ->default(0)
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('tax')
                                    ->label('مالیات (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateCalculations($get, $set);
                                    }),
                                Forms\Components\TextInput::make('tax_price')
                                    ->label('مبلغ مالیات')
                                    ->readOnly()
                                    ->default(0)
                                    ->hidden()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('total_price')
                                    ->label('جمع کل')
                                    ->suffix('ریال')
                                    ->readOnly()
                                    ->default(0),
                            ]),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        return self::calculateItemTotals($data);
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                        return self::calculateItemTotals($data);
                    })
                    ->columns(7)
                    ->addable()
                    ->deletable()
                    ->columnSpanFull()
                    ->addActionLabel('افزودن سطر جدید'),
                Grid::make(['default' => 3])
                    ->schema([
                        Fieldset::make('خلاصه')
                            ->label('')
                            ->schema([
                                Forms\Components\Placeholder::make('sum_quantities')
                                    ->label('مجموع تعداد')
                                    ->extraAttributes(['style' => 'font-size: 1.5rem;'])
                                    ->content(fn(Get $get) => number_format(
                                        collect($get('items'))->sum(fn($item) => self::cleanNumber($item['quantity']))
                                    )),
                                Forms\Components\Placeholder::make('sum_prices')
                                    ->label('مجموع جمع آیتم‌ها')
                                    ->extraAttributes(['style' => 'font-size: 1.5rem;'])
                                    ->content(fn(Get $get) => number_format(
                                        collect($get('items'))->sum(fn($item) => self::cleanNumber($item['sum_price']))
                                    ) . ' ریال'),
                                Forms\Components\Placeholder::make('sum_discount_prices')
                                    ->label('مجموع تخفیف‌ها')
                                    ->extraAttributes(['style' => 'color:green;font-size: 1.5rem;'])
                                    ->content(fn(Get $get) => number_format(
                                        collect($get('items'))->sum(fn($item) => self::cleanNumber($item['discount_price']))
                                    ) . ' ریال'),
                                Forms\Components\Placeholder::make('sum_tax_prices')
                                    ->label('مجموع مالیات‌ها')
                                    ->extraAttributes(['style' => 'color:red;font-size: 1.5rem;'])
                                    ->content(fn(Get $get) => number_format(
                                        collect($get('items'))->sum(fn($item) => self::cleanNumber($item['tax_price']))
                                    ) . ' ریال'),
                                Placeholder::make('sum_total')
                                    ->label('مجموع جمع کل')
                                    ->extraAttributes(['style' => 'font-weight:bold;font-size: 1.5rem;'])
                                    ->content(fn(Get $get) => number_format(
                                        collect($get('items'))->sum(fn($item) => self::cleanNumber($item['total_price']))
                                    ) . ' ریال'),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('شماره فاکتور'),
                Tables\Columns\TextColumn::make('date_jalali')->label('تاریخ'),
                // Tables\Columns\TextColumn::make('entity')->label('تأمین‌کننده'),
                Tables\Columns\TextColumn::make('title')->label('عنوان')
                ->default('-'),
                Tables\Columns\TextColumn::make('type')->label('نوع')
                ->formatStateUsing(function($state){
                    if($state == 'purchase')
                    return 'خرید';
                    elseif($state == 'purchase_return')
                    return 'برگشت خرید';
                    elseif($state == 'sale')
                    return 'فروش';
                    elseif($state == 'sale_return')
                    return 'برگشت فروش';
                    else
                    return '-';

                })
                ->color(function($state){
                    if($state == 'purchase')
                    return 'success';
                    elseif($state == 'purchase_return')
                    return 'danger';
                    elseif($state == 'sale')
                    return 'success';
                    elseif($state == 'sale_return')
                    return 'danger';
                    else
                    return '-';

                }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('جمع مبلغ')
                    ->money('irr', locale: 'fa')
                    ->getStateUsing(fn($record) => $record->items()->sum('total_price')),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('مانده پرداخت')
                    ->money('irr', locale: 'fa')
                    ->color(fn($record) => $record->remaining_balance > 0 ? 'danger' : 'success'),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // ExportAction::make()
                //     ->label('اکسل')
                //     ->exporter(InvoiceItemExporter::class)
                //     ->formats([ExportFormat::Xlsx])
                //     ->modifyQueryUsing(fn(Builder $query, Model $record) => InvoiceItem::where('invoice_id', $record->id))
                //     ->icon('heroicon-o-arrow-up-tray')
                //     ->color('warning'),
                Action::make('pdf')
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->url(fn(Model $record): string => route('invoice.pdf',['id'=>$record->id]))
                        ->openUrlInNewTab(),
                Action::make('payments')
                    ->label('پرداخت‌ها')
                    ->url(fn(Model $record): string => route('filament.company.resources.invoices.payments', ['record' => $record]))
                    ->hidden(fn(Model $record) => $record->type=='purchase')

                    ->icon('heroicon-o-currency-dollar'),
                Action::make('payments')
                    ->label('دریافت ها')
                    ->url('#')
                    ->hidden(fn(Model $record) => $record->type!='purchase')
                    ->icon('heroicon-o-currency-dollar'),
                Tables\Actions\EditAction::make()
                    ->label('ویرایش')
                    ->color('warning')
                    ->requiresConfirmation() // تأیید قبل از ویرایش
                    ->modalHeading('ویرایش فاکتور')
                    ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور را ویرایش کنید؟')
                    ->modalSubmitActionLabel('بله، ویرایش کن')
                    ->before(function ($record, $action) {
                        // اگر پرداخت وجود داره، اجازه ویرایش نمیدیم
                        if ($record->payments()->exists()) {
                            Notification::make()
                                ->title('خطا')
                                ->body('این فاکتور دارای پرداخت است و نمی‌توان آن را ویرایش کرد.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle('فاکتور با موفقیت ویرایش شد'),

                // Tables\Actions\DeleteAction::make()
                // ->label('حذف')
                // ->requiresConfirmation() // تأیید قبل از حذف
                // ->modalHeading('حذف فاکتور')
                // ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور را حذف کنید؟')
                // ->modalSubmitActionLabel('بله، حذف کن')
                // ->before(function ($record, $action) {
                //     // چک کردن وجود پرداخت‌ها
                //     if ($record->payments()->exists()) {
                //         Notification::make()
                //             ->title('خطا')
                //             ->body('این فاکتور دارای پرداخت است و نمی‌توان آن را حذف کرد.')
                //             ->danger()
                //             ->send();
                //         $action->cancel();
                //     }
                //     // قبل از حذف فاکتور، آیتم‌ها رو هم Soft Delete می‌کنیم
                //     $record->items()->delete();
                // })
                // ->successNotificationTitle('فاکتور با موفقیت حذف شد'),
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'payments' => Pages\Payments::route('/{record}/payments'),
        ];
    }

    public static function cleanNumber($value)
    {
        return (float) str_replace(',', '', $value ?? 0);
    }

    public static function updateCalculations(Get $get, Set $set)
    {
        $quantity = self::cleanNumber($get('quantity'));
        $unitPrice = self::cleanNumber($get('unit_price'));
        $discount = self::cleanNumber($get('discount'));
        $tax = self::cleanNumber($get('tax'));

        $sumPrice = $quantity * $unitPrice;
        $discountPrice = $sumPrice * ($discount / 100);
        $taxPrice = ($sumPrice - $discountPrice) * ($tax / 100);
        $totalPrice = $sumPrice - $discountPrice + $taxPrice;

        $set('sum_price', number_format($sumPrice, 0, '', ','));
        $set('discount_price', number_format($discountPrice, 0, '', ','));
        $set('tax_price', number_format($taxPrice, 0, '', ','));
        $set('total_price', number_format($totalPrice, 0, '', ','));
    }

    public static function calculateItemTotals(array $data): array
    {
        $data['quantity'] = self::cleanNumber($data['quantity']);
        $data['unit_price'] = self::cleanNumber($data['unit_price']);
        $data['sum_price'] = $data['quantity'] * $data['unit_price'];
        $data['discount_price'] = $data['sum_price'] * ($data['discount'] / 100);
        $data['tax_price'] = ($data['sum_price'] - $data['discount_price']) * ($data['tax'] / 100);
        $data['total_price'] = $data['sum_price'] - $data['discount_price'] + $data['tax_price'];
        return $data;
    }

    public static function sumTotal($get)
    {
        return number_format(
            intval(collect($get('items'))->sum(fn($item) => self::cleanNumber($item['total_price']))),
            0,
            '',
            ','
        );
    }

    protected static ?int $navigationSort = 5;
}
