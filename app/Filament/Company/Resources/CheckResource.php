<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Check;
use App\Models\Person;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Resources\Components\Tab;
use Filament\Tables\Columns\TextColumn;
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
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

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
            ])
            ->filters([

                SelectFilter::make('type')
                ->label('نوع چک')
                ->options([
                    'receivable' => 'دریافتی',
                    'payable' => 'پرداختی',

                ])
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
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
