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
use App\Models\InvoiceItem;
use App\Models\ProductType;
use App\Models\ProductUnit;
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
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Filament\Exports\InvoiceItemExporter;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\Actions\Action as Act;
use App\Filament\Company\Resources\SaleInvoiceResource\Pages;

class SaleInvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationLabel = 'فاکتور فروش';
    protected static ?string $pluralLabel = 'فاکتورهای فروش';
    protected static ?string $label = 'فاکتور فروش';
    protected static ?string $navigationGroup = 'فروش';
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth('company')->user()->id)
            ->whereIn('type', ['sale','sale_return']);
    }

    public static function form(Form $form): Form
    {
        $customerType = PersonType::where('title', 'مشتری')->first();
        return $form
            ->schema([
                Grid::make(['default' => 3])
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('نوع فاکتور')
                            ->options(['sale' => 'فروش'])
                            ->default('sale')
                            ->hidden()
                            ->required(),
                        Radio::make('accounting_auto')
                            ->label('نحوه ورود شماره فاکتور')
                            ->options(['auto' => 'اتوماتیک', 'manual' => 'دستی'])
                            ->default('auto')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $invoice = Invoice::where('type', 'sale')
                                    ->where('company_id', auth('company')->user()->id)
                                    ->withTrashed()
                                    ->orderBy('number', 'desc')
                                    ->first();
                                $number = $invoice ? (++$invoice->number) : 1;
                                $set('number', $state === 'auto' ? (int) $number : '');
                            })
                            ->inline()
                            ->inlineLabel(false),
                        Forms\Components\TextInput::make('number')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->label('شماره فاکتور')
                            ->required()
                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                            ->default(function (Get $get) {
                                $invoice = Invoice::where('type', 'sale')
                                    ->where('company_id', auth('company')->user()->id)
                                    ->withTrashed()
                                    ->orderBy('number', 'desc')
                                    ->first();
                                $number = $invoice ? (++$invoice->number) : 1;
                                return ($get('accounting_auto') === 'auto') ? (int) $number : '';
                            })
                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                return $rule
                                    ->where('type', 'sale')
                                    ->where('company_id', auth('company')->user()->id)
                                    ->where('deleted_at', null);
                            })
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date')
                            ->label('تاریخ')
                            ->jalali()
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('عنوان'),
                        Forms\Components\Select::make('person_id')
                            ->label('مشتری')
                            ->searchable(['firstname', 'lastname'])
                            ->relationship('person', 'fullname')
                            ->options(Person::whereHas('types', fn($query) => $query->where('title', 'مشتری'))
                                ->pluck('fullname', 'id'))
                            ->required()
                            ->live()
                            ->suffixAction(
                                Act::make('add_customer')
                                    ->label('افزودن مشتری')
                                    ->icon('heroicon-o-plus')
                                    ->modalHeading('ایجاد مشتری جدید')
                                    ->action(function (array $data, Set $set, $livewire) {
                                        $person = Person::create([
                                            'firstname' => $data['firstname'],
                                            'lastname' => $data['lastname'],
                                            'accounting_auto' => $data['accounting_auto'],
                                            'accounting_code' => $data['accounting_code'],
                                            'company_id' => auth('company')->user()->id,
                                        ]);
                                        $person->types()->attach($data['types']);
                                        $account = Account::create([
                                            'code' => $person->accounting_code,
                                            'name' => 'حساب مشتری ' . $person->fullname,
                                            'type' => 'asset',
                                            'company_id' => auth('company')->user()->id,
                                            'balance' => 0,
                                        ]);
                                        $person->update(['account_id' => $account->id]);
                                        $set('person_id', $person->id);
                                        $livewire->dispatch('refresh-person-options');
                                    })
                                    ->form([
                                        Radio::make('accounting_auto')
                                            ->label('نحوه ورود کد حسابداری')
                                            ->options(['auto' => 'اتوماتیک', 'manual' => 'دستی'])
                                            ->default('auto')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $person = Person::where('company_id', auth('company')->user()->id)
                                                    ->withTrashed()
                                                    ->latest()
                                                    ->first();
                                                $accounting_code = $person ? (++$person->accounting_code) : 1;
                                                $set('accounting_code', $state === 'auto' ? $accounting_code : '');
                                            })
                                            ->inline()
                                            ->inlineLabel(false),
                                        Forms\Components\TextInput::make('accounting_code')
                                            ->extraAttributes(['style' => 'direction:ltr'])
                                            ->label('کد حسابداری')
                                            ->required()
                                            ->default(function (Get $get) {
                                                $person = Person::where('company_id', auth('company')->user()->id)
                                                    ->withTrashed()
                                                    ->latest()
                                                    ->first();
                                                $accounting_code = $person ? (++$person->accounting_code) : 1;
                                                return ($get('accounting_auto') === 'auto') ? (int) $accounting_code : '';
                                            })
                                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                                return $rule
                                                    ->where('company_id', auth('company')->user()->id)
                                                    ->where('deleted_at', null);
                                            })
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
                                        $livewire->dispatch('refreshForm');
                                    })
                            ),
                        Forms\Components\Select::make('store_id')
                            ->label('انبار')
                            ->relationship('store', 'title')
                            ->options(Store::where('company_id', auth('company')->user()->id)->pluck('title', 'id'))
                            ->required()
                            ->live()
                            ->suffixAction(
                                Act::make('add_store')
                                    ->label('اضافه کردن انبار')
                                    ->icon('heroicon-o-plus')
                                    ->modalHeading('ایجاد انبار جدید')
                                    ->action(function (array $data, Set $set, $livewire) {
                                        $store = Store::create([
                                            'title' => $data['title'],
                                            'phone_number' => $data['phone_number'],
                                            'address' => $data['address'],
                                            'company_id' => auth('company')->user()->id,
                                        ]);
                                        $set('store_id', $store->id);
                                        $livewire->dispatch('refresh-store-options');
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
                                        $livewire->dispatch('refreshForm');
                                    })
                            ),
                    ]),
                Forms\Components\Repeater::make('items')
                    ->label('آیتم‌ها')
                    ->relationship('items')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addable(true)
                    ->deleteAction(fn($action) => $action->requiresConfirmation()->modalHeading('حذف آیتم'))
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2, 'md' => 4, 'lg' => 10])
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('محصول')
                                    ->searchable()
                                    ->placeholder('انتخاب')
                                    ->options(Product::where('company_id', auth('company')->user()->id)->pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->columnSpan(2)
                                    ->suffixAction(
                                        Act::make('add_product')
                                            ->label('افزودن محصول')
                                            ->icon('heroicon-o-plus')
                                            ->modalHeading('ایجاد محصول جدید')
                                            ->action(function (array $data, Set $set, $livewire) {
                                                $product = Product::create([
                                                    'name' => $data['name'],
                                                    'barcode' => $data['barcode'],
                                                    'selling_price' => (float) str_replace(',', '', $data['selling_price']),
                                                    'purchase_price' => (float) str_replace(',', '', $data['purchase_price']),
                                                    'minimum_order' => $data['minimum_order'],
                                                    'lead_time' => $data['lead_time'],
                                                    'reorder_point' => $data['reorder_point'],
                                                    'sales_tax' => $data['sales_tax'],
                                                    'purchase_tax' => $data['purchase_tax'],
                                                    'product_type_id' => $data['product_type_id'],
                                                    'inventory' => $data['inventory'] ?? 0,
                                                    'product_unit_id' => $data['product_unit_id'],
                                                    'tax_id' => $data['tax_id'],
                                                    'product_category_id' => $data['product_category_id'],
                                                    'company_id' => auth('company')->user()->id,
                                                ]);
                                                if (!empty($data['image'])) {
                                                    $product->update(['image' => $data['image']]);
                                                }
                                                if (!empty($data['selected_store_id']) && $data['inventory'] > 0) {
                                                    $product->stores()->attach($data['selected_store_id'], ['quantity' => $data['inventory']]);
                                                }
                                                $set('product_id', $product->id);
                                                $livewire->dispatch('refresh-product-options');
                                                $set('unit', $product->product_unit_id);
                                                $set('unit_price', $product ? number_format($product->selling_price, 0, '', ',') : 0);
                                            })
                                            ->form([
                                                Forms\Components\Grid::make()->columns(3)->schema([
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
                                                        ->mask(RawJs::make('$money($input)'))
                                                        ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state))
                                                        ->postfix('ریال'),
                                                    Forms\Components\TextInput::make('purchase_price')
                                                        ->label('قیمت خرید')
                                                        ->mask(RawJs::make('$money($input)'))
                                                        ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state))
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
                                                    Forms\Components\Select::make('product_type_id')
                                                        ->label('نوع')
                                                        ->options(ProductType::all()->pluck('title', 'id'))
                                                        ->required(),
                                                    Forms\Components\Select::make('selected_store_id')
                                                        ->label('انبار')
                                                        ->options(fn() => Store::all()->pluck('title', 'id'))
                                                        ->required(),
                                                    Forms\Components\Select::make('product_unit_id')
                                                        ->label('واحد شمارش')
                                                        ->options(ProductUnit::all()->pluck('name', 'id'))
                                                        ->required(),
                                                    Forms\Components\Select::make('tax_id')
                                                        ->options(fn() => \App\Models\Tax::all()->pluck('title', 'id'))
                                                        ->label('نوع مالیات'),
                                                    Forms\Components\Select::make('product_category_id')
                                                        ->required()
                                                        ->label('گروه‌بندی')
                                                        ->options(function () {
                                                            $categories = ProductCategory::all();
                                                            $options = [];
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
                                                        ->preload(),
                                                ]),
                                            ])
                                            ->after(function ($livewire) {
                                                $livewire->dispatch('refreshForm');
                                            })
                                    )
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $product = Product::find($state);
                                        $set('unit', $product ? $product->product_unit_id : null);
                                        $latestInvoiceProduct = InvoiceItem::where('product_id', $state)->latest()->first();
                                        $set('unit_price', $latestInvoiceProduct ? number_format($latestInvoiceProduct->unit_price, 0, '', ',') : ($product ? $product->selling_price : 0));
                                    })
                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                        if ($record && $record->unit) {
                                            $set('unit', $record->unit);
                                        } else {
                                            $product = Product::find($state);
                                            $set('unit', $product ? $product->product_unit_id : null);
                                        }
                                    }),
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
                                                $quantity = self::cleanNumber($value);
                                                if ($quantity <= 0) {
                                                    $fail("تعداد باید بزرگ‌تر از صفر باشد.");
                                                }
                                                $storeId = $get('../../store_id');
                                                $productId = $get('product_id');
                                                if ($storeId && $productId) {
                                                    $currentStock = \App\Services\InventoryService::getStock($productId, $storeId);
                                                    $oldQuantity = $record ? ($record->quantity ?? 0) : 0;
                                                    $quantityDiff = $quantity - $oldQuantity;
                                                    if ($quantityDiff > 0 && $currentStock < $quantityDiff) {
                                                        $fail("موجودی کافی برای محصول در انبار وجود ندارد (موجودی: {$currentStock}).");
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->helperText(function (Get $get) {
                                        $storeId = $get('../../store_id');
                                        $productId = $get('product_id');
                                        if ($storeId && $productId) {
                                            $stock = \App\Services\InventoryService::getStock($productId, $storeId);
                                            return "موجودی: " . number_format($stock);
                                        }
                                        return '';
                                    })
                                    ->live(debounce: '500ms')
                                    ->mask(RawJs::make(<<<'JS'
                                    $money($input)
                                    JS))
                                    ->dehydrateStateUsing(function($state){
                                        return(float)str_replace(',','',$state);// تبدیل رشته فرمت‌شده به عدد
                                    })
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateCalculations($get, $set)),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('مبلغ واحد')
                                    ->suffix('ریال')
                                    ->columnSpan(2)
                                    ->required()
                                    ->rules([
                                        fn(Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                                            $price = self::cleanNumber($value);
                                            if ($price <= 0) {
                                                $fail("مبلغ واحد باید بزرگ‌تر از صفر باشد.");
                                            }
                                        },
                                    ])
                                    ->live(debounce: '500ms')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state))
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateCalculations($get, $set)),
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
                                    ->live(debounce: '500ms')
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateCalculations($get, $set)),
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
                                    ->live(debounce: '500ms')
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateCalculations($get, $set)),
                                Forms\Components\TextInput::make('tax_price')
                                    ->label('مبلغ مالیات')
                                    ->readOnly()
                                    ->default(0)
                                    ->hidden()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('total_price')
                                    ->label('جمع کل')
                                    ->columnSpan(2)
                                    ->suffix('ریال')
                                    ->readOnly()
                                    ->default(0),
                            ]),
                    ])
                    ->mutateRelationshipDataBeforeFillUsing(function (array $data, $record): array {
                        $data['quantity'] = number_format($data['quantity'], 0, '', ',');
                        $data['unit_price'] = number_format($data['unit_price'], 0, '', ',');
                        $data['sum_price'] = number_format($data['sum_price'], 0, '', ',');
                        $data['discount_price'] = number_format($data['discount_price'], 0, '', ',');
                        $data['tax_price'] = number_format($data['tax_price'], 0, '', ',');
                        $data['total_price'] = number_format($data['total_price'], 0, '', ',');
                        $data['unit'] = $data['unit'] ?? Product::find($data['product_id'])->product_unit_id;
                        return $data;
                    })
                    ->mutateRelationshipDataBeforeCreateUsing(fn(array $data) => self::calculateItemTotals($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn(array $data) => self::calculateItemTotals($data))
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
                Tables\Columns\TextColumn::make('name')
                            ->label('عنوان'),
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
                Tables\Columns\TextColumn::make('date_jalali')
                    ->label('تاریخ')
                    ->sortable(['date']),
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->default('-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('جمع مبلغ')
                    ->money('irr', locale: 'fa')
                    ->getStateUsing(fn($record) => $record->items()->sum('total_price'))
                    ->sortable(
                        query: fn($query, $direction) => $query->withSum('items', 'total_price')->orderBy('items_sum_total_price', $direction)
                    ),
                Tables\Columns\IconColumn::make('is_installment')
                    ->label('اقساطی؟')
                    ->boolean(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('پرداخت‌شده')
                    ->money('irr', locale: 'fa')
                    ->getStateUsing(fn($record) => $record->total_paid)
                    ->sortable(
                        query: fn($query, $direction) => $query->withSum('payments', 'amount')->orderBy('payments_sum_amount', $direction)
                    ),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('باقیمانده')
                    ->money('irr', locale: 'fa')
                    ->getStateUsing(fn($record) => $record->remaining_amount)
                    ->sortable(
                        query: fn($query, $direction) => $query
                            ->withSum('items', 'total_price')
                            ->withSum('payments', 'amount')
                            ->orderByRaw('(items_sum_total_price - payments_sum_amount) ' . $direction)
                    ),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('return')
                ->label('برگشت')
                ->color('warning')
                ->icon('heroicon-o-arrow-uturn-left')
                ->url(fn(Model $record): string => route('filament.company.resources.sale-invoices.return', ['record' => $record]))
                ->visible(fn(Model $record): bool => $record->type === 'sale'),
                Action::make('installment')
                    ->label('تقسیط')
                    ->icon('heroicon-o-chart-pie')
                    ->color('success')
                    ->url(fn() => route('filament.company.resources.installment-sales.create')),
                ExportAction::make()
                    ->label('خروجی')
                    ->exporter(InvoiceItemExporter::class)
                    ->formats([ExportFormat::Xlsx])
                    ->modifyQueryUsing(fn(Builder $query, Model $record) => InvoiceItem::where('invoice_id', $record->id))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning'),
                Tables\Actions\Action::make('edit')
                    ->label(fn(Invoice $record): string => $record->type === 'sale_return' ? 'ویرایش برگشت' : 'ویرایش فروش')
                    ->icon('heroicon-o-pencil')
                    ->color(fn(Invoice $record): string => $record->type === 'sale_return' ? 'info' : 'primary')
                    ->url(fn(Invoice $record): string => $record->type === 'sale_return'
                        ? route('filament.company.resources.sale-invoices.edit-sale-return', ['record' => $record->id])
                        : route('filament.company.resources.sale-invoices.edit', ['record' => $record->id]))
                    ->requiresConfirmation()
                    ->modalHeading(fn(Invoice $record): string => $record->type === 'sale_return' ? 'ویرایش فاکتور برگشت فروش' : 'ویرایش فاکتور فروش')
                    ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور را ویرایش کنید؟'),
                    Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('حذف فاکتور')
                    ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور و فاکتورهای برگشت فروش مرتبط را حذف کنید؟')
                    ->action(function ($record) {
                        Log::warning('deleting invoice_id: ' . $record->id . ', record: ' . json_encode($record->toArray()));

                        try {
                            DB::transaction(function () use ($record) {
                                // اعتبارسنجی: بررسی مانده پرداخت
                                if ($record->remaining_balance > 0) {
                                    throw new \Exception('فاکتور دارای مانده پرداخت است و نمی‌تواند حذف شود.');
                                }

                                // اگر فاکتور فروش است، فاکتورهای برگشت فروش مرتبط را حذف کن
                                if ($record->type === 'sale') {
                                    $returnInvoices = Invoice::where('parent_invoice_id', $record->id)
                                        ->where('type', 'sale_return')
                                        ->where('company_id', auth('company')->user()->id)
                                        ->get();

                                    foreach ($returnInvoices as $returnInvoice) {
                                        if ($returnInvoice->remaining_balance > 0) {
                                            throw new \Exception("فاکتور برگشت فروش {$returnInvoice->number} دارای مانده دریافت است.");
                                        }

                                        // حذف تراکنش‌های انبار مرتبط با فاکتور برگشت فروش
                                        $returnTransaction = StoreTransaction::where('reference', 'SR-' . $returnInvoice->number)->first();
                                        if ($returnTransaction) {
                                            $returnItems = StoreTransactionItem::where('store_transaction_id', $returnTransaction->id)->get();
                                            foreach ($returnItems as $item) {
                                                $product = Product::findOrFail($item->product_id);
                                                // برای sale_return، موجودی کاهش یافته بود، حالا افزایش می‌یابد
                                                InventoryService::updateInventory($product, $returnInvoice->store, $item->quantity, 'exit');
                                            }
                                            StoreTransactionItem::where('store_transaction_id', $returnTransaction->id)->delete();
                                            $returnTransaction->delete();
                                        }

                                        // حذف اسناد مالی مرتبط
                                        AccountingService::deleteFinancialDocument($returnInvoice);

                                        // حذف آیتم‌های فاکتور برگشت فروش
                                        $returnInvoice->items()->delete();

                                        // حذف نرم فاکتور برگشت فروش
                                        $returnInvoice->delete();
                                    }
                                }elseif($record->type === 'sale_return'){
                                    if ($record->remaining_balance > 0) {
                                        throw new \Exception("فاکتور برگشت فروش {$record->number} دارای مانده دریافت است.");
                                    }

                                    // حذف تراکنش‌های انبار مرتبط با فاکتور برگشت فروش
                                    $returnTransaction = StoreTransaction::where('reference', 'SR-' . $record->number)->first();
                                    if ($returnTransaction) {
                                        $returnItems = StoreTransactionItem::where('store_transaction_id', $returnTransaction->id)->get();
                                        foreach ($returnItems as $item) {
                                            $product = Product::findOrFail($item->product_id);
                                            // برای purchase_return، موجودی کاهش یافته بود، حالا افزایش می‌یابد
                                            InventoryService::updateInventory($product, $record->store, $item->quantity, 'exit');
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
                                $storeTransaction = StoreTransaction::where('reference', 'SALE-INV-' . $record->number)->first();
                                if ($storeTransaction) {
                                    $items = StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)->get();
                                    foreach ($items as $item) {
                                        $product = Product::findOrFail($item->product_id);
                                        // برای purchase، موجودی افزایش یافته بود، حالا کاهش می‌یابد
                                        // برای purchase_return، موجودی کاهش یافته بود، حالا افزایش می‌یابد
                                        $transactionType = $record->type === 'sale' ? 'entry' : 'exit';
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaleInvoices::route('/'),
            'create' => Pages\CreateSaleInvoice::route('/create'),
            'edit' => Pages\EditSaleInvoice::route('/{record}/edit'),
            'return'               => Pages\ReturnSale::route('/{record}/return'),
            'edit-sale-return' => Pages\EditSaleReturn::route('/{record}/edit-sale-return'),
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
}