<?php

namespace App\Filament\Company\Resources;

use Closure;
use stdClass;
use Filament\Forms;
use Filament\Tables;
use App\Models\Store;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StoreTransaction;
use Filament\Resources\Resource;
use Dotswan\MapPicker\Fields\Map;
use Filament\Tables\Actions\Action;
use App\Models\StoreTransactionItem;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\StoreResource\Pages;
use App\Filament\Company\Resources\StoreResource\RelationManagers;
use App\Filament\Company\Resources\StoreResource\Pages\StoreInventory;
use App\Filament\Resources\StoreResource\RelationManagers\ProductsRelationManager;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;


    protected static ?string $navigationLabel = 'انبار';
    protected static ?string $pluralLabel = 'انبارها';
    protected static ?string $label = 'انبار';
    protected static ?string $navigationGroup = 'انبارداری';
    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
}
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Employer')
                    ->label('')
                    ->schema([
                        FileUpload::make('image')
                            ->label('تصویر')
                            ->disk('public')
                            ->directory('stores/image')
                            ->visibility('private')
                            ->deleteUploadedFileUsing(function ($file) {
                                // Optional: Define how to delete the file
                                $imagePath = env('APP_ROOT').'upload/store/' . $file;
                                if (file_exists($imagePath)) {
                                    unlink($imagePath);
                                }
                            }),
                        Map::make('location')
                            ->label('')
                            ->default([
                                'lat' => 40.4168,
                                'lng' => -3.7038
                            ])
                            ->afterStateUpdated(function (Set $set, ?array $state): void {
                                $set('latitude', $state['lat']);
                                $set('longitude', $state['lng']);
                            })
                            ->afterStateHydrated(function ($state, $record, Set $set): void {
                                $set('location', ['lat' => $record->latitude ?? 35.7219, 'lng' => $record->longitude ?? 51.3347]);
                            })
                            ->extraStyles([
                                'border: 1px solid '
                            ])
                            ->liveLocation()
                            ->showMarker()
                            ->markerColor("#22c55eff")
                            ->showFullscreenControl()
                            ->showZoomControl()
                            ->draggable()
                            ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                            ->zoom(5)
                            ->detectRetina()
                            ->showMyLocationButton()
                            ->extraTileControl([])
                            ->extraControl([
                                'zoomDelta'           => 1,
                                'zoomSnap'            => 2,
                            ]),
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان')
                            ->required()
                            ->maxLength(255),


                        Forms\Components\TextInput::make('phone_number')
                            ->label('شماره تلفن')
                            ->required()
                            ->extraAttributes(['style' => 'direction:ltr'])
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->label('آدرس')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات')
                            ->columnSpanFull(),

                    ])

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
                ImageColumn::make('image')
                    ->label('عکس انبار')
                    ->circular()
                    ->disk('public'),
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان انبار')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('آدرس')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->searchable()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('total_inventory')
                    ->label('موجودی کل')
                    ->getStateUsing(function (Store $record) {
                        return $record->products()->sum('store_product.quantity');
                    }),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('شماره تلفن')
                    ->searchable(),
                    Tables\Columns\IconColumn::make('is_default')
                    ->label('پیش‌فرض')
                    ->boolean()
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success') // سبز برای true
                    ->falseColor('danger'), // قرمز برای false
                Tables\Columns\TextColumn::make('created_at_jalali')
                    ->label('تاریخ ایجاد')
                    ->searchable(['created_at']),

            ])->defaultSort('id', 'desc')
            ->headerActions([])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['latitude'] = $data['location']['lat'];
                    $data['longitude'] = $data['location']['lng'];
                    unset($data['location']);
                    $data['company_id'] = auth('company')->id();
                    return $data;
                }),
                Action::make('transfer')
                    ->label('انتقال به انبار دیگر')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-right')
                ->form([
                        Forms\Components\Select::make('destination_store_id')
                            ->label('انبار مقصد')
                            ->options(function ($record) {
                                return Store::where('id', '!=', $record->id)
								->where('company_id',auth()->user('company')->id)
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->required(),
                        Forms\Components\Repeater::make('transfer_items')
                            ->label('محصولات برای انتقال')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('محصول')
                                    ->searchable()
                                    ->placeholder('انتخاب کنید')
                                    ->searchPrompt('تایپ کنید ...')
                                    ->options(function ($record) {
                                        return $record->products()
                                            ->pluck('name', 'products.id')
                                            ->toArray();
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state, $record) {
                                        $productId = $state;
                                        if ($productId) {
                                            $stock = $record->products()
                                                ->where('product_id', $productId)
                                                ->first()
                                                ->pivot->quantity ?? 0;
                                            $set('quantity_hint', "موجودی فعلی: $stock");
                                        } else {
                                            $set('quantity_hint', null);
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('تعداد')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->helperText(function (Get $get) {
                                        return $get('quantity_hint');
                                    })
                                    ->rules([
                                        function ($get, $record) {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                                $productId = $get('product_id');
                                                $stock = $record->products()
                                                    ->where('product_id', $productId)
                                                    ->first()
                                                    ->pivot->quantity ?? 0;
                                                if ($stock < $value) {
                                                    $fail("موجودی کافی نیست. موجودی فعلی: $stock");
                                                }
                                            };
                                        },
                                    ]),
                            ])
                        ->columns(2) // سه ستون: محصول، موجودی فعلی، تعداد
                        ->required(),
                ])
                ->action(function (array $data, $record) {
                    $sourceStoreId = $record->id;
                    $destinationStoreId = $data['destination_store_id'];
                    $date = now();

                    // ثبت انتقال از انبار مبدأ به مقصد
                    $lastTransferredStoreTransaction = StoreTransaction::where('type', 'transfer')->withTrashed()->latest()->first();
                    $lastTransferredStoreTransactionId = $lastTransferredStoreTransaction ? ++$lastTransferredStoreTransaction->id : 1;

                    $transferTransaction = StoreTransaction::create([
                        'store_id' => $sourceStoreId,
                        'type' => 'transfer',
                        'date' => $date,
                        'reference' => 'TRN-' . $lastTransferredStoreTransactionId,
                        'destination_id' => $destinationStoreId, // اضافه کردن انبار مقصد
                    ]);

                    // ثبت آیتم‌های انتقال
                    foreach ($data['transfer_items'] as $item) {
                        StoreTransactionItem::create([
                            'store_transaction_id' => $transferTransaction->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                        ]);

                        // کاهش موجودی از انبار مبدأ
                        $record->products()->updateExistingPivot($item['product_id'], [
                            'quantity' => \DB::raw('quantity - ' . $item['quantity']),
                        ]);

                        // افزایش موجودی در انبار مقصد
                        $destinationStore = Store::find($destinationStoreId);
                        $destinationStore->products()->syncWithoutDetaching([
                            $item['product_id'] => ['quantity' => \DB::raw('COALESCE(quantity, 0) + ' . $item['quantity'])],
                        ]);
                    }

                    Notification::make()
                        ->title('موفقیت')
                        ->body('محصولات با موفقیت به انبار مقصد منتقل شدند.')
                        ->success()
                        ->send();
                })
                
                ->requiresConfirmation()
                ->modalHeading('انتقال محصولات به انبار دیگر'),
                     Action::make('view_inventory')
                    ->label('موجودی')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Store $record): string => static::getUrl('inventory', ['record' => $record->id]))
                    ->openUrlInNewTab(false)
                    ->modalHeading(fn (Store $record) => "آمار موجودی انبار: {$record->title}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('بستن')
                    ->color('success')
                    ->modalWidth('lg'),

                    Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('حذف انبار')
                    ->modalDescription('با حذف انبار تمامی محصولات آن حذف خواهد شد. آیا مطمئن هستید؟')
                    ->modalSubmitActionLabel('بله، حذف کن')
                    ->form(function ($record) {
                        // اگر انبار خالی نیست، فرم تأیید اضافی نشون می‌دیم
                        if (!$record->isEmpty()) {
                            return [
                                Forms\Components\Checkbox::make('confirm_non_empty')
                                    ->label('این انبار حاوی محصولات است. حذف آن تمام محصولات و تراکنش‌ها را حذف می‌کند. آیا ادامه می‌دهید؟')
                                    ->required(),
                                Forms\Components\Checkbox::make('final_confirmation')
                                    ->label('این عملیات غیرقابل بازگشت است. لطفاً تأیید نهایی کنید.')
                                    ->required(),
                            ];
                        }
                        return [];
                    })
                    ->action(function ($record, array $data) {
                        // اگر انبار خالی نیست، چک می‌کنیم که تأییدیه‌ها داده شده یا نه
                        if (!$record->isEmpty() && (!isset($data['confirm_non_empty']) || !$data['confirm_non_empty'] || !isset($data['final_confirmation']) || !$data['final_confirmation'])) {
                            Notification::make()
                                ->title('خطا')
                                ->body('برای حذف انبار غیرخالی، باید تمام تأییدیه‌ها را انجام دهید.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // حذف انبار و محتویاتش
                        try {
                            $record->transactions()->each(function ($transaction) {
                                $transaction->items()->delete();
                                $transaction->delete();
                            });
                            $record->products()->detach();
                            $record->delete();

                            Notification::make()
                                ->title('موفقیت')
                                ->body('انبار و محتویات آن با موفقیت حذف شدند.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('خطا در حذف انبار: ' . $e->getMessage(), [
                                'store_id' => $record->id,
                            ]);
                            Notification::make()
                                ->title('خطا')
                                ->body('خطایی در حذف انبار رخ داد: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->successNotificationTitle(null),


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
            'index' => Pages\ManageStores::route('/'),
            'inventory' => StoreInventory::route('/{record}/inventory'),
        ];
    }

}
