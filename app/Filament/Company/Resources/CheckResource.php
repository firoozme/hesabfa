<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Bank;
use App\Models\Fund;
use Filament\Tables;
use App\Models\Check;
use App\Models\Person;
use App\Models\Transfer;
use Filament\Forms\Form;
use App\Models\PettyCash;
use Filament\Tables\Table;
use App\Models\Transaction;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use App\Models\CompanyBankAccount;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\CheckResource\Pages;
use App\Filament\Company\Resources\CheckResource\RelationManagers;

class CheckResource extends Resource
{
    protected static ?string $model = Check::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'چک';
    protected static ?string $pluralLabel = 'چک ها';
    protected static ?string $label = 'چک';
    protected static ?string $navigationGroup = 'بانکداری';
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
    }
    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Select::make('type')
                ->label('نوع چک')
                ->options([
                    'receivable' => 'دریافتی',
                    'payable' => 'پرداختی',
                ])
                ->required()
                ->columnSpanFull(),
            TextInput::make('serial_number')
                ->label('شماره صیاد')
                ->required()
                ->unique(ignorable: fn ($record) => $record)
                ->maxLength(255),
            Select::make('payer')
                ->label('پرداخت‌کننده')
                ->options(Person::where('company_id',auth('company')->user()->id)->pluck('fullname', 'id')->toArray())
                ->searchable()
                ->preload()
                ->required(),
                Select::make('bank')
                ->label('بانک')
                ->options(Bank::where('company_id',auth('company')->user()->id)->pluck('name', 'name')->toArray())
                ->searchable()
                ->required(),
            TextInput::make('branch')
                ->label('شعبه')
                ->required()
                ->maxLength(255),
            TextInput::make('amount')
                ->label('مبلغ')
                ->required()
                ->minValue(0)
                ->postfix('ریال')
                ->default(0)
                ->required()
                ->mask(RawJs::make(<<<'JS'
                    $money($input)
                    JS))
                    ->dehydrateStateUsing(function ($state) {
                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                    }),
            DatePicker::make('date_received')
                ->label('تاریخ دریافت')
                ->required()
                ->jalali(),
            DatePicker::make('due_date')
                ->label('تاریخ سررسید')
                ->required()
                ->jalali(),
            // Select::make('status')
            //     ->label('وضعیت')
            //     ->options([
            //         'overdue' => 'سررسید گذشته',
            //         'in_progress' => 'در جریان وصول',
            //         'received' => 'وصول شده',
            //         'returned' => 'عودت شده',
            //         'cashed' => 'خرج شده',
            //     ])
            //     ->required(),
            
            // اگر چک به موجودیت خاصی مرتبط است (رابطه پلی‌مورفیک)
            Select::make('checkable_id')
                ->label('مرتبط با')
                ->options(Person::where('company_id',auth('company')->user()->id)->pluck('fullname', 'id')->toArray())
                ->searchable()
                ->required(),
            Select::make('checkable_type')
                ->label('نوع موجودیت')
                ->options([
                    'App\Models\Person' => 'شخص',
                    // اگر موجودیت‌های دیگری دارید، اینجا اضافه کنید
                ])
                ->default('App\Models\Person')
                ->hidden()
                ->required(),
            Textarea::make('description')
                ->label('یادداشت')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_number')->label('شماره صیاد'),
                TextColumn::make('payer')->label('پرداخت کننده')
                ->formatStateUsing(function($state){
                    $supplier = Person::find($state);
                    return $supplier->fullname;
                }),
                TextColumn::make('bank')->label('بانک'),
                TextColumn::make('branch')->label('شعبه'),
                TextColumn::make('amount')->label('مبلغ')->sortable()->formatStateUsing(fn($state) => number_format($state)),
                TextColumn::make('date_received_jalali')->label('تاریخ دریافت'),
                TextColumn::make('due_date_jalali')->label('تاریخ سررسید'),
                TextColumn::make('status_label')->label('وضعیت'),
                TextColumn::make('type')->label('نوع چک')
                ->formatStateUsing(function($state){
                    return ($state == 'receivable') ? 'دریافتی' : 'پرداختی';
                }),
                TextColumn::make('description')->label('یادداشت')
            ])
            ->filters([

                SelectFilter::make('type')
                ->label('نوع چک')
                ->options([
                    'receivable' => 'دریافتی',
                    'payable' => 'پرداختی',

                ])
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('mark_as_received')
                    ->label('وصول شد')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('account_type')
                            ->label('نوع حساب')
                            ->options([
                                'App\Models\PettyCash' => 'تنخواه',
                                'App\Models\Fund' => 'صندوق',
                                'App\Models\CompanyBankAccount' => 'حساب بانکی',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('account_id', null); // ریست کردن حساب انتخاب‌شده
                            }),
                        Select::make('account_id')
                            ->label('حساب')
                            ->options(function (callable $get) {
                                $accountType = $get('account_type');
                                if ($accountType === 'App\Models\PettyCash') {
                                    return PettyCash::where('company_id', auth()->user()->id)
                                        ->get()
                                        ->mapWithKeys(fn ($account) => [$account->id => "{$account->name} (موجودی: " . number_format($account->balance) . ")"])
                                        ->toArray();
                                } elseif ($accountType === 'App\Models\Fund') {
                                    return Fund::where('company_id', auth()->user()->id)
                                        ->get()
                                        ->mapWithKeys(fn ($account) => [$account->id => "{$account->name} (موجودی: " . number_format($account->balance) . ")"])
                                        ->toArray();
                                } elseif ($accountType === 'App\Models\CompanyBankAccount') {
                                    return CompanyBankAccount::where('company_id', auth()->user()->id)
                                        ->get()
                                        ->mapWithKeys(fn ($account) => [$account->id => "{$account->name} (موجودی: " . number_format($account->balance) . ")"])
                                        ->toArray();
                                }
                                return [];
                            })
                            ->required()
                            ->searchable(),
                        DatePicker::make('received_date')
                            ->label('تاریخ وصول')
                            ->required()
                            ->jalali()
                            ->default(now())
                            ->afterOrEqual('date_received'),
                    ])
                    ->action(function (Check $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                           
                            // بررسی موجودی برای چک پرداختی
                            if ($record->type === 'payable') {
                                $account = match ($data['account_type']) {
                                    'App\Models\PettyCash' => PettyCash::find($data['account_id']),
                                    'App\Models\Fund' => Fund::find($data['account_id']),
                                    'App\Models\CompanyBankAccount' => CompanyBankAccount::find($data['account_id']),
                                    default => null,
                                };
                                if ($account && $account->balance < $record->amount) {
                                    Notification::make()
                                    ->title('خطا')
                                    ->body('موجودی حساب کافی نیست.')
                                    ->danger()
                                    ->send();
                                    return;
                                }
                            }

                            // تغییر وضعیت چک
                            $record->update([
                                'status' => 'received',
                                'received_date' => $data['received_date'],
                            ]);

                            // ثبت تراکنش‌ها در جدول transfers (رعایت اصل دوبل)
                            $amount = $record->amount;
                            $companyId = $record->company_id;
                            $description = 'وصول چک ' . ($record->type === 'receivable' ? 'دریافتی' : 'پرداختی') . ' با شماره صیاد ' . $record->serial_number;

                            // تراکنش برای وصول چک
                            $transferData = [
                                'company_id' => $companyId,
                                'amount' => $amount,
                                'description' => $description,
                                'transfer_date' => $data['received_date'],
                                'transaction_type' => 'transfer',
                                'paymentable_type' => 'App\Models\Check',
                                'paymentable_id' => $record->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            if ($record->type === 'receivable') {
                                // چک دریافتی: حساب انتخاب‌شده مقصد، حساب شخص مبدأ
                                $transferData['destination_type'] = $data['account_type'];
                                $transferData['destination_id'] = $data['account_id'];
                                $transferData['source_type'] = 'App\Models\Person';
                                $transferData['source_id'] = $record->payer;
                            } else {
                                // چک پرداختی: حساب انتخاب‌شده مبدأ، حساب شخص مقصد
                                $transferData['source_type'] = $data['account_type'];
                                $transferData['source_id'] = $data['account_id'];
                                $transferData['destination_type'] = 'App\Models\Person';
                                $transferData['destination_id'] = $record->payer;
                            }

                            // ثبت تراکنش
                            Transfer::create($transferData);
                        });
                    })
                    ->visible(fn (Check $record) => $record->status !== 'received'),
                Tables\Actions\EditAction::make()
                ->visible(fn (Check $record) => $record->status !== 'received'),
                Tables\Actions\DeleteAction::make()
                ->visible(fn (Check $record) => $record->status !== 'received'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageChecks::route('/'),
        ];
    }
    protected static ?int $navigationSort = 2;
}
