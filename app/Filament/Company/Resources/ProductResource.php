<?php
namespace App\Filament\Company\Resources;

use stdClass;
use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use App\Models\Discount;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductType;
use App\Models\ProductUnit;
use Filament\Support\RawJs;
use App\Models\StoreProduct;
use App\Models\ProductCategory;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use App\Models\Scopes\ActiveProductScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Forms\Components\Actions\Action;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action as Act;
use App\Filament\Company\Resources\ProductResource\Pages;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'محصول';
    protected static ?string $pluralLabel     = 'محصولات';
    protected static ?string $label           = 'محصولات';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?int $navigationSort     = 4;
    protected static ?string $navigationIcon  = 'heroicon-o-tag';

    public static function form(Form $form): Form
    {
        return $form

            ->schema([

                FileUpload::make('image')
                    ->label('تصویر')
                    ->disk('public')
                    ->directory('products/image')
                    ->visibility('private')
                    ->deleteUploadedFileUsing(function ($file) {
                        // Optional: Define how to delete the file
                        $imagePath = env('APP_ROOT') . '/upload/' . $file;
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    })->columnSpanFull(),

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
                        return(float)str_replace(',','',$state);// تبدیل رشته فرمت‌شده به عدد
                    })
                    ->postfix('ریال'),
                Forms\Components\TextInput::make('purchase_price')
                    ->label('قیمت خرید')
                    ->mask(RawJs::make(<<<'JS'
                        $money($input)
                        JS))
                    ->dehydrateStateUsing(function($state){
                        return(float)str_replace(',','',$state);// تبدیل رشته فرمت‌شده به عدد
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
                Forms\Components\Select::make('product_type_id')
                    ->label('نوع')
                    ->options(ProductType::where('company_id',auth('company')->user()->id)->pluck('title','id')->all())
                    ->required()
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

                TextInput::make('inventory')
                    ->label('موجودی اولیه')
                    ->numeric()
                    ->minValue(0)
                    ->reactive()
                    ->formatStateUsing(fn ($record) => $record->real_inventory ?? 0) // مثال
                 ->debounce('500ms') // تأخیر 500 میلی‌ثانیه
                    // ->hidden(fn($context) => $context === 'edit')
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // $defaultStore = \App\Models\Store::where('is_default', true)->first();
                        // $storesExist  = \App\Models\Store::exists();
                    
                        if ($state > 0) {
                            $set('show_store_select', true);
                        } else {
                            $set('show_store_select', false);
                        }
                        
                    }),
                Select::make('selected_store_id')
                    ->label('انبار')
                    ->hidden(fn($context) => $context === 'edit')
                    ->options(fn() => \App\Models\Store::all()->where('company_id', auth()->user('company')->id)->pluck('title', 'id'))
                    ->visible(fn($get) => $get('show_store_select'))
                    ->required(fn($get) => $get('show_store_select')),

                Forms\Components\Select::make('product_unit_id')
                    ->label('واحد شمارش')
                    ->options(ProductUnit::where('company_id',auth('company')->user()->id)->pluck('name','id')->all())
                    ->required()
                    ->suffixAction(
                        Act::make('add_unit')
                            ->label('اضافه کردن واحد')
                            ->icon('heroicon-o-plus') // آیکون دلخواه
                            ->modalHeading('ایجاد واحد جدید')
                            ->action(function (array $data) {
                                $unit = ProductUnit::create([
                                    'name' => $data['name'],
                                    'company_id' => auth('company')->user()->id
                                ]);
                                return $unit->id; // برای آپدیت سلکت‌باکس
                            })
                            ->form([
                                TextInput::make('name')
                                    ->label('عنوان')
                                    ->required(),
                            ])
                            ->after(function ($livewire) {
                                $livewire->dispatch('refreshForm'); // رفرش فرم بعد از اضافه کردن
                            })
                    ),
                Forms\Components\Select::make('tax_id')
                    ->relationship('tax', 'title')
                    ->label('نوع مالیات')
                    ->suffixAction(
                        Act::make('add_type')
                            ->label('اضافه کردن گروه جدید')
                            ->icon('heroicon-o-plus') // آیکون دلخواه
                            ->modalHeading('ایجاد گروه جدید')
                            ->action(function (array $data) {
                                $unit = Tax::create(['title' => $data['title']]);
                                return $unit->id; // برای آپدیت سلکت‌باکس
                            })
                            ->form([
                                TextInput::make('title')
                                    ->label('عنوان')
                                    ->required(),
                            ])
                            ->after(function ($livewire) {
                                $livewire->dispatch('refreshForm'); // رفرش فرم بعد از اضافه کردن
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
                    ->placeholder('انتخاب گروه')
                    ->withCount()
                    ->searchable()
                    ->emptyLabel('بدون نتیجه')
                    ->suffixAction(
                        Action::make('add_category')
                            ->label('اضافه کردن گروه')
                            ->icon('heroicon-o-plus')
                            ->modalHeading('ایجاد گروه جدید')
                            ->action(function (array $data, $livewire) {
                                // ایجاد دسته جدید
                                $category = ProductCategory::create([
                                    'title'      => $data['title'],
                                    'parent_id'  => $data['parent_id'],
                                    'company_id' => auth()->user('company')->id,
                                ]);

                                // رفرش گزینه‌های SelectTree
                                $livewire->dispatch('refreshComponent', [
                                    'component' => 'select-tree.product_category_id',
                                ]);

                                // نمایش نوتیفیکیشن
                                Notification::make()
                                    ->title('موفقیت')
                                    ->body('دسته جدید با موفقیت ایجاد شد.')
                                    ->success()
                                    ->send();

                                // بازگشت ID دسته جدید برای به‌روزرسانی SelectTree
                                return $category->id;
                            })
                            ->form([
                                TextInput::make('title')
                                    ->label('عنوان')
                                    ->required(),
                                SelectTree::make('parent_id')
                                    ->label('گروه بندی')
                                    ->relationship('category', 'title', 'parent_id', function ($query) {
                                        return $query->where('company_id', auth('company')->user()->id);
                                    })
                                    ->enableBranchNode()
                                    ->placeholder('انتخاب گروه')
                                    ->withCount()
                                    ->searchable()
                                    ->emptyLabel('بدون نتیجه'),
                            ])
                    ),
                Forms\Components\Select::make('discount_id')
                    ->label('تخفیف')
                    ->relationship('discount', 'name', fn(Builder $query) => $query->where('company_id', auth()->user('company')->id)->where('is_active', true))
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->suffixAction(
                        Act::make('add_discount')
                            ->label('ایجاد تخفیف جدید')
                            ->icon('heroicon-o-ticket')
                            ->modalHeading('ایجاد تخفیف جدید')
                            ->action(function (array $data) {
                                $discount = Discount::create([
                                    'name'            => $data['name'],
                                    'type'            => $data['type'],
                                    'value'           => $data['value'],
                                    'start_date'      => $data['start_date'],
                                    'end_date'        => $data['end_date'],
                                    'recurrence_rule' => $data['recurrence_rule'],
                                    'is_active'       => $data['is_active'] ?? true,
                                    'company_id'      => auth()->user('company')->id,
                                ]);
                                return $discount->id;
                            })
                            ->form([
                                TextInput::make('name')
                                    ->label('نام تخفیف')
                                    ->required(),
                                Select::make('type')
                                    ->label('نوع تخفیف')
                                    ->options([
                                        'percentage' => 'درصدی',
                                        'fixed'      => 'مقدار ثابت',
                                    ])
                                    ->required(),
                                TextInput::make('value')
                                    ->label('مقدار تخفیف')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->suffix(fn($get) => $get('type') === 'percentage' ? 'درصد' : 'ریال'),
                                Forms\Components\DateTimePicker::make('start_date')
                                    ->label('تاریخ شروع')
                                    ->nullable()
                                    ->jalali(),
                                Forms\Components\DateTimePicker::make('end_date')
                                    ->label('تاریخ پایان')
                                    ->nullable()
                                    ->jalali(),
                                    Select::make('recurrence_rule')
                                    ->label('قانون تکرار')
                                    ->options([
                                        null               => 'هر روز',
                                        'weekly_monday'    => 'هر دوشنبه',
                                        'weekly_tuesday'   => 'هر سه‌شنبه',
                                        'weekly_wednesday' => 'هر چهارشنبه',
                                        'weekly_thursday'  => 'هر پنج‌شنبه',
                                        'weekly_friday'    => 'هر جمعه',
                                        'weekly_saturday'  => 'هر شنبه',
                                        'weekly_sunday'    => 'هر یک‌شنبه',
                                    ])
                                    ->nullable(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('فعال')
                                    ->default(true),
                            ])
                            ->after(function ($livewire) {
                                $livewire->dispatch('refreshForm');
                            })
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->where('company_id', auth()->user('company')->id))
            ->columns([
                Tables\Columns\TextColumn::make('#')->state(
                    static function (HasTable $livewire, stdClass $rowLoop): string {
                        return (string) (
                            $rowLoop->iteration +
                            ($livewire->getTableRecordsPerPage() * (
                                $livewire->getTablePage() - 1
                            ))
                        );
                    }
                ),
                ImageColumn::make('image')
                    ->label('عکس ')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->checkFileExistence(false)
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->label('عنوان')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.title')
                    ->label('دسته')
                    ->searchable(['title'])
                    ->sortable(['title']),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('بارکد')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextInputColumn::make('selling_price')
                    ->label('قیمت فروش')
                    ->type('text')
                    ->sortable()
                    ->afterStateUpdated(function ($record, $state) {

                        // تأییدیه بعد از ذخیره
                        Notification::make()
                            ->title('موفقیت')
                            ->body('قیمت فروش با موفقیت به‌روزرسانی شد.')
                            ->success()
                            ->send();
                    })
                    ->rules(['required', 'min:0'])
                    ->extraAttributes([
                        'class' => 'with-suffix',
                    ])
                    ->extraInputAttributes([
                        'style' => 'text-align: left; direction: ltr; padding-left: 50px;',
                    ])
                    ->mask(RawJs::make(<<<'JS'
                    $money($input)
                JS)),
                TextInputColumn::make('purchase_price')
                    ->label('قیمت خرید')
                    ->type('text')
                    ->sortable()
                    ->afterStateUpdated(function($record,$state){

                        // تأییدیه بعد از ذخیره
                        Notification::make()
                            ->title('موفقیت')
                            ->body('قیمت خرید با موفقیت به‌روزرسانی شد.')
                            ->success()
                            ->send();
                    })
                    ->rules(['required', 'min:0'])
                    ->extraAttributes([
                        'class' => 'with-suffix',
                    ])
                    ->extraInputAttributes([
                        'style' => 'text-align: left; direction: ltr; padding-left: 50px;',
                    ])
                    ->mask(RawJs::make(<<<'JS'
                    $money($input)
                JS)),
                Tables\Columns\TextColumn::make('inventory')
                    ->label('موجودی')
                    
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('واحد شمارش'),
                Tables\Columns\TextColumn::make('minimum_order')
                    ->label('حداقل سفارش')
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead_time')
                    ->label('زمان انتظار')
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('نقطه سفارش')
                    ->default(10)
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sales_tax')
                    ->label('مالیات فروش')
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->default(0)
                    ->numeric()
                    ->formatStateUsing(
                        fn($state)=>
                        number_format($state).' درصد'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_tax')
                    ->label('مالیات خرید')
                    ->default(0)
                    ->numeric()
                    ->formatStateUsing(
                        fn($state)=>
                        number_format($state).' درصد'
                    )
                    ->toggleable(isToggledHiddenByDefault:true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('type.title')
                    ->label('نوع'),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('واحد شمارش ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax.title')
                    ->label('نوع مالیات')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('فعال'),
                Tables\Columns\TextColumn::make('created_at_jalali')
                    ->label('تاریخ ایجاد')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault:true),
                Tables\Columns\TextColumn::make('inventory')
                    ->label('موجودی')
                    ->color(function($record){
                        $count=StoreProduct::where('product_id',$record->id)->sum('quantity');
                        return $count <= $record->reorder_point ? 'danger' : 'success';
                    })
                    ->description(function ($record) {
                        $count = StoreProduct::where('product_id', $record->id)->sum('quantity');
                        return $count <= $record->reorder_point ? 'موجودی کم - سفارش مجدد لازم است' : '';
                    })
                    ->formatStateUsing(function ($record) {
                        $count = StoreProduct::where('product_id', $record->id)->sum('quantity');
                        return $count;
                    }),
                Tables\Columns\TextColumn::make('reorder_point')->label('نقطه سفارش مجدد'),
                Tables\Columns\TextColumn::make('discounted_price')
                    ->label('قیمت با تخفیف')
                    ->formatStateUsing(function ($record) {
                        $discountedPrice = $record->discounted_price;
                        return $discountedPrice != $record->selling_price
                        ? number_format($discountedPrice) . ' ریال'
                        : '-';
                    })
                    ->color(function ($record) {
                        return $record->discounted_price != $record->selling_price ? 'success' : 'gray';
                    })
                    ->sortable(),

            ])
            ->defaultSort('created_at', 'desc')

            ->filters([
                SelectFilter::make('product_type_id')
                    ->label('نوع')
                    ->options(ProductType::where('company_id',auth('company')->user()->id)->pluck('title','id')->all()),
                TernaryFilter::make('has_discount')
                    ->label('وضعیت تخفیف')
                    ->trueLabel('دارای تخفیف')
                    ->falseLabel('بدون تخفیف')
                    ->queries(
                        true: fn(Builder $query)  => $query->whereHas('discount', function ($q) {
                            $q->where('is_active', true)
                                ->where(function ($q) {
                                    $q->whereNull('start_date')
                                        ->orWhere('start_date', '<=', now());
                                })
                                ->where(function ($q) {
                                    $q->whereNull('end_date')
                                        ->orWhere('end_date', '>=', now());
                                });
                        }),
                        false: fn(Builder $query) => $query->whereDoesntHave('discount'),
                        blank: fn(Builder $query) => $query
                    ),

            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
                Tables\Actions\BulkAction::make('count_selected')
                    ->label('چاپ')
                    ->action(function (HasTable $livewire) {
                        $selectedRecords = $livewire->getSelectedTableRecords();

                        // بررسی خالی بودن رکوردها
                        if ($selectedRecords->isEmpty()) {
                            Notification::make()
                                ->title('خطا')
                                ->body('هیچ رکوردی انتخاب نشده است.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // دریافت مقادیر موقت
                        $validRecords  = [];
                        $hasError      = false;
                        $errorMessages = [];
                        foreach ($selectedRecords as $record) {
                            $recordKey = $record->id;
                            if (! $hasError) {
                                $validRecords[] = [
                                    'name'          => $record->name,
                                    'barcode'       => $record->barcode,
                                    'type'          => $record->product_type_id,
                                    'purchase_price' => $record->purchase_price,
                                    'selling_price' => $record->selling_price,
                                ];
                            }
                        }

                        // اگر خطا وجود داشت، فرآیند متوقف می‌شود
                        if ($hasError) {
                            Notification::make()
                                ->title('خطا')
                                ->body('<ul class="list-disc list-inside"><li>' . implode('</li><li>', $errorMessages) . '</li></ul>')
                                ->danger()
                                ->send();
                            return;
                        }

                        // dd($validRecords);
                        // منطق چاپ
                        Notification::make()
                            ->title('موفقیت')
                            ->body('رکوردها برای چاپ آماده هستند.')
                            ->success()
                            ->send();

                        // ذخیره آرایه در سشن برای انتقال به کنترلر
                        session()->flash('products', $validRecords);
                        return redirect()->route('products.pdf');
                    })
                    ->icon('heroicon-o-printer'),
                Tables\Actions\BulkAction::make('apply_discount')
                    ->label('اعمال تخفیف')
                    ->icon('heroicon-o-ticket')
                    ->form([
                        Select::make('discount_id')
                            ->label('تخفیف')
                            ->options(fn() => \App\Models\Discount::where('company_id', auth()->user('company')->id)
                                    ->where('is_active', true)
                                    ->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload(),
                    ])
                    ->action(function (array $data, HasTable $livewire) {
                        $selectedRecords = $livewire->getSelectedTableRecords();

                        if ($selectedRecords->isEmpty()) {
                            Notification::make()
                                ->title('خطا')
                                ->body('هیچ محصولی انتخاب نشده است.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $discountId = $data['discount_id'] ?? null;

                        $selectedRecords->each(function ($product) use ($discountId) {
                            $product->update(['discount_id' => $discountId]);
                        });

                        Notification::make()
                            ->title('موفقیت')
                            ->body('تخفیف با موفقیت به محصولات انتخاب‌شده اعمال شد.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProducts::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user('company')->id)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
                ActiveProductScope::class,
            ]);
    }

}
