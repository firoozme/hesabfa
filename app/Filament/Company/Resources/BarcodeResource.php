<?php

namespace App\Filament\Company\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use App\Models\Barcode;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\BarcodeResource\Pages;
use App\Filament\Company\Resources\BarcodeResource\RelationManagers;

class BarcodeResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationLabel = 'بارکدها';
    protected static ?string $pluralLabel = 'بارکدها';
    protected static ?string $label = 'بارکد';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?string $navigationIcon = 'heroicon-o-printer';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth('company')->user()->id);
    }

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
                Tables\Columns\TextColumn::make('#')->state(
                    static function (HasTable $livewire, stdClass $rowLoop): string {
                        return (string) (
                            $rowLoop->iteration +
                            ($livewire->getTableRecordsPerPage() * (
                                $livewire->getTablePage() - 1
                            ))
                        );
                    }
                ),
                Tables\Columns\TextColumn::make('name')
                    ->label('عنوان')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->state(fn(Product $record) => ($record->type == 'Goods') ? 'کالا' : 'خدمات'),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('قیمت فروش')
                    ->formatStateUsing(
                        fn($state) => $state . ' ریال'
                    ),
                ViewColumn::make('selected_barcode')
                    ->label('انتخاب بارکد')
                    ->view('filament.company.resources.barcode-resource.columns.select-barcode'),
                ViewColumn::make('quantity')
                    ->label('تعداد')
                    ->view('filament.company.resources.barcode-resource.columns.quantity'),
                Tables\Columns\TextColumn::make('code')
                    ->label('کد کالا')
                    ->state(fn(Product $record) => ($record->id < 10000 ) ? str_pad($record->id, 4, '0', STR_PAD_LEFT) : str_pad($record->id, 5, '0', STR_PAD_LEFT))
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
              
            ])
            ->bulkActions([
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
                            $livewire->temporaryData[$recordKey] = $livewire->temporaryData[$recordKey] ?? ['errors' => []];
                            $quantity = $livewire->temporaryData[$recordKey]['quantity'] ?? 0;
                            $barcode = $livewire->temporaryData[$recordKey]['selected_barcode'] ?? null;

                            // اعتبارسنجی
                            if ($quantity <= 0) {
                                $hasError = true;
                                $errorMessages[] = "تعداد محصول '{$record->name}' باید بیشتر از صفر باشد.";
                                $livewire->temporaryData[$recordKey]['errors']['quantity'] = 'min';
                            }
                            if (empty($barcode)) {
                                $hasError = true;
                                $errorMessages[] = "بارکد برای محصول '{$record->name}' انتخاب نشده است.";
                                $livewire->temporaryData[$recordKey]['errors']['selected_barcode'] = 'required';
                            }

                            if (!$hasError) {
                                $validRecords[] = [
                                    'name' => $record->name,
                                    'barcode' => $barcode,
                                    'type' => $record->type == 'Goods' ? 'کالا' : 'خدمات',
                                    'selling_price' => $record->selling_price,
                                    'code' => $record->code,
                                    'quantity' => $quantity,
                                ];
                                $livewire->temporaryData[$recordKey]['errors'] = [];
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
                        session()->flash('barcodes', $validRecords);
                        return redirect()->route('barcode.pdf');                        
                    })
                    ->icon('heroicon-o-printer')
            ])
            ->selectable();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBarcodes::route('/'),
        ];
    }
}