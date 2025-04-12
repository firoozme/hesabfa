<?php

namespace App\Filament\Company\Resources;

use App\Filament\Company\Resources\FinancialDocumentResource\Pages;
use App\Filament\Company\Resources\FinancialDocumentResource\RelationManagers;
use App\Models\FinancialDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FinancialDocumentResource extends Resource
{
    protected static ?string $model = FinancialDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'اسناد مالی';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('document_number')
                    ->label('شماره سند')
                    ->required()
                    ->unique(FinancialDocument::class, 'document_number', ignoreRecord: true)
                    ,
                Forms\Components\DatePicker::make('date')
                    ->label('تاریخ')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('توضیحات'),
                Forms\Components\Select::make('status')
                    ->label('وضعیت')
                    ->options([
                        'draft' => 'پیش‌نویس',
                        'approved' => 'تأییدشده',
                        'posted' => 'ثبت‌شده',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('نوع')
                    ->options([
                        'regular' => 'عادی',
                        'opening' => 'افتتاحیه',
                        'closing' => 'اختتامیه',
                    ])
                    ->default('regular')
                    ->required(),
                Forms\Components\Section::make('آرتیکل‌ها')
                    ->schema([
                        Forms\Components\Repeater::make('lines')
                            ->relationship('lines')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->label('حساب')
                                    ->relationship('account', 'name')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('توضیحات'),
                                Forms\Components\TextInput::make('debit')
                                    ->label('بدهکار')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('credit')
                                    ->label('بستانکار')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')->label('شماره سند'),
                Tables\Columns\TextColumn::make('date')->label('تاریخ')->date(),
                Tables\Columns\TextColumn::make('description')->label('توضیحات'),
                Tables\Columns\TextColumn::make('status')->label('وضعیت'),
                Tables\Columns\TextColumn::make('type')->label('نوع'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'پیش‌نویس',
                        'approved' => 'تأییدشده',
                        'posted' => 'ثبت‌شده',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'regular' => 'عادی',
                        'opening' => 'افتتاحیه',
                        'closing' => 'اختتامیه',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialDocuments::route('/'),
            'create' => Pages\CreateFinancialDocument::route('/create'),
            'edit' => Pages\EditFinancialDocument::route('/{record}/edit'),
        ];
    }
    protected static bool $shouldRegisterNavigation = false;
}
