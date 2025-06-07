<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Account;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\AccountResource\Pages;
use App\Filament\Company\Resources\AccountResource\RelationManagers;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'حساب';
    protected static ?string $pluralLabel = 'حساب‌ها';
    protected static ?string $label = 'حساب';
    protected static ?string $navigationGroup = 'حسابداری';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('کد حساب')
                    ->required()
                    ->unique(Account::class, 'code', ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('name')
                    ->label('نام حساب')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('نوع حساب')
                    ->options([
                        'asset' => 'دارایی',
                        'liability' => 'بدهی',
                        'equity' => 'حقوق صاحبان سهام',
                        'revenue' => 'درآمد',
                        'expense' => 'هزینه',
                    ])
                    ->required(),
                TextInput::make('balance')
                    ->label('موجودی اولیه')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('کد حساب')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('نام حساب')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('نوع حساب')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'asset' => 'دارایی',
                        'liability' => 'بدهی',
                        'equity' => 'حقوق صاحبان سهام',
                        'revenue' => 'درآمد',
                        'expense' => 'هزینه',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('موجودی')
                    ->money('irr', locale: 'fa')
                    ->sortable(),
                TextColumn::make('persons_count')
                    ->label('تعداد اشخاص مرتبط')
                    ->counts('persons')
                    ->sortable(),
                TextColumn::make('created_at_jalali')
                    ->label('تاریخ ایجاد')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع حساب')
                    ->options([
                        'asset' => 'دارایی',
                        'liability' => 'بدهی',
                        'equity' => 'حقوق صاحبان سهام',
                        'revenue' => 'درآمد',
                        'expense' => 'هزینه',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccounts::route('/'),
        ];
    }
}
