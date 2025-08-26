<?php

namespace App\Filament\Company\Resources\InvoiceResource\Pages;

use App\Models\Store;
use App\Models\Person;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Transaction;
use Filament\Actions\Action;
use App\Models\StoreTransaction;
use App\Models\FinancialDocument;
use App\Services\InventoryService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingService;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use App\Models\StoreTransactionItem;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Company\Resources\InvoiceResource;

class ReturnSale extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = InvoiceResource::class;
    protected static string $view = 'filament.company.resources.invoice-resource.pages.return-sale';
    
    public $invoice;
    public ?array $data = [];
    public function mount($record)
    {
        $this->invoice = Invoice::with('items.product', 'person', 'store')->find($record);

        if (!$this->invoice || $this->invoice->type !== 'sale') {
            Notification::make()
                    ->title('فاکتور فروش یافت نشد')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
                return;
        }
        

        // پر کردن اولیه فرم
        $this->form->fill([
            'accounting_auto' => 'manual',
            'parent_invoice_id' => $this->invoice->name ?? $this->invoice->id,
            'person_id' => $this->invoice->person?->fullname ?? '',
            'store_id' => $this->invoice->store->title,
            'date' => now()->format('Y-m-d'),
            'items' => $this->invoice->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name, // برای نمایش نام محصول
                'quantity' => $this->invoice->getRemainingQuantityForProduct($item->product_id),
                'unit_price' => $item->unit_price,
            ])->toArray(),
            'type' => 'sale_return',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
            Grid::make(['default' => 3])
                    ->schema([

                        Radio::make('accounting_auto')
                            ->label('نحوه ورود شماره فاکتور')
                            ->options(['auto' => 'اتوماتیک', 'manual' => 'دستی'])
                            ->default('manual')
                            ->live()
                            ->afterStateUpdated(
                                function($state, callable $set){
                                    $invoice = Invoice::where('type','sale_return')->withTrashed()->orderBy('number','desc')->first();
                                    $number = $invoice ? (++$invoice->number) : 1;
                                    $state === 'auto' ? $set('number', (int)$number) : $set('number', '');
                                }
                            )
                            ->inline()
                            ->inlineLabel(false),
                        TextInput::make('number')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->label('شماره فاکتور')
                            ->required()
                            ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                return $rule
                                ->where('type','sale_return')
                                ->where('company_id', auth('company')->user()->id) // شرط company_id
                                ->where('deleted_at', null); //
                            })
                            ->live()
                            ->maxLength(255),
                        DatePicker::make('date')
                            ->label('تاریخ')
                            ->jalali()
                            ->default(now())
                            ->required(),
             TextInput::make('person_id')
                 ->label('تأمین‌کننده')
                 ->disabled()
                 ->dehydrated(true), // برای ارسال مقدار به سرور
             TextInput::make('store_id')
                 ->label('انبار')
                 ->disabled()
                 ->dehydrated(true),
             TextInput::make('parent_invoice_id')
                 ->label('فاکتور فروش اصلی')
                 ->default($this->invoice->number)
                 ->disabled()
                 ->dehydrated(true),
             
             Repeater::make('items')
                //  ->relationship('items')
                ->columnSpanFull()
                 ->schema([
                     TextInput::make('product_name')
                         ->label('محصول')
                         ->disabled()
                         ->dehydrated(false), // برای نمایش و عدم ارسال به سرور
                     Hidden::make('product_id')
                         ->dehydrated(true), // برای ارسال به سرور
                         TextInput::make('quantity')
                         ->label('تعداد')
                         ->numeric()
                         ->required()
                         ->minValue(1)
                         ->suffix(fn ($get) => ' (باقی‌مانده: ' . $this->invoice->getRemainingQuantityForProduct($get('product_id')) . ')')
                         ->rules([
                             'numeric',
                             'min:1',
                             fn ($get) => 'max:' . $this->invoice->getRemainingQuantityForProduct($get('product_id')), // 👈 rule همزمان 
                             
                          
                         ]),
                     
                     TextInput::make('unit_price')
                         ->numeric()
                         ->required()
                         ->label('قیمت واحد')
                         ->disabled()
                         ->dehydrated(true),
                 ])
                 ->label('آیتم‌های فاکتور')
                 ->columns(3),
             Hidden::make('type')
                 ->default('sale_return'),
                    ])
        ]) ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('ایجاد')
                ->submit('submit')
                ->color('primary'),
            Action::make('createAndDownloadPdf')
                ->label('ایجاد و دانلود PDF')
                ->action('submitAndDownloadPdf')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('ایجاد و دانلود PDF')
                ->modalDescription('آیا می‌خواهید فاکتور برگشت فروش را ایجاد کرده و فایل PDF آن را دانلود کنید؟')
                ->modalSubmitActionLabel('بله، ایجاد و دانلود کن'),
        ];
    }

    public function submit()
    {
        $data = $this->form->getState();
        // dd($data);
        // array:8 [▼ // app\Filament\Company\Resources\InvoiceResource\Pages\ReturnPurchase.php:191
        //     "accounting_auto" => "auto"
        //     "number" => 1
        //     "date" => "2025-08-18"
        //     "person_id" => "للل لللللللل"
        //     "store_id" => "انبار من"
        //     "parent_invoice_id" => "فاکتور1"
        //     "items" => array:2 [▶]
        //     "type" => "purchase_return"
        //     ]

        try {
            $invoice = DB::transaction(function () use ($data) {
                // dd($data);
                // array:5 [▼ // app\Filament\Company\Resources\InvoiceResource\Pages\ReturnPurchase.php:199
                // "accounting_auto" => "auto"
                // "number" => 1
                // "date" => "2025-08-18"
                // "items" => array:2 [▼
                //     0 => array:3 [▼
                //     "product_id" => 1
                //     "quantity" => 1000
                //     "unit_price" => "9000000"
                //     ]
                //     1 => array:3 [▼
                //     "product_id" => 2
                //     "quantity" => 2000
                //     "unit_price" => "18000000"
                //     ]
                // ]
                // "type" => "purchase_return"
                // ]

                
                // ایجاد فاکتور برگشت فروش
                $invoice = Invoice::create([
                    'name' => 'برگشت فروش '.$this->invoice->name,
                    'number' => $data['number'],
                    'date' => $data['date'],
                    'person_id' => $this->invoice->person_id,
                    'store_id' => $this->invoice->store_id,
                    'parent_invoice_id' => $this->invoice->id,
                    'type' => 'sale_return',
                    'company_id' => auth()->user()->company_id,
                    // 'total_amount' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']),
                ]);

                // ایجاد آیتم‌های فاکتور
                foreach ($data['items'] as $item) {
                    $invoice->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                    ]);
                }

                // ایجاد سند مالی معکوس
                $supplier = Person::findOrFail($this->invoice->person_id);
                $inventoryAccount = Account::where('code', $supplier->accounting_code)->firstOrFail();
                AccountingService::createReturnFinancialDocument($invoice, $inventoryAccount, $supplier->account);

                // ایجاد تراکنش انبار (نوع exit)
                $storeTransaction = StoreTransaction::create([
                    'store_id' => $invoice->store_id,
                    'type' => 'exit',
                    'date' => $invoice->date,
                    'reference' => 'SR-' . $invoice->number,
                ]);

                // ایجاد آیتم‌های تراکنش انبار و به‌روزرسانی موجودی
                foreach ($data['items'] as $item) {
                    StoreTransactionItem::create([
                        'store_transaction_id' => $storeTransaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);

                    $product = Product::findOrFail($item['product_id']);


                    // چون قبلا store_product در ایجاد شده پس دیگر نیازی به ایجاد مقدار جدید نیست
                    InventoryService::updateInventory($product, $invoice->store, $item['quantity'], '=');
                }

                return $invoice;
            });

            Notification::make()
                ->title('فاکتور برگشت فروش با موفقیت ایجاد شد')
                ->success()
                ->send();

            return redirect($this->getResource()::getUrl('index'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا در ایجاد فاکتور برگشت فروش')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }
    }

    public function submitAndDownloadPdf()
    {
        $invoice = $this->submit();
        return redirect()->route('invoice.pdf', ['id' => $invoice->id]);
    }
   
}