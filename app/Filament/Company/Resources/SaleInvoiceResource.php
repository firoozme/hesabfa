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
use App\Models\ProductUnit;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Fieldset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Tables\Actions\ExportAction;
use Filament\Notifications\Notification;
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
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user('company')->id)
            ->where('type', 'sale');
    }

    public static function form(Form $form): Form
    {
        $customer = PersonType::where('title', 'مشتری')->first();
        return $form
            ->schema([
                Grid::make(['default' => 3])
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('نوع فاکتور')
                            ->options(['sale' => 'فروش'])
                            ->default('sale')
                            ->hidden()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $invoice = Invoice::withTrashed()->latest()->first();
                                $id = $invoice ? (++$invoice->id) : 1;
                                $set('number', (int)$id);
                            }),
                        Radio::make('accounting_auto')
                            ->label('نحوه ورود شماره فاکتور')
                            ->options(['auto' => 'اتوماتیک', 'manual' => 'دستی'])
                            ->default('auto')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $invoice = Invoice::withTrashed()->latest()->first();
                                $id = $invoice ? (++$invoice->id) : 1;
                                $state === 'auto' ? $set('number', (int)$id) : $set('number', '');
                            })
                            ->inline()
                            ->inlineLabel(false),
                        Forms\Components\TextInput::make('number')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->label('شماره فاکتور')
                            ->required()
                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                            ->default(function (Get $get) {
                                $invoice = Invoice::withTrashed()->latest()->first();
                                $id = $invoice ? (++$invoice->id) : 1;
                                return ($get('accounting_auto') == 'auto') ? (int)$id : '';
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
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان')
                            ->required(),
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
                                            'company_id' => auth()->user('company')->id,
                                        ]);
                                        $person->types()->attach($data['types']);
                                        $account = Account::create([
                                            'code' => $person->accounting_code,
                                            'name' => 'حساب مشتری ' . $person->fullname,
                                            'type' => 'asset',
                                            'company_id' => auth()->user('company')->id,
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
                                                $person = Person::withTrashed()->latest()->first();
                                                $id = $person ? (++$person->id) : 1;
                                                $state === 'auto' ? $set('accounting_code', $id) : $set('accounting_code', '');
                                            })
                                            ->inline()
                                            ->inlineLabel(false),
                                        Forms\Components\TextInput::make('accounting_code')
                                            ->extraAttributes(['style' => 'direction:ltr'])
                                            ->label('کد حسابداری')
                                            ->required()
                                            ->default(function (Get $get) {
                                                $person = Person::withTrashed()->latest()->first();
                                                $id = $person ? (++$person->id) : 1;
                                                return ($get('accounting_auto') == 'auto') ? (int)$id : '';
                                            })
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
                            ->options(Store::where('company_id', auth()->user('company')->id)->pluck('title', 'id'))
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
                                            'company_id' => auth()->user('company')->id,
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
                                    
                                    ->options(Product::where('company_id', auth()->user('company')->id)->pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $product = Product::find($state);
                                        $set('unit', $product ? $product->product_unit_id : null);
                                    })
                                    ->afterStateHydrated(function ($state, callable $set, $record) {
                                        if ($record && $record->unit) {
                                            $set('unit', $record->unit);
                                        } else {
                                            $product = Product::find($state);
                                            $set('unit', $product ? $product->product_unit_id : null);
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
                                                $quantity = self::cleanNumber($value);
                                                if ($quantity <= 0) {
                                                    $fail("تعداد باید بزرگ‌تر از صفر باشد.");
                                                }
                                                if (!$record) { // فقط موقع ایجاد چک می‌کنیم
                                                    $storeId = $get('../../store_id');
                                                    $productId = $get('product_id');
                                                    $store = Store::find($storeId);
                                                    if ($store) {
                                                        $currentStock = $store->getStock($productId);
                                                        if ($currentStock < $quantity) {
                                                            $fail("موجودی کافی برای محصول در انبار وجود ندارد.");
                                                        }
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->helperText(function (Get $get) {
                                        $storeId = $get('../../store_id');
                                        $productId = $get('product_id');
                                        if ($storeId && $productId) {
                                            $store = Store::find($storeId);
                                            $stock = $store ? $store->getStock($productId) : 0;
                                            return "موجودی: " . number_format($stock);
                                        }
                                        return '';
                                    })
                                    ->live(onBlur: true)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->dehydrateStateUsing(fn($state) => (float) str_replace(',', '', $state))
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateCalculations($get, $set)),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('مبلغ واحد')
                                    ->suffix('ریال')
                                    ->required()
                                    ->rules([
                                        fn(Get $get) => function (string $attribute, $value, callable $fail) use ($get) {
                                            $price = self::cleanNumber($value);
                                            if ($price <= 0) {
                                                $fail("مبلغ واحد باید بزرگ‌تر از صفر باشد.");
                                            }
                                        },
                                    ])
                                    ->default(0)
                                    ->live(onBlur: true)
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
                                    ->live(onBlur: true)
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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateCalculations($get, $set)),
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
                Tables\Columns\TextColumn::make('date_jalali')->label('تاریخ'),
                Tables\Columns\TextColumn::make('title')->label('عنوان')->default('-'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('جمع مبلغ')
                    ->money('irr', locale: 'fa')
                    ->getStateUsing(fn($record) => $record->items()->sum('total_price')),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('مانده دریافت')
                    ->money('irr', locale: 'fa')
                    ->color(fn($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ExportAction::make()
                    ->label('خروجی')
                    ->exporter(InvoiceItemExporter::class)
                    ->formats([ExportFormat::Xlsx])
                    ->modifyQueryUsing(fn(Builder $query, Model $record) => InvoiceItem::where('invoice_id', $record->id))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning'),
                Action::make('payments')
                    ->label('دریافت‌ها')
                    ->url(fn(Model $record): string => route('filament.company.resources.sale-invoices.payments', ['record' => $record]))
                    ->icon('heroicon-o-currency-dollar'),
                Tables\Actions\EditAction::make()
                    ->label('ویرایش')
                    ->requiresConfirmation()
                    ->modalHeading('ویرایش فاکتور فروش')
                    ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور را ویرایش کنید؟')
                    ->modalSubmitActionLabel('بله، ویرایش کن')
                    ->before(function ($record, $action) {
                        if ($record->payments()->exists()) {
                            Notification::make()
                                ->title('خطا')
                                ->body('این فاکتور دارای دریافت است و نمی‌توان آن را ویرایش کرد.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->successNotificationTitle('فاکتور با موفقیت ویرایش شد'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('حذف فاکتور فروش')
                    ->modalDescription('آیا مطمئن هستید که می‌خواهید این فاکتور را حذف کنید؟')
                    ->modalSubmitActionLabel('بله، حذف کن')
                    ->before(function ($record, $action) {
                        if ($record->payments()->exists()) {
                            Notification::make()
                                ->title('خطا')
                                ->body('این فاکتور دارای دریافت است و نمی‌توان آن را حذف کرد.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                        $record->items()->delete();
                    })
                    ->successNotificationTitle('فاکتور با موفقیت حذف شد'),
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
    protected static ?int $navigationSort = 6;
}