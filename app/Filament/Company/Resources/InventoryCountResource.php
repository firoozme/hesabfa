<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Store;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\InventoryCount;
use Filament\Resources\Resource;
use App\Models\AccountingCategory;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\InventoryCountResource\Pages;
use App\Filament\Company\Resources\InventoryCountResource\RelationManagers;

class InventoryCountResource extends Resource
{
    protected static ?string $model = InventoryCount::class;

    protected static ?string $navigationLabel = 'انبارگردانی';
    protected static ?string $pluralLabel = 'انبارگردانی‌ها';
    protected static ?string $navigationGroup = 'انبارداری';
    protected static ?string $label = 'انبارگردانی';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?int $navigationSort = 6;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('store_id')
                    ->label('انبار')
                    ->options(fn () => Store::where('company_id', auth()->user('company')->id)
                        ->pluck('title', 'id'))
                    ->required()
                    ->reactive(),
                Repeater::make('items')
                    ->label('محصولات')
                    ->schema([
                        Select::make('product_id')
                            ->label('محصول')
                            ->options(fn ($get) => Product::where('company_id', auth()->user('company')->id)
                                ->whereHas('stores', fn ($query) => $query->where('stores.id', $get('../../store_id')))
                                ->pluck('name', 'id'))
                            ->required()
                            ->disabled(fn ($get) => !$get('../../store_id'))
                            ->reactive(),
                        TextInput::make('counted_quantity')
                            ->label('تعداد شمرده‌شده')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('product_price')
                            ->label('قیمت واحد محصول')
                            ->minValue(0)
                            ->required()
                            ->suffix('ریال')
                            ->default(fn ($get) => Product::find($get('product_id'))?->selling_price ?? 0)
                            ->mask(RawJs::make(<<<'JS'
                            $money($input)
                            JS))
                            ->dehydrateStateUsing(function ($state) {
                                return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                            }),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->minItems(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store.title')
                    ->label('انبار')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label('محصول')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('counted_quantity')
                    ->label('تعداد شمرده‌شده')
                    ->sortable(),
                TextColumn::make('product.inventory')
                    ->label('موجودی فعلی')
                    ->sortable(),
                TextColumn::make('verification.status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'در انتظار تأیید',
                        'verified' => 'تأیید شده',
                        'corrected' => 'اصلاح شده',
                        default => 'نامشخص',
                    })
                    ->sortable(),
                TextColumn::make('verification.verified_quantity')
                    ->label('تعداد تأییدشده')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Action برای مرحله دوم (تأیید یا اصلاح)
                Action::make('verify')
    ->label('تأیید/اصلاح')
    ->icon('heroicon-o-check')
    ->visible(fn (InventoryCount $record) => !$record->verification || $record->verification->status === 'pending')
    ->form([
        TextInput::make('verified_quantity')
            ->label('تعداد تأییدشده')
            ->numeric()
            ->minValue(0)
            ->required()
            ->default(fn (InventoryCount $record) => $record->counted_quantity),
            TextInput::make('product_price')
        ->label('قیمت واحد محصول')
        ->minValue(0)
        ->required()
        ->suffix('ریال')
        ->default(fn (InventoryCount $record) =>  $record->product->selling_price ?? 0)
        ->required()
        ->mask(RawJs::make(<<<'JS'
            $money($input)
            JS))
            ->dehydrateStateUsing(function ($state) {
                return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
            }),
    ])
    ->action(function (InventoryCount $record, array $data) {
        $verifiedQuantity = $data['verified_quantity'];
        $status = $verifiedQuantity == $record->counted_quantity ? 'verified' : 'corrected';

        // محاسبه موجودی فعلی و اختلاف
        $previousInventory = $record->product->inventory;
        $newInventory = $verifiedQuantity;
        $quantityChange = $newInventory - $previousInventory;

        // ثبت یا به‌روزرسانی تأیید
        $record->verification()->updateOrCreate(
            ['inventory_count_id' => $record->id],
            [
                'verified_quantity' => $verifiedQuantity,
                'status' => $status,
                'company_id' => $record->company_id,
            ]
        );

        
        // ثبت تراکنش در store_transactions
        if ($quantityChange != 0) {
            $storeTransaction = \App\Models\StoreTransaction::create([
                'store_id' => $record->store_id,
                'type' => $quantityChange > 0 ? 'entry' : 'exit',
                'date' => now(),
                'reference' => 'INV-' . $record->id . '-' . time(),
                // 'company_id' => $record->company_id,
            ]);

            \App\Models\StoreTransactionItem::create([
                'store_transaction_id' => $storeTransaction->id,
                'product_id' => $record->product_id,
                'quantity' => abs($quantityChange),
            ]);
        }

        // محاسبه مبلغ بر اساس قیمت محصول
        $productPrice = (float) str_replace(',', '', $record->product->selling_price) ?? 0; // فرض می‌کنیم مدل Product فیلد price دارد
        
        $amount = abs($quantityChange) * $productPrice;

        // دسته‌بندی‌های پیش‌فرض برای درآمد و هزینه
        $incomeCategoryId = 1; // دسته‌بندی درآمد برای اضافه انبار
        $expenseCategoryId = 2; // دسته‌بندی هزینه برای کسری انبار
        // dump($quantityChange,$record);
        if ($quantityChange > 0) {
            // ثبت درآمد برای کالاهای اضافی
            $income = \App\Models\Income::create([
                'income_category_id' => $incomeCategoryId,
                'amount' => $amount,
                'description' => "درآمد ناشی از اضافه انبار برای محصول {$record->product->name} در انبارگردانی #{$record->id}",
                'company_id' => $record->company_id,
                'status' => 'pending',
            ]);

            // ثبت تراکنش حسابداری برای درآمد
            \App\Models\AccountingTransaction::create([
                'income_id' => $income->id,
                'account_id' => \App\Models\IncomeCategory::find($incomeCategoryId)->account_id ?? null,
                'amount' => $amount,
                'date' => now(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('درآمد ثبت شد')
                ->body("درآمد به مبلغ " . number_format($amount) . " ریال برای اضافه انبار ثبت شد.")
                ->success()
                ->send();
        } elseif ($quantityChange < 0) {
            $last_expense = \App\Models\Expense::where('company_id', auth('company')->user()->id)->withTrashed()->latest()->first();
           // ثبت هزینه برای کالاهای کسری
           $expense = \App\Models\Expense::create([
            'number' => $last_expense ? $last_expense->number + 1 : 1,
            'description' => "هزینه ناشی از کسری انبار برای محصول {$record->product->name} در انبارگردانی #{$record->id}",
            'company_id' => $record->company_id,
            'date' => now(),
            'accounting_auto' => 'auto',
            'status' => 'pending',
        ]);

        $expenseCategory = AccountingCategory::find(131);
        // ثبت آیتم هزینه
        \App\Models\ExpenseItem::create([
            'expense_id' => $expense->id,
            'accounting_category_id' => $expenseCategory->id,
            'amount' => $amount,
            'description' => "کسری انبار برای محصول {$record->product->name}",
        ]);

        // ثبت تراکنش حسابداری برای هزینه
        \App\Models\AccountingTransaction::create([
            'expense_id' => $expense->id,
            'account_id' => $expenseCategory->account_id ?? null,
            'amount' => $amount,
            'date' => now(),
        ]);

        \Filament\Notifications\Notification::make()
            ->title('هزینه ثبت شد')
            ->body("هزینه به مبلغ " . number_format($amount) . " ریال برای کسری انبار ثبت شد.")
            ->success()
            ->send();
        }

        // به‌روزرسانی موجودی محصول
        $record->product->update(['inventory' => $newInventory]);

        \Filament\Notifications\Notification::make()
            ->title('موفقیت')
            ->body('انبارگردانی با موفقیت تأیید شد و در کاردکس ثبت شد.')
            ->success()
            ->send();
    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInventoryCounts::route('/'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user('company')->id);
    }
}
