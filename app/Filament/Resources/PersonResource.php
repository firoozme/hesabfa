<?php

namespace App\Filament\Resources;

use stdClass;
use Filament\Forms;
use App\Models\City;
use Filament\Tables;
use App\Models\Person;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PersonResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PersonResource\RelationManagers;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;
    protected static ?string $navigationLabel = 'شخص';
    protected static ?string $pluralLabel = 'اشخاص';
    protected static ?string $label = 'اشخاص';
    protected static ?string $navigationGroup = 'اشخاص';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('#')
                ->state(static function (HasTable $livewire, stdClass $rowLoop): string {
                    return (string) (
                        $rowLoop->iteration +
                        ($livewire->getTableRecordsPerPage() * (
                            $livewire->getTablePage() - 1
                        ))
                    );
                }),
            ImageColumn::make('image')
                ->label('عکس')
                ->extraImgAttributes(['loading' => 'lazy'])
                ->checkFileExistence(false)
                // ->default(fn(Person $record) => file_exists(asset('upload/' . $record->image)) ? asset('upload/' . $record->image) : asset('upload/avatar_placeholder.png'))
                ->disk('public'),
            Tables\Columns\TextColumn::make('accounting_code')
                ->label('کد حسابداری')
                ->copyable()
                ->searchable(),
            Tables\Columns\TextColumn::make('account.name')
                ->label('نام حساب')
                ->default('-')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('company_name')
                ->label('شرکت')
                ->default('-')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            Tables\Columns\TextColumn::make('firstname')
                ->label('نام')
                ->default('-')
                ->searchable(),
            Tables\Columns\TextColumn::make('lastname')
                ->label('نام خانوادگی')
                ->default('-')
                ->searchable(),
            Tables\Columns\TextColumn::make('types.title')
                ->label('نوع شخص'),
            Tables\Columns\TextColumn::make('birth_date_jalali')
                ->label('تاریخ تولد')
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(['birth_date'])
                ->searchable(['birth_date']),
            Tables\Columns\TextColumn::make('marriage_date_jalali')
                ->label('تاریخ ازدواج')
                ->toggleable(isToggledHiddenByDefault: true)
                ->default('-')
                ->sortable(['marriage_date'])
                ->searchable(['marriage_date']),
            Tables\Columns\TextColumn::make('sum')
                ->label('وضعیت حساب')
                ->getStateUsing(fn($record) => $record->previous_credit - $record->previous_debt)
                ->colors([
                    'danger' => fn($state) => $state < 0,
                    'success' => fn($state) => $state > 0,
                    'gray' => fn($state) => $state == 0,
                ])
                ->formatStateUsing(fn($state) => number_format($state) . ' ریال')
                ->description(fn($state) => ($state) < 0 ? 'بدهکار' : ($state > 0 ? 'بستانکار' : 'تسویه‌شده')),
            Tables\Columns\TextColumn::make('previous_debt')
                ->label('مبلغ بدهی')
                ->formatStateUsing(fn($state) => number_format($state) . ' ریال')
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
            Tables\Columns\TextColumn::make('previous_credit')
                ->label('مبلغ بستانکاری')
                ->formatStateUsing(fn($state) => number_format($state) . ' ریال')
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
            Tables\Columns\TextColumn::make('financial_credit')
                ->label('اعتبار مالی')
                ->formatStateUsing(fn($state) => number_format($state) . ' ریال')
                ->toggleable(isToggledHiddenByDefault: true)
                ->sortable(),
            Tables\Columns\TextColumn::make('price_list_id')
                ->label('لیست قیمت')
                ->formatStateUsing(function ($record) {
                    return $record->price_list_id ? 'لینک' : '-';
                })
                ->url(fn($record) => $record->price_list_id ? route('price.list', ['record' => $record->price_list_id]) : '')
                ->default('-')
                ->sortable(),
            Tables\Columns\TextColumn::make('tax_type.title')
                ->label('نوع مالیات'),
            Tables\Columns\TextColumn::make('national_id')
                ->default('-')
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('شناسه ملی')
                ->searchable(),
            Tables\Columns\TextColumn::make('registration_number')
                ->default('-')
                ->label('شماره ثبت')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            Tables\Columns\TextColumn::make('branch_code')
                ->default('-')
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('کد شعبه')
                ->searchable(),
            Tables\Columns\TextColumn::make('city.title')
                ->label('شهر')
                ->default('-')
                ->sortable(),
            Tables\Columns\TextColumn::make('postal_code')
                ->default('-')
                ->label('کدپستی')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            Tables\Columns\TextColumn::make('address')
                ->default('-')
                ->label('آدرس')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            Tables\Columns\TextColumn::make('mobile')
                ->default('-')
                ->label('موبایل')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            Tables\Columns\TextColumn::make('fax')
                ->default('-')
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('فکس')
                ->searchable(),
            Tables\Columns\TextColumn::make('email')
                ->default('-')
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('ایمیل')
                ->searchable(),
            Tables\Columns\TextColumn::make('website')
                ->default('-')
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('آدرس وبسایت')
                ->searchable(),
            Tables\Columns\TextColumn::make('created_at_jalali')
                ->sortable()
                ->label('تاریخ عضویت')
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
           
        SelectFilter::make('company_id')
        ->label('نوع')
        ->relationship('company','mobile')
        ], layout: FiltersLayout::AboveContent)
        ->actions([
            // Tables\Actions\EditAction::make()
            //     ->mutateRecordDataUsing(function (array $data): array {
            //         $city = City::find($data['city_id']);
            //         if (!$city) {
            //             return $data;
            //         }
            //         $state = City::where('id', $city->parent)->first();
            //         if (!$state) {
            //             return $data;
            //         }
            //         $data['state'] = $state->id;
            //         return $data;
            //     }),
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
            'index' => Pages\ManagePeople::route('/'),
        ];
    }
    protected static ?int $navigationSort = 3;


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('person_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('person_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('person_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('person_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('person_delete');
    }
}
