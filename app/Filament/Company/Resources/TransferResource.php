<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use App\Models\Transfer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Transaction;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\TransferResource\Pages;
use App\Filament\Company\Resources\TransferResource\RelationManagers;

class TransferResource extends Resource
{
    protected static ?string $model = Transfer::class;
    protected static ?string $navigationLabel = 'انتقال';
    protected static ?string $pluralLabel = 'انتقال ها';
    protected static ?string $label = 'انتقال';
    protected static ?string $navigationGroup = 'بانکداری';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Radio::make('accounting_auto')
                    ->label('نحوه ورود کد حسابداری')
                    ->options(['auto' => 'اتوماتیک', 'manual' => 'دستی'])
                    ->default('auto')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $transfer = Transfer::withTrashed()->latest()->first();
                        $id = $transfer ? (++$transfer->id) : 1;
                        $state === 'auto' ? $set('reference_number', $id) : $set('reference_number', '');
                    })
                    ->inline()
                    ->inlineLabel(false),

                TextInput::make('reference_number')
                    ->extraAttributes(['style' => 'direction:ltr'])
                    ->label('کد حسابداری')
                    ->required()
                    ->afterStateHydrated(function (Get $get, callable $set) {
                        $transfer = Transfer::withTrashed()->latest()->first();
                        $id = $transfer ? (++$transfer->id) : 1;
                        $get('accounting_auto') == 'auto' ? $set('reference_number', $id) : $set('reference_number', '');
                    })
                    ->readOnly(fn ($get) => $get('accounting_auto') === 'auto')
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('deleted_at', null))
                    ->live()
                    ->maxLength(255),

                DatePicker::make('transfer_date')
                    ->label('تاریخ انتقال')
                    ->jalali()
                    ->default(now())
                    ->required(),

                TextInput::make('amount')
                    ->label('مبلغ انتقال')
                    ->required()
                    ->mask(RawJs::make('$money($input)'))
                    ->postfix('ریال')
                    ->rules([
                        'min:0',
                        fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                            $sourceType = $get('source_type');
                            $sourceId = $get('source_id');
                            // dd($sourceType , $sourceId);
                            if ($sourceType && $sourceId) {
                                $balance = static::getAccountBalance($sourceType, $sourceId);
                                $amount = (float) str_replace(',', '', $value);
                                if ($balance < $amount) {
                                    $fail("موجودی حساب مبدأ کافی نیست. موجودی فعلی: " . number_format($balance) . " ریال");
                                }
                            }
                        },
                    ]),

                Fieldset::make('مبدا')
                    ->schema([
                        Select::make('source_type')
                            ->label('نوع حساب مبدأ')
                            ->options([
                                'App\Models\CompanyBankAccount' => 'بانک',
                                'App\Models\Fund' => 'صندوق',
                                'App\Models\PettyCash' => 'تنخواه',
                            ])
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(fn (callable $set) => $set('source_id', null)),

                        Select::make('source_id')
                            ->label('حساب مبدأ')
                            ->options(function (callable $get) {
                                $type = $get('source_type');
                                return $type ? $type::pluck('name', 'id') : [];
                            })
                            ->required()
                            ->reactive()
                            ->hint(fn (Get $get) => static::getBalanceHint($get('source_type'), $get('source_id')))
                            ->hintColor('gray'),
                    ]),

                Fieldset::make('مقصد')
                    ->schema([
                        Select::make('destination_type')
                            ->label('نوع حساب مقصد')
                            ->options([
                                'App\Models\CompanyBankAccount' => 'بانک',
                                'App\Models\Fund' => 'صندوق',
                                'App\Models\PettyCash' => 'تنخواه',
                            ])
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(fn (callable $set) => $set('destination_id', null)),

                        Select::make('destination_id')
                            ->label('حساب مقصد')
                            ->options(function (callable $get) {
                                $type = $get('destination_type');
                                return $type ? $type::pluck('name', 'id') : [];
                            })
                            ->required()
                            ->rule(function ($get) {
                                return function ($attribute, $value, $fail) use ($get) {
                                    $sourceType = $get('source_type');
                                    $sourceId = $get('source_id');
                                    $destinationType = $get('destination_type');
                                    $destinationId = $value;
                                    if ($sourceType === $destinationType && $sourceId == $destinationId) {
                                        $fail('مبدأ و مقصد نمی‌توانند یکسان باشند.');
                                    }
                                };
                            }),
                    ]),

                Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
            ]);
    }

    // متد کمکی برای محاسبه موجودی
    public static function getAccountBalance($type, $id)
    {
        if (!$type || !$id) {
            return 0;
        }
        $debits = Transaction::where('account_type', $type)
            ->where('account_id', $id)
            ->sum('debit');
        $credits = Transaction::where('account_type', $type)
            ->where('account_id', $id)
            ->sum('credit');
        return $debits - $credits;
    }

    // متد کمکی برای نمایش موجودی زیر سلکت
    protected static function getBalanceHint($type, $id)
    {
        if (!$type || !$id) {
            return '';
        }
        $balance = static::getAccountBalance($type, $id);
        return 'موجودی: ' . number_format($balance) . ' ریال';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth('company')->user()->id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('کد حسابداری')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transfer_date_jalali')
                    ->label('تاریخ انتقال')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ انتقال')
                    ->money('irr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source.name')
                    ->label('از')
                    ->searchable(),
                Tables\Columns\TextColumn::make('destination.name')
                    ->label('به')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('شرح')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->since()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('سند حسابداری')
                    ->color('warning')
                    ->icon('heroicon-o-eye')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn (Transfer $record): View => view(
                        'filament.pages.actions.transfer.detail',
                        ['record' => $record],
                    )),
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
            'index' => Pages\ManageTransfers::route('/'),
        ];
    }
    protected static ?int $navigationSort = 3;
}
