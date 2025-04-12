<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\OpeningBalance;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\OpeningBalanceResource\Pages;
use App\Filament\Company\Resources\OpeningBalanceResource\RelationManagers;

class OpeningBalanceResource extends Resource
{
    protected static ?string $model = OpeningBalance::class;
    protected static ?string $navigationLabel = 'تراز افتتاحیه';
    protected static ?string $pluralLabel = 'ترازهای افتتاحیه';
    protected static ?string $navigationGroup = 'حسابداری';
    protected static ?string $label = 'تراز افتتاحیه';
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('accountable_type')
                    ->label('نوع حساب')
                    ->options([
                        'App\Models\Fund' => 'صندوق',
                        'App\Models\CompanyBankAccount' => 'حساب بانکی',
                        'App\Models\PettyCash' => 'تنخواه',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('accountable_id', null)),
                Forms\Components\Select::make('accountable_id')
                    ->label('حساب')
                    ->options(function (callable $get) {
                        $type = $get('accountable_type');
                        if (!$type) return [];
                        return $type::all()->pluck('name', 'id')->toArray();
                    })
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('amount')
                    ->label('مبلغ')
                    ->required()
                    ->mask(RawJs::make(<<<'JS'
                        $money($input)
                        JS))
                        ->dehydrateStateUsing(function ($state) {
                            return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                        })
                    ->suffix('ریال'),
                Forms\Components\DatePicker::make('date')
                    ->label('تاریخ')
                    ->required()
                    ->jalali()
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('accountable_type')
                    ->label('نوع حساب')
                    ->formatStateUsing(function($state){
                        if(class_basename($state) == 'Fund'){
                            return 'صندوق';
                        }elseif(class_basename($state) == 'CompanyBankAccount'){
                            return 'حساب بانکی';

                        }else{
                            return 'صندوق';
                        }
                    }),
                Tables\Columns\TextColumn::make('accountable.name')
                    ->label('نام حساب'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->money('IRR'),
                Tables\Columns\TextColumn::make('date_jalali')
                    ->label('تاریخ'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth('company')->user()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOpeningBalances::route('/'),
        ];
    }

    protected static ?int $navigationSort = 1;
}
