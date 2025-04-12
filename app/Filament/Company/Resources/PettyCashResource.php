<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\PettyCash;
use Illuminate\View\View;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Radio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\PettyCashResource\Pages;
use App\Filament\Company\Resources\PettyCashResource\RelationManagers;

class PettyCashResource extends Resource
{
    protected static ?string $model = PettyCash::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'تنخواه گردان';
    protected static ?string $pluralLabel = 'تنخواه گردان';
    protected static ?string $label = 'تنخواه گردان';
    protected static ?string $navigationGroup = 'بانکداری';
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
                            $pettyCash = PettyCash::withTrashed()->latest()->first();
                            $id      = $pettyCash ? (++$pettyCash->id) : 1;
                            $state === 'auto' ? $set('accounting_code', $id) : $set('accounting_code', '');
                        }
                    )
                    ->inline()
                    ->inlineLabel(false),
                Forms\Components\TextInput::make('accounting_code')
                    ->label('کد حسابداری')
                    ->extraAttributes(['style' => 'direction:ltr'])
                    ->required()
                    ->afterStateHydrated(function (Get $get) {
                        $pettyCash = PettyCash::withTrashed()->latest()->first();
                        $id      = $pettyCash ? (++$pettyCash->id) : 1;
                        return ($get('accounting_auto') == 'auto') ? $id : '';
                    })
                    ->default(
                        function (Get $get) {
                            $pettyCash = PettyCash::withTrashed()->latest()->first();
                            $id      = $pettyCash ? (++$pettyCash->id) : 1;
                            return ($get('accounting_auto') == 'auto') ? $id : '';
                        }
                    )
                    ->readOnly(fn($get) => $get('accounting_auto') === 'auto')
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('deleted_at', null);
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('accounting_code')
                    ->label('کد حسابداری')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                ->label('نام')
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at_jalali')
                ->label('تاریخ ایجاد')
                    ->sortable(['created_at']),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                ->label('سند حسابداری')
                ->color('warning')
                ->icon('heroicon-o-eye')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(fn (PettyCash $record): View => view(
                    'filament.pages.actions.petty-cash.detail',
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
            'index' => Pages\ManagePettyCashes::route('/'),
        ];
    }
    protected static ?int $navigationSort = 2;
}
