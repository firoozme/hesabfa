<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\FiscalYear;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\FiscalYearResource\Pages;
use App\Filament\Resources\FiscalYearResource\RelationManagers;

class FiscalYearResource extends Resource
{
    protected static ?string $model = FiscalYear::class;


    protected static ?string $navigationLabel = 'سال مالی';
    protected static ?string $pluralLabel = 'سال مالی';
    protected static ?string $label = 'سال مالی';
    protected static ?string $navigationGroup = 'سیستم';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Select::make('name')
                    ->options([
                        '1400' => '1400',
                        '1401' => '1401',
                        '1402' => '1402',
                        '1403' => '1403',
                        '1404' => '1404',
                        '1405' => '1405',
                        '1406' => '1406',
                    ])
                    ->label('سال مالی جدید')
                    ->unique(ignoreRecord: true,modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('deleted_at', null);
                    })
                    ->columnSpanFull()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('سال مالی')

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    // Check if this is the last record in the table
                    $recordCount = $record->newQuery()->count();
                    if ($recordCount > 1) {
                        // Proceed with deletion
                        $record->delete();
                    } else {
                        // Prevent deletion if it's the last record
                        // You can add a flash message or alert here
                                Notification::make()
                                ->title('پاک کردن همه سالهای مالی امکان پذیر نیست')
                                ->danger()
                                ->send();
                    }
                })
,
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('id','desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFiscalYears::route('/'),
        ];
    }



    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('fiscal_year_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('fiscal_year_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('fiscal_year_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('fiscal_year_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('fiscal_year_delete');
    }
}
