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
use App\Models\PersonType;
use Filament\Tables\Table;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\Transaction;
use Filament\Support\RawJs;
use App\Models\ProductCategory;
use App\Models\StoreTransaction;
use Filament\Resources\Resource;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingService;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use App\Models\StoreTransactionItem;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Forms\Components\Actions\Action as Act;
use App\Filament\Company\Resources\InvoiceResource\Pages;

class InvoiceResource extends Resource
{
    protected static ?string $model           = Invoice::class;
    protected static ?string $navigationLabel = 'فاکتور خرید';
    protected static ?string $pluralLabel     = 'فاکتورهای  خرید';
    protected static ?string $label           = 'فاکتور خرید';
    protected static ?string $navigationGroup = 'خرید';
    protected static ?string $navigationIcon  = 'heroicon-o-document-arrow-down';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('type', ['purchase', 'purchase_return'])
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
                                function ($state, callable $set) {
                                    $invoice = Invoice::where('type', 'purchase')->withTrashed()->orderBy('number', 'desc')->first();
                                    $number  = $invoice ? (++$invoice->number) : 1;
                                    $state === 'auto' ? $set('number', (int) $number) : $set('number', '');
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
                                    $invoice = Invoice::where('type', 'purchase')->where('company_id', auth('company')->user()->id)->withTrashed()->orderBy('number', 'desc')->first();
                                    $number  = $invoice ? (++$invoice->number) : 1;
                                    return ($get('accounting_auto') == 'auto') ? (int) $number : '';
                                }
                            )
                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                return $rule
                                    ->where('type', 'purchase')
                                    ->where('company_id', auth('company')->user()->id) // شرط company_id
                                    ->where('deleted_at', null);                       //
                            })
                            ->live()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')
                            ->label('تاریخ')
                            ->jalali()
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->label('عنوان'),
                        Forms\Components\Select::make('person_id')
                            ->label('تأمین‌کننده')
                            ->searchable(['firstname', 'lastname'])
                            ->relationship('person', 'fullname')
                            ->options(Person::whereHas('types', fn($query) => $query->where('title', 'تامین کننده'))->where('company_id', auth()->user('company')->id)
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
                                            'firstname'       => $data['firstname'],
                                            'lastname'        => $data['lastname'],
                                            'accounting_auto' => $data['accounting_auto'],
                                            'accounting_code' => $data['accounting_code'],
                                            'company_id'      => auth()->user('company')->id,
                                        ]);
                                        // dd($data);

                                        // اتصال PersonTypeها به Person
                                        $person->types()->attach($data['types']);

                                        // Create Account
                                        $account = Account::create([
                                            'code'       => $person->accounting_code,
                                            'name'       => 'حساب تأمین‌کننده ' . $person->fullname,
                                            'type'       => 'liability',
                                            'company_id' => auth()->user('company')->id,
                                            'balance'    => 0,
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
                                                'auto'   => 'اتوماتیک',
                                                'manual' => 'دستی',
                                            ])
                                            ->default('auto')
                                            ->live()
                                            ->afterStateUpdated(
                                                function ($state, callable $set) {
                                                    $person          = Person::where('company_id', auth('company')->user()->id)->withTrashed()->latest()->first();
                                                    $accounting_code = $person ? (++$person->accounting_code) : 1;
                                                    $state === 'auto' ? $set('accounting_code', $accounting_code) : $set('accounting_code', '');
                                                }
                                            )
                                            ->inline()
                                            ->inlineLabel(false),
                                        Forms\Components\TextInput::make('accounting_code')
                                            ->extraAttributes(['style' => 'direction:ltr'])
                                            ->label('کد حسابداری')
                                            ->required()
                                            ->afterStateHydrated(function (Get $get) {
                                                $person          = Person::where('company_id', auth('company')->user()->id)->withTrashed()->latest()->first();
                                                $accounting_code = $person ? (++$person->accounting_code) : 1;
                                                return ($get('accounting_auto') == 'auto') ? (int) $accounting_code : '';
                                            })
                                            ->default(
                                                function (Get $get) {
                                                    $person          = Person::where('company_id', auth('company')->user()->id)->withTrashed()->latest()->first();
                                                    $accounting_code = $person ? (++$person->accounting_code) : 1;
                                                    // dd($id);
                                                    return ($get('accounting_auto') == 'auto') ? (int) $accounting_code : '';
                                                }
                                            )
                                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                                return $rule
                                                    ->where('company_id', auth('company')->user()->id) // شرط company_id
                                                    ->where('deleted_at', null);                       //
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
                                            ->options(PersonType::all()->pluck('title', 'id'))
                                            ->preload()
                                            ->multiple()
                                            ->required()
                                            ->live(),
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
                                    ->label('اضافه کردن انبار')
                                    ->icon('heroicon-o-plus') // آیکون دلخواه
                                    ->modalHeading('ایجاد انبار جدید')
                                    ->action(function (array $data) {
                                        $unit = Store::create([
                                            'title'        => $data['title'],
                                            'phone_number' => $data['phone_number'],
                                            'address'      => $data['address'],
                                            'company_id'   => auth()->user('company')->id,
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
                        Grid::make(['default' => 1, 'sm' => 2, 'md' => 4, 'lg' => 10])
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('محصول')
                                    ->searchable()
                                    ->placeholder('انتخاب')
                                    ->loadingMessage('در حال لود ...')
                                    ->searchPrompt('تایپ کنید ...')
                                    ->noSearchResultsMessage('بدون نتیجه!')
                                    ->options(Product::where('company_id', auth()->user('company')->id)->pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->columnSpan(2)
                                    ->suffixAction(
                                        Act::make('add_product')
                                            ->label('افزودن محصول')
                                            ->icon('heroicon-o-plus')
                                            ->modalHeading('ایجاد محصول جدید')
                                            ->action(function (array $data, Set $set, $livewire) {
                                                // ایجاد محصول جدید
                                                $product = Product::create([
                                                    'name'                => $data['name'],
                                                    'barcode'             => $data['barcode'],
                                                    'selling_price'       => (float) str_replace(',', '', $data['selling_price']),
                                                    'purchase_price'      => (float) str_replace(',', '', $data['purchase_price']),
                                                    'minimum_order'       => $data['minimum_order'],
                                                    'lead_time'           => $data['lead_time'],
                                                    'reorder_point'       => $data['reorder_point'],
                                                    'sales_tax'           => $data['sales_tax'],
                                                    'purchase_tax'        => $data['purchase_tax'],
                                                    'product_type_id'     => $data['type'],
                                                    'inventory'           => $data['inventory'] ?? 0,
                                                    'product_unit_id'     => $data['product_unit_id'],
                                                    'tax_id'              => $data['tax_id'],
                                                    'product_category_id' => $data['product_category_id'],
                                                    'company_id'          => auth('company')->user()->id, // فرض بر این است که شرکت از کاربر لاگین شده می‌آید
                                                ]);

                                                // مدیریت تصویر (اگر وجود داشته باشد)
                                                if (! empty($data['image'])) {
                                                    $product->update(['image' => $data['image']]);
                                                }

                                                // مدیریت انبار (اگر انتخاب شده باشد)
                                                if (! empty($data['selected_store_id']) && $data['inventory'] > 0) {
                                                    $product->stores()->attach($data['selected_store_id'], ['quantity' => $data['inventory']]);
                                                }

                                                                                  // تنظیم مقدار سلکت‌باکس برای محصول جدید
                                                $set('product_id', $product->id); // فرض می‌کنیم نام فیلد سلکت product_id است

                                                // ارسال رویداد برای رفرش گزینه‌ها
                                                $livewire->dispatch('refresh-product-options');

                                                $set('unit', $product->product_unit_id);
                                                $set('unit_price', $product ? $product->purchase_price : null);
                                            })
                                            ->form([
                                                Section::make()
                                                    ->columns([
                                                        'sm'  => 2,
                                                        'xl'  => 3,
                                                        '2xl' => 4,
                                                    ])
                                                    ->schema([

                                                        FileUpload::make('image')
                                                            ->label('تصویر')
                                                            ->disk('public')
                                                            ->directory('products/image')
                                                            ->visibility('private')
                                                            ->deleteUploadedFileUsing(function ($file) {
                                                                $imagePath = env('APP_ROOT') . '/upload/' . $file;
                                                                if (file_exists($imagePath)) {
                                                                    unlink($imagePath);
                                                                }
                                                            })
                                                            ->columnSpanFull(),

                                                        Forms\Components\TextInput::make('name')
                                                            ->label('نام محصول')
                                                            ->required(),

                                                        Forms\Components\TagsInput::make('barcode')
                                                            ->label('بارکد'),

                                                        Forms\Components\TextInput::make('selling_price')
                                                            ->label('قیمت فروش')
                                                            ->mask(RawJs::make(<<<'JS'
                                                        $money($input)
                                                    JS))
                                                            ->dehydrateStateUsing(function($state){
                                                                return(float)str_replace(',','',$state);
                                                            })
                                                            ->postfix('ریال'),

                                                        Forms\Components\TextInput::make('purchase_price')
                                                            ->label('قیمت خرید')
                                                            ->mask(RawJs::make(<<<'JS'
                                                        $money($input)
                                                    JS))
                                                            ->dehydrateStateUsing(function($state){
                                                                return(float)str_replace(',','',$state);
                                                            })
                                                            ->postfix('ریال'),

                                                        Forms\Components\TextInput::make('minimum_order')
                                                            ->label('حداقل سفارش')
                                                            ->default(1)
                                                            ->numeric()
                                                            ->minValue(1),

                                                        Forms\Components\TextInput::make('lead_time')
                                                            ->label('زمان انتظار')
                                                            ->default(1)
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->postfix('روز'),

                                                        Forms\Components\TextInput::make('reorder_point')
                                                            ->label('نقطه سفارش')
                                                            ->minValue(1)
                                                            ->default(1)
                                                            ->numeric(),

                                                        Forms\Components\TextInput::make('sales_tax')
                                                            ->label('مالیات فروش')
                                                            ->default(0)
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->postfix('درصد'),

                                                        Forms\Components\TextInput::make('purchase_tax')
                                                            ->label('مالیات خرید')
                                                            ->default(0)
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->postfix('درصد'),

                                                        Forms\Components\Select::make('type')
                                                            ->label('نوع')
                                                            ->options(ProductType::all()->pluck('title', 'id'))

                                                            ->required(),

                                                        Forms\Components\Select::make('selected_store_id')
                                                            ->label('انبار')
                                                            ->options(fn() => \App\Models\Store::all()->pluck('title', 'id'))
                                                            ->visible(fn($get) => $get('show_store_select'))
                                                            ->required(fn($get) => $get('show_store_select')),

                                                        Forms\Components\Select::make('product_unit_id')
                                                            ->label('واحد شمارش')
                                                            ->options(ProductUnit::all()->pluck('name', 'id'))
                                                            ->required(),

                                                        Forms\Components\Select::make('tax_id')
                                                            ->options(fn() => \App\Models\Tax::all()->where('company_id', auth('company')->user()->id)->pluck('title', 'id'))
                                                            ->label('نوع مالیات'),

                                                        Forms\Components\Select::make('product_category_id')
                                                            ->required()
                                                            ->label('گروه بندی')
                                                            ->options(function () {
                                                                $categories = ProductCategory::all();
                                                                $options    = [];

                                                                $buildOptions = function ($categories, $parentId = null, $prefix = '') use (&$buildOptions, &$options) {
                                                                    $filtered = $categories->where('parent_id', $parentId);
                                                                    foreach ($filtered as $category) {
                                                                        $options[$category->id] = $prefix . $category->title;
                                                                        $buildOptions($categories, $category->id, $prefix . '— ');
                                                                    }
                                                                };

                                                                $buildOptions($categories);
                                                                return $options;
                                                            })
                                                            ->placeholder('انتخاب گروه')
                                                            ->searchable()
                                                            ->preload()
                                                    ])

                                            ])
                                            ->after(function ($livewire) {
                                                $livewire->dispatch('refreshForm'); // رفرش فرم بعد از اضافه کردن
                                            })
                                    )
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $product = Product::find($state);
                                        $set('unit', $product ? $product->product_unit_id : null);
                                        $set('unit_price', $product ? $product->purchase_price : null);
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
                                    ->live()
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

                                    ->live(onBlur: true)
                                    ->mask(RawJs::make(<<<'JS'
                                    $money($input)
                                    JS))
                                    ->dehydrateStateUsing(function($state){
                                        return(float)str_replace(',','',$state);// تبدیل رشته فرمت‌شده به عدد
                                    })
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateCalculations($get, $set);
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('مبلغ واحد')
                                    ->suffix('ریال')
                                    ->live()
                                    ->required()
                                    ->columnSpan(2)
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
                                    ->default(0) // This Line

                                    ->live(onBlur: true)
                                    ->mask(RawJs::make(<<<'JS'
                                    $money($input)
                                    JS))
                                    ->dehydrateStateUsing(function($state){
                                        return(float)str_replace(',','',$state);// تبدیل رشته فرمت‌شده به عدد
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
                                    ->columnSpan(2)
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
                            ->columns(5)
                            // ->statePath('data')
                    ]),

            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('شماره فاکتور')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_jalali')->label('تاریخ')
                    ->sortable(['created_at']),
                // Tables\Columns\TextColumn::make('entity')->label('تأمین‌کننده'),
                Tables\Columns\TextColumn::make('name')->label('عنوان')
                    ->sortable()
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('type')->label('نوع')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if ($state == 'purchase') {
                            return 'خرید';
                        } elseif ($state == 'purchase_return')
                        return 'برگشت خرید';
                        elseif ($state == 'sale')
                        return 'فروش';
                        elseif ($state == 'sale_return')
                        return 'برگشت فروش';
                        else{
                            return '-';
                        }

                    })
                    ->color(function ($state) {
                        if ($state == 'purchase') {
                            return 'success';
                        } elseif ($state == 'purchase_return')
                        return 'danger';
                        elseif ($state == 'sale')
                        return 'success';
                        elseif ($state == 'sale_return')
                        return 'danger';
                        else{
                            return '-';
                        }

                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('جمع مبلغ')
                    ->money('irr', locale: 'fa')
                    ->sortable(
                        query: function (\Illuminate\Database\Eloquent\Builder $query, string $direction) {
                            $query->withSum('items', 'total_price')
                                ->orderBy('items_sum_total_price', $direction);
                        }
                    )
                    ->getStateUsing(fn($record) => $record->items()->sum('total_price')),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('مانده پرداخت')
                    ->sortable(
                        query: function (\Illuminate\Database\Eloquent\Builder $query, string $direction) {
                            $query->withSum('items', 'total_price')
                                ->withSum('payments', 'amount')
                                ->orderByRaw('(items_sum_total_price - payments_sum_amount) ' . $direction);
                        }
                    )
                    ->money('irr', locale: 'fa')
                    ->color(fn($record) => $record->remaining_balance > 0 ? 'danger' : 'success'),
                TextInputColumn::make('note')
                    ->label('یادداشت'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع فاکتور')

                    ->options([

                        'purchase'        => 'فاکتورهای خرید',
                        'return_purchase' => 'فاکتورهای برگشت خرید',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('created_at', 'desc')
            ->actions([
                ActionGroup::make([
                    Action::make('return')
                        ->label('برگشت')
                        ->color('warning')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->url(fn(Model $record): string => route('filament.company.resources.invoices.return', ['record' => $record]))
                        ->visible(fn(Model $record): bool => $record->type === 'purchase'),
                    Action::make('pdf')
                        ->label('PDF')
                        ->color('success')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->url(fn(Model $record): string => route('invoice.pdf', ['id' => $record->id]))
                        ->openUrlInNewTab(),
                    Action::make('payments')
                        ->label('پرداخت‌ها')
                        ->visible(fn(Model $record): bool => $record->type === 'purchase')
                        ->url(fn(Model $record): string => route('filament.company.resources.invoices.payments', ['record' => $record]))
                        ->hidden(fn(Model $record) => $record->type == 'purchase')

                        ->icon('heroicon-o-currency-dollar'),
                    Action::make('payments')
                        ->label('پرداخت ها')
                        ->url(fn(Model $record): string => route('filament.company.resources.invoices.receipts', ['record' => $record]))
                        ->hidden(fn(Model $record) => $record->type != 'purchase')
                        ->icon('heroicon-o-currency-dollar'),
                    Tables\Actions\Action::make('edit')
                        ->label(fn(Invoice $record): string => $record->type === 'purchase_return' ? 'ویرایش برگشت' : 'ویرایش خرید')
                        ->icon('heroicon-o-pencil')
                        ->color(fn(Invoice $record): string => $record->type === 'purchase_return' ? 'info' : 'primary')
                        ->url(fn(Invoice $record): string => $record->type === 'purchase_return'
                            ? route('filament.company.resources.invoices.edit-purchase-return', ['record' => $record->id])
                            : route('filament.company.resources.invoices.edit', ['record' => $record->id]))
                        ->requiresConfirmation()
                        ->modalHeading(fn(Invoice $record): string => $record->type === 'purchase_return' ? 'ویرایش فاکتور برگشت خرید' : 'ویرایش فاکتور خرید')
                        ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور را ویرایش کنید؟'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف')
                        ->requiresConfirmation()
                        ->modalHeading('حذف فاکتور')
                        ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور و فاکتورهای برگشت خرید مرتبط را حذف کنید؟')
                        ->action(function ($record) {
                            Log::warning('deleting invoice_id: ' . $record->id . ', record: ' . json_encode($record->toArray()));
    
                            try {
                                DB::transaction(function () use ($record) {
                                    // اعتبارسنجی: بررسی مانده پرداخت
                                    if ($record->remaining_balance > 0) {
                                        throw new \Exception('فاکتور دارای مانده پرداخت است و نمی‌تواند حذف شود.');
                                    }
    
                                    // اگر فاکتور خرید است، فاکتورهای برگشت خرید مرتبط را حذف کن
                                    if ($record->type === 'purchase') {
                                        $returnInvoices = Invoice::where('parent_invoice_id', $record->id)
                                            ->where('type', 'purchase_return')
                                            ->where('company_id', auth('company')->user()->id)
                                            ->get();
    
                                        foreach ($returnInvoices as $returnInvoice) {
                                            if ($returnInvoice->remaining_balance > 0) {
                                                throw new \Exception("فاکتور برگشت خرید {$returnInvoice->number} دارای مانده پرداخت است.");
                                            }
    
                                            // حذف تراکنش‌های انبار مرتبط با فاکتور برگشت خرید
                                            $returnTransaction = StoreTransaction::where('reference', 'PR-' . $returnInvoice->number)->first();
                                            if ($returnTransaction) {
                                                $returnItems = StoreTransactionItem::where('store_transaction_id', $returnTransaction->id)->get();
                                                foreach ($returnItems as $item) {
                                                    $product = Product::findOrFail($item->product_id);
                                                    // برای purchase_return، موجودی کاهش یافته بود، حالا افزایش می‌یابد
                                                    InventoryService::updateInventory($product, $returnInvoice->store, $item->quantity, 'entry');
                                                }
                                                StoreTransactionItem::where('store_transaction_id', $returnTransaction->id)->delete();
                                                $returnTransaction->delete();
                                            }
    
                                            // حذف اسناد مالی مرتبط
                                            AccountingService::deleteFinancialDocument($returnInvoice);
    
                                            // حذف آیتم‌های فاکتور برگشت خرید
                                            $returnInvoice->items()->delete();
    
                                            // حذف نرم فاکتور برگشت خرید
                                            $returnInvoice->delete();
                                        }
                                    }elseif($record->type === 'purchase_return'){
                                        if ($record->remaining_balance > 0) {
                                            throw new \Exception("فاکتور برگشت خرید {$record->number} دارای مانده پرداخت است.");
                                        }

                                        // حذف تراکنش‌های انبار مرتبط با فاکتور برگشت خرید
                                        $returnTransaction = StoreTransaction::where('reference', 'PR-' . $record->number)->first();
                                        if ($returnTransaction) {
                                            $returnItems = StoreTransactionItem::where('store_transaction_id', $returnTransaction->id)->get();
                                            foreach ($returnItems as $item) {
                                                $product = Product::findOrFail($item->product_id);
                                                // برای purchase_return، موجودی کاهش یافته بود، حالا افزایش می‌یابد
                                                InventoryService::updateInventory($product, $record->store, $item->quantity, 'entry');
                                            }
                                            StoreTransactionItem::where('store_transaction_id', $returnTransaction->id)->delete();
                                            $returnTransaction->delete();
                                        }

                                        // حذف اسناد مالی مرتبط
                                        AccountingService::deleteFinancialDocument($record);

                                        // حذف آیتم‌های فاکتور برگشت خرید
                                        $record->items()->delete();

                                        // حذف نرم فاکتور برگشت خرید
                                        $record->delete();
                                    }
    
                                    // حذف تراکنش‌های انبار فاکتور اصلی
                                    $storeTransaction = StoreTransaction::where('reference', 'INV-' . $record->number)->first();
                                    if ($storeTransaction) {
                                        $items = StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)->get();
                                        foreach ($items as $item) {
                                            $product = Product::findOrFail($item->product_id);
                                            // برای purchase، موجودی افزایش یافته بود، حالا کاهش می‌یابد
                                            // برای purchase_return، موجودی کاهش یافته بود، حالا افزایش می‌یابد
                                            $transactionType = $record->type === 'purchase' ? 'exit' : 'entry';
                                            InventoryService::updateInventory($product, $record->store, $item->quantity, $transactionType);
                                        }
                                        StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)->delete();
                                        $storeTransaction->delete();
                                    }
    
                                    // حذف اسناد مالی فاکتور اصلی
                                    AccountingService::deleteFinancialDocument($record);
    
                                    // حذف آیتم‌های فاکتور اصلی
                                    $record->items()->delete();
    
                                    // حذف نرم فاکتور اصلی
                                    $record->delete();
                                });
    
                                Notification::make()
                                    ->title('فاکتور با موفقیت حذف شد')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('خطا در حذف فاکتور: ' . $e->getMessage());
                                Notification::make()
                                    ->title('خطا در حذف فاکتور')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                                throw $e;
                            }
                        }),

                ])
                    ->label('عملیات')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size(ActionSize::Small)
                    ->color(fn(Invoice $record): string => $record->type === 'purchase' ? 'primary' : 'warning')
                    ->button(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'                => Pages\ListInvoices::route('/'),
            'create'               => Pages\CreateInvoice::route('/create'),
            'edit'                 => Pages\EditInvoice::route('/{record}/edit'),
            'payments'             => Pages\Payments::route('/{record}/payments'),
            'receipts'             => Pages\Receipts::route('/{record}/receipts'),
            'return'               => Pages\ReturnPurchase::route('/{record}/return'),
            'edit-purchase-return' => Pages\EditPurchaseReturn::route('/{record}/edit-purchase-return'),

        ];
    }

    public static function cleanNumber($value)
    {
        return (float) str_replace(',', '', $value ?? 0);
    }

    public static function updateCalculations(Get $get, Set $set)
    {
        $quantity  = self::cleanNumber($get('quantity'));
        $unitPrice = self::cleanNumber($get('unit_price'));
        $discount  = self::cleanNumber($get('discount'));
        $tax       = self::cleanNumber($get('tax'));

        $sumPrice      = $quantity * $unitPrice;
        $discountPrice = $sumPrice * ($discount / 100);
        $taxPrice      = ($sumPrice - $discountPrice) * ($tax / 100);
        $totalPrice    = $sumPrice - $discountPrice + $taxPrice;

        $set('sum_price', number_format($sumPrice, 0, '', ','));
        $set('discount_price', number_format($discountPrice, 0, '', ','));
        $set('tax_price', number_format($taxPrice, 0, '', ','));
        $set('total_price', number_format($totalPrice, 0, '', ','));
    }

    public static function calculateItemTotals(array $data): array
    {
        $data['quantity']       = self::cleanNumber($data['quantity']);
        $data['unit_price']     = self::cleanNumber($data['unit_price']);
        $data['sum_price']      = $data['quantity'] * $data['unit_price'];
        $data['discount_price'] = $data['sum_price'] * ($data['discount'] / 100);
        $data['tax_price']      = ($data['sum_price'] - $data['discount_price']) * ($data['tax'] / 100);
        $data['total_price']    = $data['sum_price'] - $data['discount_price'] + $data['tax_price'];
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
