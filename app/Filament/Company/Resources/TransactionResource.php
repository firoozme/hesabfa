<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use App\Traits\DefaultTableSettings;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\TransactionResource\Pages;
use App\Filament\Company\Resources\TransactionResource\RelationManagers;

class TransactionResource extends Resource
{
    use DefaultTableSettings; // استفاده از Trait
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationLabel = 'اسناد حسابداری';
    protected static ?string $pluralLabel = 'اسناد حسابداری';
    protected static ?string $navigationGroup = 'حسابداری';
    protected static ?string $label = 'سند حسابداری';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('تراکنشی وجود ندارد')
            ->query(Transaction::query()->where('company_id', auth('company')->user()->id)->latest())
            ->columns([
                TextColumn::make('account.name')
                    ->label('حساب')
                    ->description(function (Model $record) {
                        if ($record->account_type === 'App\Models\CompanyBankAccount') {
                            return 'بانک';
                        } elseif ($record->account_type === 'App\Models\Fund') {
                            return 'صندوق';
                        } elseif ($record->account_type === 'App\Models\PettyCash') {
                            return 'تنخواه';
                        } elseif ($record->account_type === 'App\Models\Capital') {
                            return 'سرمایه';
                        } else {
                            return '-';
                        }
                    })
                    ->searchable(),
                TextColumn::make('description')
                    ->label('شرح')
                    ->searchable(),
                TextColumn::make('debit')
                    ->label('بدهکار')
                    ->money('irr')
                    ->color(function ($state) {
                        return $state != 0 ? 'danger' : null;
                    }),
                TextColumn::make('credit')
                    ->label('بستانکار')
                    ->money('irr')
                    ->color(function ($state) {
                        return $state != 0 ? 'success' : null;
                    }),
                TextColumn::make('created_at_jalali')
                    ->label('تاریخ')
                    ->sortable(['created_at']),
            ])
            ->filters([
                // فیلتر جستجو برای توضیحات
                Filter::make('description')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('description')
                            ->label('جستجو در توضیحات')
                            ->placeholder('جستجو...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $data['description']
                            ? $query->where('description', 'like', '%' . $data['description'] . '%')
                            : $query;
                    }),

                // فیلتر نوع حساب
                SelectFilter::make('account_type')
                    ->label('نوع حساب')
                    ->options([
                        'App\Models\CompanyBankAccount' => 'بانک',
                        'App\Models\Fund' => 'صندوق',
                        'App\Models\PettyCash' => 'تنخواه',
                        'App\Models\Capital' => 'سرمایه',
                    ])
                    ->query(function ($query, array $data) {
                        return $data['value']
                            ? $query->where('account_type', $data['value'])
                            : $query;
                    }),

                // فیلتر تاریخ تراکنش
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('از تاریخ')
                            ->jalali()
                            ->placeholder('انتخاب تاریخ'),
                        DatePicker::make('to_date')
                            ->jalali()
                            ->label('تا تاریخ')
                            ->placeholder('انتخاب تاریخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from_date'], fn($q) => $q->whereDate('created_at', '>=', $data['from_date']))
                            ->when($data['to_date'], fn($q) => $q->whereDate('created_at', '<=', $data['to_date']));
                    }),

                // فیلتر برای بدهکار
                TernaryFilter::make('debit')
                    ->label('بدهکار')
                    ->trueLabel('دارای بدهکار')
                    ->falseLabel('بدون بدهکار')
                    ->queries(
                        true: fn($query) => $query->where('debit', '>', 0),
                        false: fn($query) => $query->where('debit', 0),
                        blank: fn($query) => $query
                    ),

                // فیلتر برای بستانکار
                TernaryFilter::make('credit')
                    ->label('بستانکار')
                    ->trueLabel('دارای بستانکار')
                    ->falseLabel('بدون بستانکار')
                    ->queries(
                        true: fn($query) => $query->where('credit', '>', 0),
                        false: fn($query) => $query->where('credit', 0),
                        blank: fn($query) => $query
                    ),

                // فیلتر برای انتقال
                TernaryFilter::make('transfer_id')
                    ->label('انتقال')
                    ->trueLabel('مرتبط با انتقال')
                    ->falseLabel('بدون انتقال')
                    ->queries(
                        true: fn($query) => $query->whereNotNull('transfer_id'),
                        false: fn($query) => $query->whereNull('transfer_id'),
                        blank: fn($query) => $query
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)

            ->actions([
                // می‌توانید اکشن‌های ویرایش، حذف و غیره را اینجا اضافه کنید
            ])
            ->bulkActions([
                // می‌توانید اکشن‌های گروهی مانند حذف را اینجا اضافه کنید
            ]);
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
            'index' => Pages\ListTransactions::route('/'),
            // 'create' => Pages\CreateTransaction::route('/create'),
            // 'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
