<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\PriceList;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\PriceListResource\Pages;
use App\Filament\Company\Resources\PriceListResource\RelationManagers;

class PriceListResource extends Resource
{
    protected static ?string $model = PriceList::class;
    protected static ?string $navigationLabel = 'لیست قیمت محصول';
    protected static ?string $pluralLabel = 'لیست قیمت محصولات';
    protected static ?string $label = 'لیست قیمت محصول';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';

    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
}
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('عنوان لیست')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                    Forms\Components\DatePicker::make('start_date')
                    ->label('از تاریخ')
                    ->jalali()
                    ->default(now()),
                    Forms\Components\DatePicker::make('end_date')
                    ->label('تا تاریخ')
                    ->jalali()
                    ->default(date('d-m-Y', strtotime('+1 year'))),
                Forms\Components\Textarea::make('description')
                ->label('توضیحات')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('display_id')
                ->default(true)
                ->label('نمایش شناسه محصول'),
                Forms\Components\Toggle::make('display_name')
                ->default(true)
                ->disabled()
                ->dehydrated(false)
                    ->label('نمایش نام محصول'),
                Forms\Components\Toggle::make('display_barcode')
                    ->label('نمایش بارکد محصول'),
                Forms\Components\Toggle::make('display_image')
                ->label('نمایش تصویر محصول'),
                Forms\Components\Toggle::make('display_selling_price')
                   ->label('نمایش قیمت فروش محصول'),
                Forms\Components\Toggle::make('display_purchase_price')
                    ->label('نمایش قیمت خرید محصول'),
                Forms\Components\Toggle::make('display_inventory')
                    ->label('نمایش موجودی محصول'),
                Forms\Components\Toggle::make('display_minimum_order')
                    ->label('نمایش حداقل سفارش محصول'),
                Forms\Components\Toggle::make('display_lead_time')
                    ->label('نمایش زمان انتظار محصول'),
                Forms\Components\Toggle::make('display_reorder_point')
                     ->label('نمایش نقطه سفارش محصول'),
                Forms\Components\Toggle::make('display_sales_tax')
                    ->label('نمایش  مالیات خرید محصول'),
                Forms\Components\Toggle::make('display_purchase_tax')
                    ->label('نمایش  مالیات فروش محصول'),
                Forms\Components\Toggle::make('display_type')
                    ->label('نمایش  نوع محصول'),
                Forms\Components\Toggle::make('display_unit')
                ->label('نمایش  واحد مالیاتی محصول'),
                Forms\Components\Toggle::make('display_tax')
                ->label('نمایش  مالیات محصول'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                ->label('عنوان لیست')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date_jalali')
                ->label('از تاریخ')
                    ->sortable(['start_date']),
                Tables\Columns\TextColumn::make('end_date_jalali')
                ->label('تا تاریخ')
                ->sortable(['end_date']),

                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->default('-')
                        ->searchable(),

                Tables\Columns\TextColumn::make('created_at_jalali')
                ->label('تاریخ ایجاد')
                ->sortable(['created_at']),

            ])
            ->defaultSort('created_at','desc')
            ->filters([
                //
            ])
            ->actions([
                Action::make('url')
					->label('لینک')
					->icon('heroicon-o-link')
                    ->url(fn($record) => route('price.list',['record'=>$record->id]))
					->color('warning')
                    ->extraAttributes([
                        'target' => '_blank',
                    ])
                    ->link(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                Tables\Actions\BulkAction::make('count_selected')
                ->label('چاپ')
                ->action(function (HasTable $livewire) {
                    $selectedRecords = $livewire->getSelectedTableRecords();

                    // بررسی خالی بودن رکوردها
                    if ($selectedRecords->isEmpty()) {
                        Notification::make()
                            ->title('خطا')
                            ->body('هیچ رکوردی انتخاب نشده است.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // دریافت مقادیر موقت
                    $validRecords = [];
                    $hasError = false;
                    $errorMessages = [];

                    foreach ($selectedRecords as $record) {
                        $recordKey = $record->id;

                        

                        if (!$hasError) {
                            $validRecords[] = [
                                'id' => $record->id,
                                'name' => $record->name,
                                'start_date' => $record->start_date,
                                'end_date' => $record->end_date,
                                'end_date' => $record->created_at,
                            ];
                        }
                    }

                    // اگر خطا وجود داشت، فرآیند متوقف می‌شود
                    if ($hasError) {
                        Notification::make()
                            ->title('خطا')
                            ->body('<ul class="list-disc list-inside"><li>' . implode('</li><li>', $errorMessages) . '</li></ul>')
                            ->danger()
                            ->send();
                        return;
                    }

                    // dd($validRecords);
                    // منطق چاپ
                    Notification::make()
                        ->title('موفقیت')
                        ->body('رکوردها برای چاپ آماده هستند.')
                        ->success()
                        ->send();

                        // ذخیره آرایه در سشن برای انتقال به کنترلر
                    session()->flash('lists', $validRecords);
                    return redirect()->route('products.list.pdf');                        
                })
                ->icon('heroicon-o-printer')
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceLists::route('/'),
            'create' => Pages\CreatePriceList::route('/create'),
            'edit' => Pages\EditPriceList::route('/{record}/edit'),
        ];
    }
    protected static ?int $navigationSort = 4;
}
