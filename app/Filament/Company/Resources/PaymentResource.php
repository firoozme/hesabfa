<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Payment;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\PaymentResource\Pages;
use App\Filament\Company\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    public $invoice;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('invoice_id')
                    ->label('انتخاب فاکتور')
                    ->relationship('invoice', 'number')
                    ->required(),

                Select::make('paymentable_type')
                    ->label('روش پرداخت')
                    ->options([
                        'App\Models\BankAccount' => 'حساب بانکی',
                        'App\Models\PettyCash' => 'تنخواه',
                        'App\Models\Fund' => 'صندوق',
                        'App\Models\Cheque' => 'چک',
                        'App\Models\MixedPayment' => 'ترکیبی',
                    ])
                    ->live()
                    ->required(),
                    Select::make('paymentable_id')
                    ->label('انتخاب حساب مربوطه')
                    ->options(fn (callable $get) => match ($get('paymentable_type')) {
                        'App\\Models\\BankAccount' => \App\Models\BankAccount::where('company_id',auth()->user('company')->id)->pluck('name', 'id'),
                        'App\\Models\\PettyCash' => \App\Models\PettyCash::where('company_id',auth()->user('company')->id)->pluck('name', 'id'),
                        'App\\Models\\Fund' => \App\Models\Fund::where('company_id',auth()->user('company')->id)->pluck('name', 'id'),
                        'App\\Models\\Cheque' => \App\Models\Cheque::where('company_id',auth()->user('company')->id)->pluck('cheque_number', 'id'),
                        default => [],
                    })
                    ->live()
                    ->required(),
                TextInput::make('amount')
                    ->label('مبلغ پرداختی')

                    ->required(),

                TextInput::make('reference_number')
                    ->label('شماره مرجع')
                    ->visible(fn (Get $get) => $get('paymentable_type') === 'App\Models\BankAccount'),

                DatePicker::make('cheque_due_date')
                    ->label('تاریخ سررسید چک')
                    ->visible(fn (Get $get) => $get('paymentable_type') === 'App\Models\Cheque'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.number')->label('شماره فاکتور'),
                TextColumn::make('amount')->label('مبلغ پرداختی')->sortable(),
                TextColumn::make('paymentable_type')->label('روش پرداخت'),
                TextColumn::make('created_at')->label('تاریخ پرداخت')->date(),
            ])
            ->filters([]);
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            // 'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
    protected static bool $shouldRegisterNavigation = false;
}
