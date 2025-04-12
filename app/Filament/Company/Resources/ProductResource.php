<?php

namespace App\Filament\Company\Resources;

use stdClass;
use App\Models\Tax;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductUnit;
use Filament\Support\RawJs;
use App\Models\ProductCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Blade;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Models\Scopes\ActiveProductScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action as Act;
use App\Filament\Company\Resources\ProductResource\Pages;
use App\Filament\Company\Resources\ProductResource\RelationManagers;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'محصول';
    protected static ?string $pluralLabel = 'محصولات';
    protected static ?string $label = 'محصولات';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-tag';


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
                    ->dehydrateStateUsing(function ($state) {
                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                    })
                    ->postfix('ریال'),
                Forms\Components\TextInput::make('purchase_price')
                    ->label('قیمت خرید')
                    ->mask(RawJs::make(<<<'JS'
                        $money($input)
                        JS))
                        ->dehydrateStateUsing(function ($state) {
                            return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
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
                    ->options([
                        'Goods' => 'کالا',
                        'Services' => 'خدمات',
                    ])
                    ->required(),
                Forms\Components\Select::make('product_unit_id')
                    ->label('واحد شمارش')
                    ->relationship('unit', 'name')
                    ->required()
                    ->suffixAction(
                        Act::make('add_unit')
                            ->label('اضافه کردن واحد')
                            ->icon('heroicon-o-plus') // آیکون دلخواه
                            ->modalHeading('ایجاد واحد جدید')
                            ->action(function (array $data) {
                                $unit = ProductUnit::create(['name' => $data['name']]);
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
                            ->label('اضافه کردن واحد مالیات')
                            ->icon('heroicon-o-plus') // آیکون دلخواه
                            ->modalHeading('ایجاد واحد مالیات')
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
                    ->label('دسته پدر')
                    ->relationship(
                        'category',
                        'title',
                        'parent_id',
                    )
                    ->enableBranchNode()
                    ->placeholder('انتخاب دسته')
                    ->withCount()
                    ->searchable()
                    ->emptyLabel('بدون نتیجه')
                    ->suffixAction(
                        Action::make('add_type')
                            ->label('اضافه کردن واحد مالیات')
                            ->icon('heroicon-o-plus') // آیکون دلخواه
                            ->modalHeading('ایجاد واحد مالیات')
                            ->action(function (array $data) {
                                $unit = ProductCategory::create([
                                    'title' => $data['title'],
                                    'parent_id' => $data['parent_id'],
                                ]);
                                return $unit->id; // برای آپدیت سلکت‌باکس
                            })
                            ->form([
                                TextInput::make('title')
                                    ->label('عنوان')
                                    ->required(),
                                SelectTree::make('parent_id')
                                    ->label('دسته پدر')
                                    ->relationship(
                                        'category',
                                        'title',
                                        'parent_id',
                                    )
                                    ->enableBranchNode()
                                    ->placeholder('انتخاب دسته')
                                    ->withCount()
                                    ->searchable()
                                    ->emptyLabel('بدون نتیجه'),
                            ])
                            ->after(function ($livewire) {
                                $livewire->dispatch('refreshForm'); // رفرش فرم بعد از اضافه کردن
                            })
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->query(Product::query()->where('company_id',auth()->user('company')->id))
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
                    ->default(fn(Product $record) => file_exists(asset('upload/' . $record->image))  ?  asset('upload/' . $record->image) : asset('upload/photo_placeholder.png'))
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
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('قیمت فروش')
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' ریال'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('قیمت خرید')
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' ریال'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventory')
                    ->label('موجودی')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('واحد شمارش'),
                Tables\Columns\TextColumn::make('minimum_order')
                    ->label('حداقل سفارش')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead_time')
                    ->label('زمان انتظار')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('نقطه سفارش')
                    ->default(10)
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sales_tax')
                    ->label('مالیات فروش')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default(0)
                    ->numeric()
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' درصد'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_tax')
                    ->label('مالیات خرید')
                    ->default(0)
                    ->numeric()
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' درصد'
                    )
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->state(fn(Product $record) => ($record->type == 'Goods') ? 'کالا' : 'خدمات')
                    ->color(fn(Product $record) => ($record->type == 'Goods') ? 'info' : 'success'),
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
                    ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('inventory')
                    ->label('موجودی')
                    ->color(function ($record) {
                        return $record->inventory <= $record->reorder_point ? 'danger' : 'success';
                    })
                    ->description(function ($record) {
                        return $record->inventory <= $record->reorder_point ? 'موجودی کم - سفارش مجدد لازم است' : '';
                    }),
                Tables\Columns\TextColumn::make('reorder_point')->label('نقطه سفارش مجدد')

            ])
            ->defaultSort('created_at', 'desc')

            ->filters([
                SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'Goods' => 'کالا',
                        'Services' => 'خدمات',
                    ]),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\Action::make('pdf')
                // ->label('PDF')
                // ->color('success')
                // ->icon('heroicon-o-home')
                // ->action(function (Model $record) {
                //     return response()->streamDownload(function () use ($record) {
                //         echo Pdf::loadHtml(
                //             Blade::render('pdf', ['record' => $record])
                //         )
                //         ->setPaper('a4', 'landscape')
                //         ->stream();
                //     }, $record->name . '.pdf');
                // }),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
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
                ActiveProductScope::class
            ]);
    }
}
