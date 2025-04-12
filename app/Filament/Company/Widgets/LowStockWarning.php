<?php

namespace App\Filament\Company\Widgets;

use Filament\Tables;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWarning extends BaseWidget
{
    protected static ?int $sort = 100; // عدد کمتر یعنی اولویت بالاتر

    protected function getTableHeading(): string
    {
        return 'هشدار موجودی کم'; // عنوان دلخواه جدول
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()->where('company_id',auth()->user('company')->id)->whereColumn('inventory', '<=', 'reorder_point')
            )
            ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->label('نام محصول'),
                    Tables\Columns\TextColumn::make('inventory')
                        ->label('موجودی فعلی')
                        ->color('danger')
                        ->formatStateUsing(fn($state) => number_format($state)),
                    Tables\Columns\TextColumn::make('reorder_point')
                        ->label('نقطه سفارش مجدد')
                        ->formatStateUsing(fn($state) => number_format($state)),
            ]);
    }

    public static function canView(): bool
{
    return Product::query()->where('company_id',auth()->user('company')->id)->whereColumn('inventory', '<=', 'reorder_point')->count();
}
}
