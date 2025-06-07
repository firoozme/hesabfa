<?php
namespace App\Filament\Company\Resources\ProductResource\Pages;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Store;
use Filament\Actions;
use App\Models\Product;
use Filament\Forms\Set;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Classes\ProductImport;
use App\Models\ProductCategory;
use App\Models\StoreTransaction;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use App\Filament\Exports\ProductExporter;
use Filament\Resources\Pages\ManageRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\Exports\Enums\ExportFormat;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use App\Filament\Company\Resources\ProductResource;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;
    public $selectedStoreId;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    dd($data);
                    // دسترسی به selected_store_id از فرم
                    $formState       = $this->getMountedActionForm()->getState();
                    $selectedStoreId = $formState['selected_store_id'] ?? null;

                    // اضافه کردن selected_store_id به $data برای استفاده در after
                    if ($selectedStoreId) {
                        $this->selectedStoreId = $selectedStoreId;
                    }

                    // اضافه کردن company_id
                    $data['company_id']     = auth('company')->user()->id;
                    $data['selling_price']  = (float) str_replace(',', '', $data['selling_price']);
                    $data['purchase_price'] = (float) str_replace(',', '', $data['purchase_price']);

                    // حذف selected_store_id برای جلوگیری از ذخیره در جدول products
                    unset($data['selected_store_id']);

                    \Log::info('mutateFormDataUsing data:', $data);
                    return $data;
                })
                ->after(function ($record, array $data) {
                    // دسترسی به selected_store_id از $data
                    if ($this->selectedStoreId) {
                        $defaultStore    = Store::where('is_default', true)->first();
                        $selectedStoreId = $this->selectedStoreId;

                        $storeId = $selectedStoreId;
                        if ($storeId) {
                            // ثبت تراکنش
                            $transaction = StoreTransaction::create([
                                'store_id'         => $storeId,
                                'type'             => 'entry',
                                'date'             => Carbon::today(),
                                'reference'        => 'INIT' . $record->id,
                                'destination_type' => Product::class,
                                'destination_id'   => $record->id,
                            ]);

                            // ثبت آیتم تراکنش
                            $transaction->items()->create([
                                'product_id' => $record->id,
                                'quantity'   => $record->inventory,
                            ]);

                            // به‌روزرسانی موجودی در جدول store_product
                            $record->stores()->syncWithoutDetaching([
                                $storeId => ['quantity' => $record->inventory],
                            ]);
                        }
                    }

                    // سایر منطق‌های بعد از ایجاد
                }),

            ExportAction::make()
                ->label('خروجی اکسل')
                ->color('success')
                ->modalHeading('گرفتن خروجی ')
                ->icon('heroicon-o-arrow-up-tray')
                ->exporter(ProductExporter::class)
                ->formats([
                    ExportFormat::Xlsx,
                ])
                ->fileDisk('public'),

            ExcelImportAction::make()
                ->use(ProductImport::class)
                ->color('warning')
                // ->validateUsing([
                //     'inventory' => 'prohibited',
                // ])
                ->sampleExcel(
                    sampleData: [
                        ['name' => 'محصول 1', 'barcode' => '1111,2222,3333', 'Selling_price' => '10000000', 'purchase_price' => '9000000', 'minimum_order' => 1, 'lead_time' => 2, 'reorder_point' => 10, 'sales_tax' => 10, 'purchase_tax' => 10, 'inventory' => 100],
                        ['name' => 'محصول 2', 'barcode' => '0000,88888,99999', 'Selling_price' => '20000000', 'purchase_price' => '18000000', 'minimum_order' => 1, 'lead_time' => 2, 'reorder_point' => 10, 'sales_tax' => 10, 'purchase_tax' => 10, 'inventory' => 120],
                    ],
                    fileName: 'محصولات.xlsx',
                    sampleButtonLabel: 'نمونه',
                    customiseActionUsing: fn(Action $action) => $action->color('primary')
                        ->icon('heroicon-o-arrow-down-tray')
                )
                ->label('وارد کردن محصول')
                ->slideOver()
                ->modalDescription('فایل خودر را طبق نمونه بصورت اکسل آپلود کنید')
                ->modalHeading('وارد کردن دسته ای ')
                ->beforeUploadField([
                    Select::make('product_unit_id')
                        ->options(ProductUnit::where('company_id',auth('company')->user()->id)->pluck('name','id')->all())
                        ->label('واحد شمارش')
                        ->required()
                        ->live()
                        ->suffixAction(
                            Action::make('add_store')
                                ->label('اضافه کردن واحد ')
                                ->icon('heroicon-o-plus')
                                ->modalHeading('ایجاد واحد ')
                                ->action(function (array $data) {
                                    $unit = ProductUnit::create([
                                        'name' => $data['name'],
                                        'company_id' => auth('company')->user()->id
                                    ]);
                                    return $unit->id;
                                })
                                ->form([
                                    TextInput::make('name')
                                        ->label('عنوان')
                                        ->required(),
                                ])
                                ->after(function ($livewire) {
                                    $livewire->dispatch('refreshForm');
                                })
                        ),

                    Select::make('store_id')
                        ->label('انبار')
                        ->required()
                        ->options(Store::where('company_id',auth('company')->user()->id)->pluck('title','id'))
                        ->suffixAction(
                            Action::make('add_store')
                                ->label('افزودن انبار')
                                ->icon('heroicon-o-plus')
                                ->modalHeading('افزودن انبار جدید')
                                ->action(function (array $data) {
                                    $store = Store::create([
                                        'title' => $data['title'],
                                        'phone_number' => $data['phone_number'],
                                        'address' => $data['address'],
                                        'company_id' => auth('company')->user()->id,
                                    ]);
                                    return $store->id; // برای آپدیت سلکت‌باکس
                                })
                                ->form([
                                    TextInput::make('title')
                                    ->label('عنوان')
                                   
                                    ->required()
                                    ->maxLength(255),


                                TextInput::make('phone_number')
                                    ->label('شماره تلفن')
                                    ->required()
                                    ->extraAttributes(['style' => 'direction:ltr'])
                                    ->maxLength(255),
                                Textarea::make('address')
                                    ->label('آدرس')
                                    ->required()
                                    ->columnSpanFull(),
                                ])
                                ->after(function ($livewire) {
                                    $livewire->dispatch('refreshForm');
                                })
                        ),
                        Select::make('tax_id')
                        ->label('نوع مالیات')
                        ->required()
                        ->relationship('tax', 'title')
                        ->suffixAction(
                            Action::make('add_store')
                                ->label('اضافه کردن واحد مالیات')
                                ->icon('heroicon-o-plus')
                                ->modalHeading('ایجاد گروه جدید')
                                ->action(function (array $data) {
                                    $unit = Tax::create(['title' => $data['title']]);
                                    return $unit->id;
                                })
                                ->form([
                                    TextInput::make('title')
                                        ->label('عنوان')
                                        ->required(),
                                ])
                                ->after(function ($livewire) {
                                    $livewire->dispatch('refreshForm');
                                })
                        ),
                    Select::make('product_type_id')
                        ->label('نوع محصول')
                        ->required()
                        ->options(ProductType::where('company_id',auth('company')->user()->id)->pluck('title','id')->all())
                        ->suffixAction(
                            Action::make('add_store')
                                ->label('اضافه کردن واحد ')
                                ->icon('heroicon-o-plus')
                                ->modalHeading('ایجاد واحد ')
                                ->action(function (array $data) {
                                    $unit = ProductType::create([
                                        'title' => $data['title'],
                                        'company_id' => auth('company')->user()->id
                                    ]);
                                    return $unit->id;
                                })
                                ->form([
                                    TextInput::make('title')
                                        ->label('عنوان')
                                        ->required(),
                                ])
                                ->after(function ($livewire) {
                                    $livewire->dispatch('refreshForm');
                                })
                        ),

                    SelectTree::make('product_category_id')
                        ->required()
                        ->label('گروه بندی')
                        ->relationship('category', 'title', 'parent_id', function ($query) {
                            // اطمینان از فیلتر کردن دسته‌ها بر اساس company_id
                            return $query->where('company_id', auth()->user('company')->id);
                        })
                        ->enableBranchNode()
                        ->placeholder('انتخاب گروه ')
                        ->withCount()
                        ->searchable()
                        ->emptyLabel('بدون نتیجه')
                        ->suffixAction(
                            Action::make('add_store')
                                ->label('اضافه کردن گروه جدید')
                                ->icon('heroicon-o-plus')
                                ->modalHeading('ایجاد گروه جدید')
                                ->action(function (array $data, Set $set, $livewire) {
                                    $category = ProductCategory::create([
                                        'title'     => $data['title'],
                                        'parent_id' => $data['parent_id'],
                                    ]);
                                    // $livewire->dispatch('refresh-product_category-options');
                                    $livewire->dispatch('refreshComponent', [
                                        'component' => 'select-tree.product_category_id',
                                    ]);
                                    $set('product_category_id', $category->id);
                                    // return $category->id;

                                })
                                ->form([
                                    TextInput::make('title')
                                        ->label('عنوان')
                                        ->required(),
                                    SelectTree::make('parent_id')
                                        ->label('گروه بندی')
                                        ->relationship('category', 'title', 'parent_id')
                                        ->enableBranchNode()
                                        ->placeholder('انتخاب گروه')
                                        ->withCount()
                                        ->searchable()
                                        ->emptyLabel('بدون نتیجه'),
                                ])
                                ->after(function ($livewire) {
                                    $livewire->dispatch('refreshForm');
                                })
                        ),
                ])
                ->beforeImport(function (array $data, $livewire, $excelImportAction) {
                    $excelImportAction->additionalData([
                        'product_unit_id'     => $data['product_unit_id'],
                        'tax_id'              => $data['tax_id'],
                        'company_id'          => auth('company')->user()->id,
                        'product_type_id'                => $data['product_type_id'],
                        'product_category_id' => $data['product_category_id'],
                        'temp' => $data['store_id'],
                        'method' => 'import'
                    ]);
                })
                
                ->uploadField(
                    fn($upload) => $upload
                        ->label("آپلود فایل")
                ),
        ];
    }
}
