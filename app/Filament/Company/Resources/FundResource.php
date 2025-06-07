<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use App\Models\Fund;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Illuminate\View\View;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\FundResource\Pages;
use App\Filament\Company\Resources\FundResource\RelationManagers;

class FundResource extends Resource
{
    protected static ?string $model = Fund::class;
    protected static ?string $navigationLabel = 'صندوق';
    protected static ?string $pluralLabel = 'صندوق ها';
    protected static ?string $label = 'صندوق ها';
    protected static ?string $navigationGroup = 'بانکداری';
    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

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
                            $fund = Fund::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                            $accounting_code      = $fund ? (++$fund->accounting_code) : 1;
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
                        $fund = Fund::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                        $accounting_code      = $fund ? (++$fund->accounting_code) : 1;
                        return ($get('accounting_auto') == 'auto') ? $accounting_code : '';
                    })
                    ->default(function (Get $get) {
                        $fund = Fund::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                        $accounting_code      = $fund ? (++$fund->accounting_code) : 1;
                        return ($get('accounting_auto') == 'auto') ? $accounting_code : '';
                    })
                    ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                         return $rule
                        ->where('company_id', auth('company')->user()->id) // شرط company_id
                        ->where('deleted_at', null); //
                    })
                    ->live()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('نام')
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('deleted_at', null);
                    })
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('توضیحات')
                    ->columnSpanFull(),
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                ])
                    ->schema([
                        Forms\Components\TextInput::make('switch_number')
                            ->label('شماره سوئیچ پرداخت')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->maxLength(255),
                        Forms\Components\TextInput::make('terminal_number')
                            ->label('شماره ترمینال پرداخت')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->maxLength(255),
                        Forms\Components\TextInput::make('merchant_number')
                            ->label('شماره پذیرنده فروشگاهی')
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->maxLength(255),
                    ])
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
                    ->label('نام صندوق')
                    ->searchable()
                    ->description(function(Model $record){
                        // محاسبه موجودی
                        $balance = $record->incomingTransfers()->sum('amount') - $record->outgoingTransfers()->sum('amount');

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
                    ->sortable(),
                Tables\Columns\TextColumn::make('switch_number')
                ->label('شماره سوئیچ پرداخت')
                ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('terminal_number')
                ->label('شماره ترمینال پرداخت')
                ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('merchant_number')
                ->label('شماره پذیرنده فروشگاهی')
                ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                ->label('توضیحات')
                ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at_jalali')
                ->label('تاریخ ایجاد')
                    ->sortable(['created_at']),

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
                ->modalContent(fn (Fund $record): View => view(
                    'filament.pages.actions.fund.detail',
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
            'index' => Pages\ManageFunds::route('/'),
        ];
    }
    protected static ?int $navigationSort = 3;
}
