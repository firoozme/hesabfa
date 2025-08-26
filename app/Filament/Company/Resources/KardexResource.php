<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Store;
use App\Models\Product;
use Filament\Tables\Table;
use App\Models\StoreTransaction;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Company\Resources\KardexResource\Pages;

class KardexResource extends Resource
{
    protected static ?string $navigationLabel = 'کاردکس کالا';
    protected static ?string $pluralLabel = 'کاردکس کالا';
    protected static ?string $navigationGroup = 'انبارداری';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 7;

    protected static ?string $model = StoreTransaction::class;


 

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('تاریخ')
                    ->getStateUsing(fn ($record) => verta($record->storeTransaction->date)->format('Y/m/d H:i'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.title')
                    ->label('انبار')
                    ->getStateUsing(fn ($record) => $record->storeTransaction->store->title),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('محصول')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('store_transaction_type')
                    ->label('نوع تراکنش')
                    // ->getStateUsing(fn ($record) => match ($record->storeTransaction->type) {
                    //     'entry' => 'ورود',
                    //     'exit' => 'خروج',
                    //     'transfer' => $record->storeTransaction->store_id == $record->store_id ? 'انتقال (خروج)' : 'انتقال (ورود)',
                    //     'in' => 'موجودی اولیه',
                    //     default => 'نامشخص',
                    // })
                    ->getStateUsing(fn ($record) => match (preg_replace('/-\d+$/', '', $record->storeTransaction->reference)) {
                        'SALE' => 'فروش',
                        'SALE-INV' => 'فروش',
                        'RET-SALE' => 'برگشت فروش',
                        'INV' => 'خرید',
                        'RET-INV' => 'برگشت خرید',
                        'INIT' => 'موجودی اولیه',
                        default => $record->storeTransaction->reference,
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->join('store_transactions', 'store_transaction_items.store_transaction_id', '=', 'store_transactions.id')
                                     ->orderBy('store_transactions.type', $direction);
                    }),
                Tables\Columns\TextColumn::make('quantity_in')
                    ->label('ورودی')
                    ->getStateUsing(fn ($record) => match ($record->storeTransaction->type) {
                        'entry' => $record->quantity,
                        'transfer' => $record->storeTransaction->destination_id == $record->store_id ? $record->quantity : 0,
                        'in' => $record->quantity,
                        default => 0,
                    }),
                Tables\Columns\TextColumn::make('quantity_out')
                    ->label('خروجی')
                    ->getStateUsing(fn ($record) => match ($record->storeTransaction->type) {
                        'exit' => $record->quantity,
                        'transfer' => $record->storeTransaction->store_id == $record->store_id ? $record->quantity : 0,
                        default => 0,
                    }),
                Tables\Columns\TextColumn::make('balance')
                    ->label('موجودی')
                    ->getStateUsing(function ($record) {
                        // محاسبه موجودی تا این تراکنش
                        $previousTransactions = StoreTransactionItem::where('product_id', $record->product_id)
                            ->whereHas('storeTransaction', function ($query) use ($record) {
                                $query->where('store_transactions.created_at', '<=', $record->storeTransaction->created_at)
                                    ->where(function ($q) use ($record) {
                                        $q->where('store_transactions.store_id', $record->store_id)
                                          ->orWhere('store_transactions.destination_id', $record->store_id);
                                    });
                            })
                            ->with('storeTransaction')
                            ->orderBy('created_at')
                            ->get();

                        $balance = 0;
                        foreach ($previousTransactions as $item) {
                            $type = $item->storeTransaction->type;
                            if ($type === 'entry' || $type === 'in') {
                                $balance += $item->quantity;
                            } elseif ($type === 'exit') {
                                $balance -= $item->quantity;
                            } elseif ($type === 'transfer') {
                                if ($item->storeTransaction->store_id == $record->store_id) {
                                    $balance -= $item->quantity; // خروج از انبار مبدأ
                                } elseif ($item->storeTransaction->destination_id == $record->store_id) {
                                    $balance += $item->quantity; // ورود به انبار مقصد
                                }
                            }
                        }
                        return $balance;
                    })
                    ,
                Tables\Columns\TextColumn::make('reference')
                    ->label('شماره حواله')
                    ->getStateUsing(fn ($record) => $record->storeTransaction->reference)
                    ,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('انبار')
                    ->options(fn () => Store::where('company_id', auth()->user('company')->id)
                        ->pluck('title', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $data['value'] 
                            ? $query->whereHas('storeTransaction', function (Builder $query) use ($data) {
                                $query->where('store_id', $data['value'])
                                      ->orWhere('destination_id', $data['value']);
                            })
                            : $query;
                    }),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('محصول')
                    ->options(fn () => Product::where('company_id', auth()->user('company')->id)
                        ->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $data['value'] 
                            ? $query->where('product_id', $data['value'])
                            : $query;
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('pdf')
                ->label('چاپ')
                ->color('warning')
                ->url(fn(Model $record): string => route('store.transaction.pdf',['id'=>$record->id]))
                ->openUrlInNewTab(),
               
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKardexes::route('/'),
            // 'create' => Pages\CreateKardex::route('/create'),
            // 'edit' => Pages\EditKardex::route('/{record}/edit'),
            // 'view' => Pages\ViewKardex::route('/{record}'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return StoreTransactionItem::query()
            ->with(['storeTransaction', 'storeTransaction.store'])
            ->whereHas('storeTransaction', function (Builder $query) {
                $query->whereHas('store', function (Builder $query) {
                    $query->where('company_id', auth()->user('company')->id);
                });
            });
    }
}
