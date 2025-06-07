<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use Filament\Forms\Get;
use App\Models\Transfer;
use Filament\Forms\Form;
use Illuminate\View\View;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use App\Models\CompanyBankAccount;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Radio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\CompanyBankAccountResource\Pages;
use App\Filament\Company\Resources\CompanyBankAccountResource\RelationManagers;

class CompanyBankAccountResource extends Resource
{
    protected static ?string $model = CompanyBankAccount::class;
    protected static ?string $navigationLabel = 'حساب بانکی';
    protected static ?string $pluralLabel = 'حسابهای بانکی';
    protected static ?string $label = 'حسابهای بانکی';
    protected static ?string $navigationGroup = 'بانکداری';
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
}
    public static function form(Form $form): Form
    {
        return $form
        
            ->schema([
                Radio::make('accounting_auto')
                ->label('نحوه ورود کد حسابداری')
                ->options([
                    'auto' => 'اتوماتیک',
                    'manual' => 'دستی',
                ])
                ->default('auto')
                ->live()
                ->afterStateUpdated(
                    function($state, callable $set){
                        $companyBankAccount = CompanyBankAccount::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                        $accounting_code      = $companyBankAccount ? (++$companyBankAccount->accounting_code) : 1;
                        $state === 'auto' ? $set('accounting_code', $accounting_code) : $set('accounting_code', '');
                    }

                )
                ->inline()
                ->inlineLabel(false),
            Forms\Components\TextInput::make('accounting_code')
            ->extraAttributes(['style' => 'direction:ltr'])
                ->label('کد حسابداری')
                ->required()
                ->afterStateHydrated(function (Get $get) {
                    $companyBankAccount = CompanyBankAccount::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                    $accounting_code = $companyBankAccount ? (++$companyBankAccount->accounting_code) : 1;
                    return ($get('accounting_auto') == 'auto') ?? $accounting_code;
                })
                ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                ->default(
                    function (Get $get) {
                    $companyBankAccount = CompanyBankAccount::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                    $accounting_code      = $companyBankAccount ? (++$companyBankAccount->accounting_code) : 1;
                    return ($get('accounting_auto') == 'auto') ? $accounting_code  : '';
                })
                ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                    return $rule
                    ->where('company_id', auth('company')->user()->id) // شرط company_id
                    ->where('deleted_at', null); //
                })
                ->live()
                ->maxLength(255),
            Forms\Components\TextInput::make('name')
                ->label('عنوان حساب')
                ->required(),
                Forms\Components\TextInput::make('balance')
                ->label('موجودی اولیه')
                ->hidden(fn($context) => $context === 'edit')
                ->postfix('ریال')
                ->default(0)
                ->required()
                ->mask(RawJs::make(<<<'JS'
                    $money($input)
                    JS))
                    ->dehydrateStateUsing(function ($state) {
                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                    }),
            Forms\Components\Select::make('bank_id')
                    ->label('بانک')
                    ->searchable()
                    ->preload()
                    ->options(Bank::where('company_id',auth('company')->user()->id)->pluck('name', 'id')->toArray())
                    ->suffixAction(
                        Action::make('add_bank')
                            ->label('اضافه کردن بانک')
                            ->icon('heroicon-o-plus') // آیکون دلخواه
                            ->modalHeading('ایجاد بانک جدید')
                            ->action(function (array $data) {
                                $unit = Bank::create(['name' => $data['name']]);
                                return $unit->id; // برای آپدیت سلکت‌باکس
                            })
                            ->form([
                                TextInput::make('name')
                                    ->label('نام')
                                    ->required(),
                            ])
                            ->after(function ($livewire) {
                                $livewire->dispatch('refreshForm'); // رفرش فرم بعد از اضافه کردن
                            })
                    )
                    ->Required(),
                Forms\Components\TextInput::make('card_number')
                    ->label('شماره کارت')
                    ->mask('9999-9999-9999-9999')
                    ->extraAttributes(['style' => 'direction:ltr'])
                    ->Required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_number')
                ->label('شماره حساب')
                ->extraAttributes(['style' => 'direction:ltr'])
                ->regex('/^\d+$/i')
                ->maxLength(255),
            Forms\Components\TextInput::make('iban')
                ->label('شماره شبا')
                ->extraAttributes(['style' => 'direction:ltr'])
                ->regex('/^\d{24}$/')
                ->prefix('IR')
                ->maxLength(255),
            Forms\Components\TextInput::make('account_holder')
                ->label('نام صاحب حساب')
                ->alpha(),
            Forms\Components\TextInput::make('pos_number')
                ->label('شماره pos')
                ->extraAttributes(['style' => 'direction:ltr']),
            Forms\Components\Textarea::make('description')
                ->label('توضیحات')
                ->columnSpanFull(),
            ]);
            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('accounting_code')
                    ->label('کد حسابداری')
                    ->searchable()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('name')
                    ->label('عنوان حساب')
                    ->sortable()
                    ->description(function(Model $record){
                        // محاسبه موجودی
                        $balance = $record->incomingTransfers()->sum('amount') - $record->outgoingTransfers()->sum('amount');
                        // $balance = $record->incomingTransfers()->sum('amount');

                        // تغییر رنگ توضیحات بر اساس موجودی
                        $color = 'black'; // رنگ پیش‌فرض
                        if ($balance > 0) {
                            $color = 'green'; // رنگ سبز برای موجودی مثبت
                        } elseif ($balance < 0) {
                            $color = 'red'; // رنگ قرمز برای موجودی منفی
                        } elseif ($balance == 0) {
                            $color = 'orange'; // رنگ نارنجی برای موجودی صفر
                        }

                        return new HtmlString("<span style='color: {$color};'>" . number_format($balance) . " ریال</span>");
                    })
                    ->searchable(),
                    Tables\Columns\TextColumn::make('bank.name')
                    ->label('بانک')

                    ->searchable(),

                Tables\Columns\TextColumn::make('card_number')
                    ->label('شماره کارت')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('شماره حساب')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('iban')
                    ->label('شماره شبا')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_holder')
                    ->label('نام صاحب حساب')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pos_number')
                    ->label('شماره پوز')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at_jalali')
                    ->sortable()
                    ->label('تاریخ ایجاد'),


            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                ->label('گردش')
                ->color('warning')
                ->icon('heroicon-o-eye')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(fn (CompanyBankAccount $record): View => view(
                    'filament.pages.actions.company-bank-account.detail',
                    ['record' => $record],
                )),
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
            'index' => Pages\ManageCompanyBankAccounts::route('/'),
        ];
    }
    protected static ?int $navigationSort = 2;
}
