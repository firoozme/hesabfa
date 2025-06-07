<?php
namespace App\Filament\Company\Resources;

use stdClass;
use Filament\Forms;
use App\Models\Bank;
use App\Models\City;
use Filament\Tables;
use App\Models\Person;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\PersonTax;
use App\Models\PersonType;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action as Act;
use App\Filament\Company\Resources\PersonResource\Pages;
use App\Filament\Company\Resources\PersonResource\RelationManagers;

class PersonResource extends Resource
{
    protected static ?string $navigationLabel = 'شخص';
    protected static ?string $pluralLabel = 'اشخاص';
    protected static ?string $label = 'اشخاص';
    protected static ?string $navigationGroup = 'اشخاص';
    protected static ?string $model = Person::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';

    public $previous_credit, $previous_debt;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('General')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->label('مشخصات اصلی')
                        ->schema([
                            FileUpload::make('image')
                                ->label('تصویر')
                                ->disk('public')
                                ->directory('people/image')
                                ->visibility('private')
                                ->deleteUploadedFileUsing(function ($file) {
                                    $imagePath = env('APP_ROOT') . '/upload/' . $file;
                                    if (file_exists($imagePath)) {
                                        unlink($imagePath);
                                    }
                                })->columnSpanFull(),

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
                                        $person = Person::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                                        $accounting_code      = $person ? (++$person->accounting_code) : 1;
                                        $state === 'auto' ? $set('accounting_code', $accounting_code) : $set('accounting_code', '');
                                    }
                                )
                                ->inline()
                                ->inlineLabel(false),
                            Forms\Components\TextInput::make('accounting_code')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->label('کد حسابداری')
                                ->required()
                                ->default(
                                    function (Get $get) {
                                        $person = Person::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                                        $accounting_code      = $person ? (++$person->accounting_code) : 1;
                                        return ($get('accounting_auto') == 'auto') ? $accounting_code : '';
                                    }
                                )
                                ->afterStateHydrated(function (Get $get) {
                                    $person = Person::where('company_id',auth('company')->user()->id)->withTrashed()->latest()->first();
                                    $accounting_code      = $person ? (++$person->accounting_code) : 1;
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

                            Forms\Components\TextInput::make('firstname')
                                ->label('نام')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('lastname')
                                ->label('نام خانوادگی')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('person_type_id')
                                ->label('نوع')
                                ->relationship('types', 'title')
                                ->preload()
                                ->multiple()
                                ->required()
                                ->reactive(),
                                // ->suffixAction(
                                //     Act::make('add_type')
                                //         ->label('اضافه کردن نوع')
                                //         ->icon('heroicon-o-plus')
                                //         ->modalHeading('ایجاد نوع جدید')
                                //         ->action(function (array $data) {
                                //             $type = PersonType::create(['title' => $data['title']]);
                                //             return $type->id;
                                //         })
                                //         ->form([
                                //             TextInput::make('title')
                                //                 ->label('عنوان')
                                //                 ->required(),
                                //         ])
                                //         ->after(function ($livewire) {
                                //             $livewire->dispatch('refreshForm');
                                //         })
                                // ),
                            Forms\Components\Select::make('person_tax_id')
                                ->label('نوع مالیات')
                                ->relationship('tax_type', 'title')
                                ->preload()
                                ->suffixAction(
                                    Act::make('add_type')
                                        ->label('اضافه کردن مالیات')
                                        ->icon('heroicon-o-plus')
                                        ->modalHeading('ایجاد مالیات جدید')
                                        ->action(function (array $data) {
                                            $tax = PersonTax::create(['title' => $data['title']]);
                                            return $tax->id;
                                        })
                                        ->form([
                                            TextInput::make('title')
                                                ->label('عنوان')
                                                ->required(),
                                        ])
                                        ->after(function ($livewire) {
                                            $livewire->dispatch('refreshForm');
                                        })
                                ),
                            Forms\Components\TextInput::make('previous_debt')
                                ->label('مبلغ بدهکاری')
                                ->required()
                                ->numeric()
                                ->hidden()
                                ->default(0),
                            Forms\Components\TextInput::make('previous_credit')
                                ->label('مبلغ بستانکاری')
                                ->required()
                                ->numeric()
                                ->hidden()
                                ->default(0),
                            Forms\Components\TextInput::make('account_id')
                                ->label('شناسه حساب')
                                ->disabled()

                                ->default(function ($record = null) {
                                    return $record && $record->account ? $record->account->id : 'حساب به‌صورت خودکار ساخته می‌شود';
                                })
                                ->hidden()
                                ->helperText('حساب مرتبط با این شخص به‌صورت خودکار در جدول accounts ایجاد می‌شود.'),
                            Repeater::make('phone1')
                                ->label('')
                                ->schema([
                                    Forms\Components\TextInput::make('phone')
                                        ->label('شماره تماس')
                                        ->tel(),
                                ])
                                ->defaultItems(0)
                                ->addable(true)
                                ->deleteAction(
                                    fn($action) => $action->hidden(fn($state) => count($state) <= 1)
                                )
                                ->columnSpanFull()
                                ->addActionLabel('افزودن شماره تماس')
                                ->reorderable(false),
                            Repeater::make('banks')
                                ->addActionLabel('افزودن حساب بانکی')
                                ->label('')
                                ->defaultItems(0)
                                ->relationship()
                                ->addable(true)
                                ->deleteAction(
                                    fn($action) => $action->hidden(fn($state) => count($state) <= 1)
                                )
                                ->schema([
                                    Forms\Components\Select::make('bank_name')
                                        ->label('نام بانک')
                                        ->options(fn() => Bank::all()->pluck('name', 'name')->toArray())
                                        ->searchable()
                                        ->reactive()
                                        ->suffixAction(
                                            Act::make('add_bank')
                                                ->label('اضافه کردن بانک')
                                                ->icon('heroicon-o-plus')
                                                ->modalHeading('ایجاد بانک جدید')
                                                ->action(function (array $data, $component) {
                                                    $bank = Bank::create(['name' => $data['name']]);
                                                    $component->state(null);
                                                    return $bank->id;
                                                })
                                                ->form([
                                                    TextInput::make('name')
                                                        ->label('نام بانک')
                                                        ->required(),
                                                ])
                                        ),
                                    Forms\Components\TextInput::make('account_number')
                                        ->label('شماره حساب')
                                        ->extraAttributes(['style' => 'direction:ltr'])
                                        ->regex('/^\d+$/i')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('card_number')
                                        ->label('شماره کارت')
                                        ->mask('9999-9999-9999-9999')
                                        ->extraAttributes(['style' => 'direction:ltr'])
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('iban')
                                        ->label('شماره شبا')
                                        ->extraAttributes(['style' => 'direction:ltr'])
                                        ->regex('/^\d{24}$/')
                                        ->prefix('IR')
                                        ->maxLength(255),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Wizard\Step::make('finance')
                        ->completedIcon('heroicon-m-hand-thumb-up')
                        ->label('تکمیلی')
                        ->schema([
                            Forms\Components\TextInput::make('financial_credit')
                                ->label('اعتبار مالی')
                                ->numeric()
                                ->default(0),
                            Forms\Components\Select::make('price_list_id')
                                ->label('لیست قیمت')
                                ->relationship('price_list', 'name'),
                            Forms\Components\TextInput::make('national_id')
                                ->label('شناسه ملی')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->maxLength(255),
                            Forms\Components\TextInput::make('registration_number')
                                ->label('شماره ثبت')
                                ->maxLength(255)
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->regex('/^\d+$/'),
                            Forms\Components\TextInput::make('branch_code')
                                ->label('کد شعبه')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->maxLength(255)
                                ->regex('/^\d+$/'),
                            Forms\Components\Textarea::make('notes')
                                ->label('توضیحات')
                                ->columnSpanFull(),
                            Forms\Components\Select::make('state')
                                ->label('استان')
                                ->dehydrated(false)
                                ->live()
                                ->searchable()
                                ->options(City::all()->where('parent', null)->pluck('title', 'id')),
                            Forms\Components\Select::make('city_id')
                                ->label('شهر')
                                ->options(fn(Get $get) => City::all()->where('parent', $get('state') ?? '')->pluck('title', 'id'))
                                ->searchable(),
                            Forms\Components\TextInput::make('postal_code')
                                ->label('کدپستی')
                                ->regex('/^\d{10}$/i')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->maxLength(255),
                            Forms\Components\Textarea::make('address')
                                ->label('آدرس')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('mobile')
                                ->label('شماره موبایل')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->maxLength(255)
                                ->regex('/^09\d{9}$/'),
                            Forms\Components\TextInput::make('fax')
                                ->label('شماره فکس')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->label('ایمیل')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->email()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('website')
                                ->label('آدرس وبسایت')
                                ->extraAttributes(['style' => 'direction:ltr'])
                                ->url()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('company_name')
                                ->label('شرکت')
                                ->maxLength(255),
                            Forms\Components\DatePicker::make('birth_date')
                                ->label('تاریخ تولد')
                                ->jalali(),
                            Forms\Components\DatePicker::make('marriage_date')
                                ->jalali()
                                ->label('تاریخ ازدواج'),
                        ])
                        ->columns(2),
                ])
                    ->skippable()
                    ->columnSpanFull(),
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
                SelectFilter::make('type')
                    ->label('نوع')
                    ->options(fn() => PersonType::all()->pluck('title', 'id')->toArray()) // گزینه‌ها از PersonType
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('types', function (Builder $query) use ($data) {
                                $query->where('person_type_id', $data['value']);
                            });
                        }
                }),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(function (array $data): array {
                        $city = City::find($data['city_id']);
                        if (!$city) {
                            return $data;
                        }
                        $state = City::where('id', $city->parent)->first();
                        if (!$state) {
                            return $data;
                        }
                        $data['state'] = $state->id;
                        return $data;
                    }),
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
    protected static ?int $navigationSort = 7;
}
