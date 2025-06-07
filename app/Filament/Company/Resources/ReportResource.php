<?php

namespace App\Filament\Company\Resources;

use Filament\Tables;
use App\Models\Store;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\StoreTransactionItem;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Company\Resources\ReportResource\Pages;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationLabel = 'گزارش کل پروژه';
    protected static ?string $pluralLabel = 'گزارش کل پروژه';
    protected static ?string $navigationGroup = 'گزارش‌ها';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 8;

    

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
                    ->getStateUsing(fn ($record) => $record->storeTransaction->store->title)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('محصول')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('store_transaction_type')
                    ->label('نوع تراکنش')
                    ->getStateUsing(fn ($record) => match ($record->storeTransaction->type) {
                        'entry' => 'ورود',
                        'exit' => 'خروج',
                        'transfer' => $record->storeTransaction->store_id == $record->store_id ? 'انتقال (خروج)' : 'انتقال (ورود)',
                        'in' => 'موجودی اولیه',
                        default => 'نامشخص',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_in')
                    ->label('ورودی')
                    ->getStateUsing(fn ($record) => match ($record->storeTransaction->type) {
                        'entry' => $record->quantity,
                        'transfer' => $record->storeTransaction->destination_id == $record->store_id ? $record->quantity : 0,
                        'in' => $record->quantity,
                        default => 0,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_out')
                    ->label('خروجی')
                    ->getStateUsing(fn ($record) => match ($record->storeTransaction->type) {
                        'exit' => $record->quantity,
                        'transfer' => $record->storeTransaction->store_id == $record->store_id ? $record->quantity : 0,
                        default => 0,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('موجودی')
                    ->getStateUsing(function ($record) {
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
                                    $balance -= $item->quantity;
                                } elseif ($item->storeTransaction->destination_id == $record->store_id) {
                                    $balance += $item->quantity;
                                }
                            }
                        }
                        return $balance;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.selling_price')
                    ->label('قیمت فروش')
                    ->formatStateUsing(fn ($state) => number_format($state) . ' ریال')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_status')
                    ->label('وضعیت تخفیف')
                    ->getStateUsing(function ($record) {
                        $discount = $record->product->discount;
                        if (!$discount) {
                            return 'بدون تخفیف';
                        }
                        $today = now()->startOfDay();
                        $startDate = \Carbon\Carbon::parse($discount->start_date);
                        $endDate = \Carbon\Carbon::parse($discount->end_date);
                        $daysOfWeek = json_decode($discount->days_of_week, true);
                        $currentDay = verta()->format('l'); // روز به زبان فارسی
                        $dayMapping = [
                            'دوشنبه' => 'monday',
                            'سه‌شنبه' => 'tuesday',
                            'چهارشنبه' => 'wednesday',
                            'پنج‌شنبه' => 'thursday',
                            'جمعه' => 'friday',
                            'شنبه' => 'saturday',
                            'یک‌شنبه' => 'sunday',
                        ];
                        $englishDay = $dayMapping[$currentDay] ?? '';
                        $isActive = $today->between($startDate, $endDate) && in_array($englishDay, $daysOfWeek);
                        return $isActive ? 'دارای تخفیف (' . $discount->percentage . '%)' : 'تخفیف غیرفعال';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('انبار')
                    ->options(fn () => Store::where('company_id', auth()->user('company')->id)
                        ->pluck('title', 'id')),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('محصول')
                    ->options(fn () => Product::where('company_id', auth()->user('company')->id)
                        ->pluck('name', 'id')),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('از تاریخ')
                            ->jalali(),
                        DatePicker::make('date_to')
                            ->label('تا تاریخ')
                            ->jalali(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->when(
                            $data['date_from'],
                            fn (Builder $query, $date) => $query->whereHas('storeTransaction', fn ($q) => $q->whereDate('date', '>=', verta($date)->toCarbon()))
                        )->when(
                            $data['date_to'],
                            fn (Builder $query, $date) => $query->whereHas('storeTransaction', fn ($q) => $q->whereDate('date', '<=', verta($date)->toCarbon()))
                        );
                    }),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('نوع تراکنش')
                    ->options([
                        'entry' => 'ورود',
                        'exit' => 'خروج',
                        'transfer' => 'انتقال',
                        'in' => 'موجودی اولیه',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->whereHas('storeTransaction', fn ($q) => $q->where('type', $data))),
                Tables\Filters\SelectFilter::make('discount_status')
                    ->label('وضعیت تخفیف')
                    ->options([
                        'active' => 'دارای تخفیف',
                        'inactive' => 'بدون تخفیف',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $today = now()->startOfDay();
                        $dayMapping = [
                            'دوشنبه' => 'monday',
                            'سه‌شنبه' => 'tuesday',
                            'چهارشنبه' => 'wednesday',
                            'پنج‌شنبه' => 'thursday',
                            'جمعه' => 'friday',
                            'شنبه' => 'saturday',
                            'یک‌شنبه' => 'sunday',
                        ];
                        $currentDay = verta()->format('l');
                        $englishDay = $dayMapping[$currentDay] ?? '';

                        if ($data === 'active') {
                            $query->whereHas('product.discount', function ($q) use ($today, $englishDay) {
                                $q->where('start_date', '<=', $today)
                                  ->where('end_date', '>=', $today)
                                  ->whereRaw('JSON_CONTAINS(days_of_week, ?)', ['"' . $englishDay . '"']);
                            });
                        } elseif ($data === 'inactive') {
                            $query->whereDoesntHave('product.discount')
                                  ->orWhereHas('product.discount', function ($q) use ($today, $englishDay) {
                                      $q->where('start_date', '>', $today)
                                        ->orWhere('end_date', '<', $today)
                                        ->orWhereRaw('NOT JSON_CONTAINS(days_of_week, ?)', ['"' . $englishDay . '"']);
                                  });
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('خروجی PDF')
                    ->color('warning')
                    // ->url(fn () => route('report.pdf'))
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return StoreTransactionItem::query()
            ->with(['storeTransaction', 'storeTransaction.store', 'product', 'product.discount'])
            ->whereHas('storeTransaction', function (Builder $query) {
                $query->whereHas('store', function (Builder $query) {
                    $query->where('company_id', auth()->user('company')->id);
                });
            });
    }

    protected static bool $shouldRegisterNavigation = false;

}
