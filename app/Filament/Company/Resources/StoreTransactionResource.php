<?php

namespace App\Filament\Company\Resources;

use Closure;
use Filament\Forms;
use Filament\Tables;
use App\Models\Store;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StoreTransaction;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use App\Models\StoreTransactionItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\StoreTransactionResource\Pages;
use App\Filament\Company\Resources\StoreTransactionResource\RelationManagers;

class StoreTransactionResource extends Resource
{
    protected static ?string $model = StoreTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'حواله ها';
    protected static ?string $navigationGroup = 'انبارداری';
    protected static ?string $label = '';
    protected static ?string $pluralLabel = 'حواله ها';

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->whereHas('store', function (Builder $query) {
            $query->where('company_id', auth()->user('company')->id);
        });
}
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('store_id')
                    ->label('انبار مبدا')
                    ->relationship('store', 'title')
                    ->options(Store::pluck('title', 'id')->toArray())
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('نوع')
                    ->options([
                        'entry' => 'ورود',
                        'exit' => 'خروج (حواله)',
                        'transfer' => 'انتقال (حواله)',
                    ])
                    ->live()
                    ->required(),
                Forms\Components\TextInput::make('reference')
                    ->label('شماره حواله')
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('deleted_at', null);
                    })
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('تاریخ')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('destination_type')
                    ->label('نوع مقصد')
                    ->options([
                        'App\Models\Store' => 'انبار',
                        'Customer' => 'مشتری', // فرض بر وجود مدل Customer
                    ])
                    ->reactive()
                    ->requiredIf('type', '!=', 'entry'),
                Forms\Components\Select::make('destination_id')
                    ->label('مقصد')
                    ->options(function (callable $get) {
                        if ($get('destination_type') === 'App\Models\Store') {
                            return Store::pluck('title', 'id')->toArray();
                        }
                        return [];
                    })
                    ->requiredIf('type', '!=', 'entry'),
                Forms\Components\Repeater::make('items')
                    ->label('محصولات')
                    ->relationship('items')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('محصول')
                            ->relationship('product', 'name')
                            ->options(Product::pluck('name', 'id')->toArray())
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('تعداد')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->rules([
                                function ($get) {
                                    return function (string $attribute, $value, Closure $fail) use ($get) {
                                        $parentType = $get('../../type');
                                        if ($parentType === 'exit') {
                                            $store = Store::find($get('../../store_id'));
                                            $productId = $get('product_id');
                                            $stock = $store->products()->where('product_id', $productId)->first()->pivot->quantity ?? 0;
                                            if ($stock < $value) {
                                                $fail("موجودی کافی نیست. موجودی فعلی: $stock");
                                            }
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->columns(2)
                    ->required(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.title')->label('انبار'),
                Tables\Columns\TextColumn::make('type')->label('نوع')
                ->formatStateUsing(fn(string $state) => match ($state) {
                    'in' => 'موجودی اولیه',
                    'entry' => 'ورودی',
                    'exit' => 'خروجی',
                })
                ->color(fn(string $state) => match ($state) {
                    'in' => 'warning',
                    'entry' => 'success',
                    'exit' => 'danger',
                }),
                Tables\Columns\TextColumn::make('reference')->label('شماره حواله'),
                Tables\Columns\TextColumn::make('date_jalali')->label('تاریخ'),
                Tables\Columns\TextColumn::make('items_count')->label('تعداد آیتم‌ها')->counts('items'),
            ])
            ->defaultSort('created_at','desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'entry' => 'ورود',
                        'exit' => 'خروج',
                        'transfer' => 'انتقال',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('pdf')
                ->label('خروجی PDF')
                ->color('warning')
                ->url(fn(Model $record): string => route('store.transaction.pdf',['id'=>$record->id]))
                ->openUrlInNewTab(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreTransactions::route('/'),
            'create' => Pages\CreateStoreTransaction::route('/create'),
            'edit' => Pages\EditStoreTransaction::route('/{record}/edit'),
        ];
    }
    protected static ?int $navigationSort = 8;
}
