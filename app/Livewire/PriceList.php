<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Filament\Tables\Table;
use App\Models\PriceList as Plist;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class PriceList extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    public $products;
    public $price_list;
    public function mount($record)
    {
        $this->price_list = Plist::findOrFail($record);
        $this->products = $this->price_list->products->pluck('id')->toArray();
    }
    public function render()
    {
        return view('livewire.price-list');
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->whereIn('id',$this->products))
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->searchable()
                    ->hidden(!$this->price_list->display_id),
                ImageColumn::make('image')
                ->label('عکس ')
                ->circular()
                ->extraImgAttributes(['loading' => 'lazy'])
                ->checkFileExistence(false)
                // ->default(fn(Product $record) => file_exists(asset('upload/'.$record->image))  ?  asset('upload/'.$record->image) : asset('upload/photo_placeholder.png') )

                ->disk('public')
                ->hidden(!$this->price_list->display_image),
                TextColumn::make('name')
                    ->label('عنوان')
                    ->sortable()
                    ->searchable()
                    ->hidden(!$this->price_list->display_name),
                TextColumn::make('barcode')
                    ->label('بارکد')
                    ->searchable()
                    ->hidden(!$this->price_list->display_barcode),
                TextColumn::make('selling_price')
                    ->label('قیمت فروش')
                    ->money('irr')
                    ->sortable()
                    ->hidden(!$this->price_list->display_selling_price),
                TextColumn::make('purchase_price')
                ->label('قیمت خرید')
                ->money('irr')
                    ->sortable()
                    ->hidden(!$this->price_list->display_purchase_price),
                TextColumn::make('inventory')
                ->label('موجودی')
                    ->sortable()
                    ->hidden(!$this->price_list->display_inventory),
                TextColumn::make('minimum_order')
                ->label('حداقل سفارش')
                    ->numeric()
                    ->sortable()
                    ->hidden(!$this->price_list->display_minimum_order),
                TextColumn::make('lead_time')
                ->label('زمان انتظار')
                    ->numeric()
                    ->sortable()
                    ->hidden(!$this->price_list->display_lead_time),
                TextColumn::make('reorder_point')
                ->label('نقطه سفارش')
                    ->numeric()
                    ->sortable()
                    ->hidden(!$this->price_list->display_reorder_point),
                TextColumn::make('sales_tax')
                ->label('مالیات فروش')
                    ->numeric()
                    ->sortable()
                    ->hidden(!$this->price_list->display_sales_tax),
                TextColumn::make('purchase_tax')
                ->label('مالیات خرید')
                    ->numeric()
                    ->sortable()
                    ->hidden(!$this->price_list->display_purchase_tax),
                TextColumn::make('type')
                ->label('نوع')
                ->state(fn(Product $record)=> ($record->type == 'Goods') ? 'کالا' : 'خدمات')
                ->color(fn(Product $record)=> ($record->type == 'Goods') ? 'info' : 'success')
                ->hidden(!$this->price_list->display_type),
                TextColumn::make('unit.name')
                ->label('واحد مالیاتی')
                    ->sortable()
                    ->hidden(!$this->price_list->display_unit),
                TextColumn::make('tax.title')
                ->label('نوع مالیات')
                    ->sortable()
                    ->hidden(!$this->price_list->display_tax),
                TextColumn::make('created_at_jalali')
                    ->label('تاریخ ایجاد')
                    ->sortable()
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }
}
