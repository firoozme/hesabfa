<?php

namespace App\Filament\Company\Resources\SaleInvoiceResource\Pages;

use App\Models\Store;
use App\Models\Person;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Product;
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
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Company\Resources\SaleInvoiceResource;

class EditSaleReturn extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SaleInvoiceResource::class;
    protected static string $view = 'filament.company.resources.invoice-resource.pages.edit-return-sale';

    public $invoice;
    public ?array $data = [];

    public function mount($record)
    {
        $this->invoice = Invoice::with('items.product', 'person', 'store', 'parentInvoice')->find($record);

        if (!$this->invoice || $this->invoice->type !== 'sale_return') {
            Notification::make()
                ->title('فاکتور برگشت فروش یافت نشد')
                ->danger()
                ->send();
            return redirect($this->getResource()::getUrl('index'));
        }

        // پر کردن اولیه فرم
        $this->form->fill([
            'accounting_auto' => 'manual',
            'number' => $this->invoice->number,
            'date' => $this->invoice->date,
            'person_id' => $this->invoice->person?->fullname ?? '',
            'store_id' => $this->invoice->store?->title ?? '',
            'parent_invoice_id' => $this->invoice->parentInvoice?->name ?? $this->invoice->parent_invoice_id,
            'note' => $this->invoice->note,
            'items' => $this->invoice->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->toArray(),
            'type' => 'sale_return',
        ]);
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
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
                                function ($state, callable $set) {
                                    $invoice = Invoice::where('type', 'sale_return')->withTrashed()->orderBy('number', 'desc')->first();
                                    $number = $invoice ? (++$invoice->number) : 1;
                                    $state === 'auto' ? $set('number', (int)$number) : $set('number', $this->invoice->number);
                                }
                            )
                            ->inline()
                            ->inlineLabel(false),
                        TextInput::make('number')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->label('شماره فاکتور')
                            ->required()
                            ->readOnly(fn ($get) => $get('accounting_auto') === 'auto')
                            ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                                return $rule
                                    ->where('type', 'sale_return')
                                    ->where('company_id', auth('company')->user()->id)
                                    ->where('deleted_at', null);
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
                            ->dehydrated(true),
                        TextInput::make('store_id')
                            ->label('انبار')
                            ->disabled()
                            ->dehydrated(true),
                        TextInput::make('parent_invoice_id')
                            ->label('فاکتور فروش اصلی')
                            ->disabled()
                            ->dehydrated(true),
                       
                        Repeater::make('items')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('product_name')
                                    ->label('محصول')
                                    ->disabled()
                                    ->dehydrated(false),
                                Hidden::make('product_id')
                                    ->dehydrated(true),
                                TextInput::make('quantity')
                                    ->label('تعداد')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->suffix(fn ($get) => ' (باقی‌مانده: ' . $this->invoice->parentInvoice->getRemainingQuantityForProduct($get('product_id'), $this->invoice->id) . ')')
                                    ->rules([
                                        'numeric',
                                        'min:1',
                                        function (){
                                            return function (string $attribute, $value, $fail){
                                                $productId = $this->data['items'][explode('.', $attribute)[1]]['product_id'] ?? null;
                                                if ($productId) {
                                                    $remaining = $this->invoice->parentInvoice->getRemainingQuantityForProduct($productId, $this->invoice->id);
                                                    if ($value > $remaining) {
                                                        $product = Product::findOrFail($productId);
                                                        $fail("مقدار برگشتی برای {$product->name} نمی‌تواند بیشتر از {$remaining} باشد.");
                                                    }
                                                }
                                            };
                                        },
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
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('update')
                ->label('به‌روزرسانی')
                ->submit('submit')
                ->color('primary'),
            Action::make('updateAndDownloadPdf')
                ->label('به‌روزرسانی و دانلود PDF')
                ->action('updateAndDownloadPdf')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('به‌روزرسانی و دانلود PDF')
                ->modalDescription('آیا می‌خواهید فاکتور برگشت فروش را به‌روزرسانی کرده و فایل PDF آن را دانلود کنید؟')
                ->modalSubmitActionLabel('بله، به‌روزرسانی و دانلود کن'),
        ];
    }

    public function submit()
    {
        // \Log::info('Starting Purchase Return form submission');
        $data = $this->data;
        
        try {
            $invoice = DB::transaction(function () use ($data) {
                // اعتبارسنجی مجموع مقادیر برگشتی
                foreach ($data['items'] as $item) {
                    $remaining = $this->invoice->parentInvoice->getRemainingQuantityForProduct($item['product_id'], $this->invoice->id);
                    if ($item['quantity'] > $remaining) {
                        $product = Product::findOrFail($item['product_id']);
                        throw new \InvalidArgumentException("مقدار برگشتی برای محصول {$product->name} نمی‌تواند بیشتر از {$remaining} باشد.");
                    }
                }

                // محاسبه مبلغ کل فاکتور
                $totalAmount = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

                // به‌روزرسانی فاکتور برگشت فروش
                $this->invoice->update([
                    'number' => $data['number'],
                    'date' => $data['date'],
                    'note' => $data['note'],
                ]);

                // حذف آیتم‌های قبلی و تراکنش‌های انبار
                $storeTransaction = StoreTransaction::where('reference', 'SR-' . $this->invoice->number)->first();
                if ($storeTransaction) {
                    $items = StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)->get();

                    $i=0;
                    foreach ($data['items'] as $itm) {
                        $it[$i] = $itm['quantity'];
                        $i++;
                    }

                    $i=0;
                    foreach ($items as $item) {
                        $product = Product::findOrFail($item->product_id);
                        // Log::info("Reverting inventory for product {$product->id}, quantity +{$item->quantity}");
                        $diff = $it[$i] - $item['quantity'];
                        $update_type = ($diff > 0) ? 'entry' : 'exit';
                        InventoryService::updateInventory($product, $this->invoice->store,  abs($diff),  $update_type );
                        $i++;
                    }
                    StoreTransactionItem::where('store_transaction_id', $storeTransaction->id)->delete();
                    $storeTransaction->delete();
                }

                // حذف سند مالی قبلی
                $financialDocument = FinancialDocument::where('invoice_id', $this->invoice->id)->first();
                if ($financialDocument) {
                    Transaction::where('financial_document_id', $financialDocument->id)->delete();
                    $financialDocument->delete();
                }

                // ایجاد آیتم‌های جدید فاکتور
                $this->invoice->items()->delete();
                foreach ($data['items'] as $item) {
                    $this->invoice->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                    ]);
                }

                // ایجاد سند مالی معکوس جدید
                $supplier = Person::findOrFail($this->invoice->person_id);
                $inventoryAccount = Account::where('code', $supplier->accounting_code)->firstOrFail();
                AccountingService::createReturnFinancialDocument($this->invoice, $inventoryAccount, $supplier->account);

                // ایجاد تراکنش انبار جدید (نوع exit)
                $storeTransaction = StoreTransaction::create([
                    'store_id' => $this->invoice->store_id,
                    'type' => 'exit',
                    'date' => $this->invoice->date,
                    'reference' => 'SR-' . $this->invoice->number,
                ]);

                // ایجاد آیتم‌های تراکنش انبار و به‌روزرسانی موجودی
                foreach ($data['items'] as $item) {
                    StoreTransactionItem::create([
                        'store_transaction_id' => $storeTransaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);

                    $product = Product::findOrFail($item['product_id']);
                    // Log::info("Updating inventory for product {$product->id}, quantity -{$item['quantity']}");

                    

                    InventoryService::updateInventory($product, $this->invoice->store, $item['quantity'], 'entry');
                }

                return $this->invoice;
            });

            Notification::make()
                ->title('فاکتور برگشت فروش با موفقیت به‌روزرسانی شد')
                ->success()
                ->send();

            return redirect($this->getResource()::getUrl('index'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا در به‌روزرسانی فاکتور برگشت فروش')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }
    }

    public function updateAndDownloadPdf()
    {
        $this->update();
        return redirect()->route('invoice.pdf', ['id' => $this->invoice->id]);
    }
}