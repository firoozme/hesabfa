<?php

namespace App\Filament\Company\Resources\ProductResource\Pages;

use App\Models\Tax;
use Filament\Actions;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductCategory;
use Filament\Actions\ExportAction;
use Filament\Actions\Action as Act;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use App\Filament\Exports\ProductExporter;
use Filament\Resources\Pages\ManageRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\Exports\Enums\ExportFormat;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use App\Filament\Company\Resources\ProductResource;
use Filament\Forms\Components\Actions\Action as Acti;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->mutateFormDataUsing(function (array $data) {
                $data['company_id'] = auth('company')->user()->id;
                return $data;
            }),
            // Act::make('print')
            // ->form([
            //     Checkbox::make('name'),
            //     Checkbox::make('barcode'),
            //     Checkbox::make('selling_price'),
            //     Checkbox::make('purchase_price'),
            //     Checkbox::make('inventory'),
            //     Checkbox::make('minimum_order'),
            //     Checkbox::make('lead_time'),
            //     Checkbox::make('reorder_point'),
            //     Checkbox::make('purchase_tax'),
            //     Checkbox::make('type'),
            // ])
            // ->action(function (array $data): void {
            //     // $record->author()->associate($data['authorId']);
            //     dd($data);
            //     // $record->save();
            // }),
            ExportAction::make()
                ->label('خروجی اکسل')
                ->color('success')
            ->modalHeading('گرفتن خروجی ')
                ->icon('heroicon-o-arrow-up-tray')
                ->exporter(ProductExporter::class)
                ->formats([
                    ExportFormat::Xlsx,
                ])
                ->fileDisk('export'),

            ExcelImportAction::make()
            ->color('warning')
            ->validateUsing([
                'inventory' => 'prohibited',
            ])
            ->sampleExcel(
                sampleData: [
                    ['name' => 'محصول 1', 'barcode' => '1111,2222,3333', 'Selling_price' => '10000000', 'purchase_price' => '9000000', 'minimum_order' => 1, 'lead_time' => 2, 'reorder_point' => 10, 'sales_tax' => 10, 'purchase_tax' => 10,],
                    ['name' => 'محصول 2', 'barcode' => '0000,88888,99999', 'Selling_price' => '20000000', 'purchase_price' => '18000000', 'minimum_order' => 1, 'lead_time' => 2, 'reorder_point' => 10, 'sales_tax' => 10, 'purchase_tax' => 10,],
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
                    ->relationship('unit','name')
                    ->label('واحدها')
                    ->required()
                    ->live()
                    ->suffixAction(
                        Acti::make('add_type')
                            ->label('اضافه کردن واحد ')
                            ->icon('heroicon-o-plus') // آیکون دلخواه
                            ->modalHeading('ایجاد واحد ')
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

            Select::make('tax_id')
                ->label('نوع مالیات')
                    ->required()
                    ->relationship('tax','title')
                    ->suffixAction(
                        Acti::make('add_type')
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
                    )
                    ,
            Select::make('type')
                ->label('نوع محصول')
                ->required()
                ->options([
                    'Goods' => 'کالا',
                    'Services' => 'خدمات',
                ]),
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


            ])
            ->beforeImport(function (array $data, $livewire, $excelImportAction) {
                $excelImportAction->additionalData([
                    'product_unit_id' => $data['product_unit_id'],
                    'tax_id' => $data['tax_id'],
                    'company_id' => auth('company')->user()->id,
                    'type' => $data['type'],
                    'product_category_id' => $data['product_category_id'],
                ]);


                // Do some other stuff with the data before importing
            })
            ->uploadField(
                fn ($upload) => $upload
                ->label("آپلود فایل")
            ),
        ];
    }
}
