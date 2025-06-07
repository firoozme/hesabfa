<?php
namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use App\Models\Discount;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Company\Resources\DiscountResource\Pages;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationLabel = 'تخفیف‌ها';
    protected static ?string $pluralLabel     = 'تخفیف‌ها';
    protected static ?string $label           = 'تخفیف‌ها';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?string $navigationIcon  = 'heroicon-o-ticket';
    protected static ?int $navigationSort     = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('نام تخفیف')
                    ->required(),
                Select::make('type')
                    ->label('نوع تخفیف')
                    ->options([
                        'percentage' => 'درصدی',
                        'fixed'      => 'مقدار ثابت',
                    ])
                    ->default('percentage')
                    ->reactive()
                    ->required(),
                TextInput::make('value')
                    ->label('مقدار تخفیف')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required()
                    ->suffix('درصد')
                    ->visible(fn($get) => $get('type') === 'percentage'), // Show only if type,
                TextInput::make('value')
                    ->label('مقدار تخفیف')
                    ->minValue(0)
                    ->required()
                    ->mask(RawJs::make(<<<'JS'
                    $money($input)
                    JS))
                    ->dehydrateStateUsing(function ($state) {
                        return (float) str_replace(',', '', $state); // تبدیل رشته فرمت‌شده به عدد
                    })
                    ->suffix('ریال')
                    ->visible(fn($get) => $get('type') === 'fixed'), // Show only if type,,
                DateTimePicker::make('start_date')
                    ->label('تاریخ شروع')
                    ->jalali()
                    ->nullable(),
                DateTimePicker::make('end_date')
                    ->label('تاریخ پایان')
                    ->jalali()
                    ->nullable(),
                Select::make('recurrence_rule')
                    ->label('قانون تکرار')
                    ->options([
                        'everyday'         => 'هر روز',
                        'weekly_saturday'  => 'هر شنبه',
                        'weekly_sunday'    => 'هر یک‌شنبه',
                        'weekly_monday'    => 'هر دوشنبه',
                        'weekly_tuesday'   => 'هر سه‌شنبه',
                        'weekly_wednesday' => 'هر چهارشنبه',
                        'weekly_thursday'  => 'هر پنج‌شنبه',
                        'weekly_friday'    => 'هر جمعه',
                    ])
                    ->nullable(),
                Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
                // Select::make('categories')
                //     ->label('گروه ها')
                //     ->multiple()
                //     ->relationship('categories', 'title')
                //     ->preload()
                //     ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('نام تخفیف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn($state) => $state === 'percentage' ? 'درصدی' : 'مقدار ثابت'),
                TextColumn::make('value')
                    ->label('مقدار')
                    ->formatStateUsing(fn($state, $record) => number_format($state) . ($record->type === 'percentage' ? ' %' : ' ریال')),
                TextColumn::make('start_date_jalali')
                    ->label('تاریخ شروع')
                    ->sortable(['start_date']),
                TextColumn::make('end_date_jalali')
                    ->label('تاریخ پایان')
                    ->sortable(['end_date']),
                TextColumn::make('recurrence_rule')
                    ->label('قانون تکرار')
                    ->formatStateUsing(function($state){
                        // fn($state) => $state === 'weekly_wednesday' ? 'هر چهارشنبه' : ($state ? $state : 'بدون تکرار'))
                        if($state == 'everyday'){
                            return 'هر روز';
                        }elseif($state == 'weekly_saturday'){
                            return 'هر شنبه';
                        }elseif($state == 'weekly_sunday'){
                            return 'هر یکشنبه';
                        }elseif($state == 'weekly_monday'){
                            return 'هر دوشنبه';
                        }elseif($state == 'weekly_tuesday'){
                            return 'هر سه شنبه';
                        }elseif($state == 'weekly_wednesday'){
                            return 'هر چهارشنبه';
                        }elseif($state == 'weekly_thursday'){
                            return 'هر پنج شنبه';
                        }elseif($state == 'weekly_friday'){
                            return 'هر جمعه';
                        }else{
                            return '-';
                        }

                    }),
                ToggleColumn::make('is_active')
                    ->label('فعال'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('apply_to_category')
                    ->label('اعمال به دسته‌بندی')
                    ->icon('heroicon-o-folder')
                    ->color('success')
                    ->form([
                        Select::make('category_id')
                            ->label('دسته‌بندی')
                            ->options(fn() => \App\Models\ProductCategory::where('company_id', auth()->user('company')->id)->pluck('title', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data, Discount $record) {
                        $categoryId = $data['category_id'];
                        Product::where('product_category_id', $categoryId)
                            ->where('company_id', auth()->user('company')->id)
                            ->update(['discount_id' => $record->id]);

                        $record->categories()->syncWithoutDetaching([$categoryId]);

                        Notification::make()
                            ->title('موفقیت')
                            ->body('تخفیف به محصولات دسته‌بندی انتخاب‌شده اعمال شد.')
                            ->success()
                            ->send();
                    }),
                    Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index'  => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit'   => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
