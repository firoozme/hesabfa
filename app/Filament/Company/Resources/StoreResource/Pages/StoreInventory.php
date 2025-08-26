<?php
namespace App\Filament\Company\Resources\StoreResource\Pages;

use Filament\Tables;
use App\Models\Store;
use Filament\Actions;
use App\Models\Product;
use Filament\Forms\Get;
use Filament\Tables\Table;
use App\Models\StoreProduct;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use App\Models\StoreTransactionItem;
use App\Filament\Company\Resources\StoreResource;

class StoreInventory extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = StoreResource::class;

    protected static ?string $title = 'آمار موجودی انبار';
    protected static string $view = 'filament.company.resources.store-resource.pages.store-inventory';
    public $record;

    public function mount($record): void
    {
        $this->record = Store::findOrFail($record);
    }

    public function table(Table $table): Table
    {
        return $table
        ->emptyStateHeading('انبار خالی است')
            ->query(
                $this->record->products()->getQuery()
                           

            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام محصول'),
                // Tables\Columns\TextColumn::make('quantity')
                //     ->label('موجودی فعلی')
                //     ->sortable()
                //     ->getStateUsing(function ($record) {
                //         return StoreProduct::where('store_id', $this->record->id)
                //             ->where('product_id', $record->product_id)
                //             ->sum('quantity');
                //         }),
                Tables\Columns\TextColumn::make('pivot.quantity')
                ->label('موجودی فعلی')
               ->getStateUsing(function ($record) {
                                $store = Store::find($record->store_id);
                                $stock = $store ? $store->getStock($record->product_id) : 0;
                                // dd($record);
                                return $stock;
                    }),
                Tables\Columns\TextColumn::make('total_entries')
                    ->label('تعداد ورودی')
                    ->getStateUsing(function ($record) {
                        return StoreTransactionItem::whereHas('storeTransaction', function ($query) {
                                $query->where('store_id', $this->record->id)
                                ->whereIn('type', ['entry','in']);
                            })
                            ->where('product_id', $record->product_id)
                            ->sum('quantity');
                    }),
                Tables\Columns\TextColumn::make('total_exits')
                    ->label('تعداد خروجی')
                    ->getStateUsing(function ($record) {
                        return StoreTransactionItem::whereHas('storeTransaction', function ($query) {
                                $query->where('store_id', $this->record->id)
                                      ->where('type', 'exit');
                            })
                            ->where('product_id', $record->product_id)
                            ->sum('quantity');
                    }),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([])
            ->paginated([10, 25, 50, 'all'])
            ->defaultSort('store_product.quantity', 'desc'); // اصلاح اینجا
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('بازگشت')
                ->url(fn () => route('filament.company.resources.stores.index'))
                ->color('gray'),
        ];
    }
}