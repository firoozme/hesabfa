<?php

namespace App\Filament\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductResource\RelationManagers;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationLabel = 'محصولات';
    protected static ?string $pluralLabel = 'محصولات';
    protected static ?string $label = 'محصول';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
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
                ImageColumn::make('image')
                    ->label('عکس ')
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->checkFileExistence(false)
                    // ->default(fn(Product $record) => file_exists(asset('upload/' . $record->image))  ?  asset('upload/' . $record->image) : asset('upload/photo_placeholder.png'))
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->label('عنوان')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.fullname')
                    ->label('شرکت')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.title')
                    ->label('دسته')
                    ->searchable(['title'])
                    ->sortable(['title']),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('بارکد')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('قیمت فروش')
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' ریال'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('قیمت خرید')
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' ریال'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventory')
                    ->label('موجودی')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('واحد شمارش'),
                Tables\Columns\TextColumn::make('minimum_order')
                    ->label('حداقل سفارش')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead_time')
                    ->label('زمان انتظار')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('نقطه سفارش')
                    ->default(10)
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sales_tax')
                    ->label('مالیات فروش')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default(0)
                    ->numeric()
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' درصد'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_tax')
                    ->label('مالیات خرید')
                    ->default(0)
                    ->numeric()
                    ->formatStateUsing(
                        fn($state) =>
                        number_format($state) . ' درصد'
                    )
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->state(fn(Product $record) => ($record->type == 'Goods') ? 'کالا' : 'خدمات')
                    ->color(fn(Product $record) => ($record->type == 'Goods') ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('واحد شمارش ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax.title')
                    ->label('نوع مالیات')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('فعال'),
                Tables\Columns\TextColumn::make('created_at_jalali')
                    ->label('تاریخ ایجاد')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    Tables\Columns\TextColumn::make('inventory')
                    ->label('موجودی')
                    ->color(function ($record) {
                        return $record->inventory <= $record->reorder_point ? 'danger' : 'success';
                    })
                    ->description(function ($record) {
                        return $record->inventory <= $record->reorder_point ? 'موجودی کم - سفارش مجدد لازم است' : '';
                    }),
                Tables\Columns\TextColumn::make('reorder_point')->label('نقطه سفارش مجدد')
            ])
            ->defaultSort('created_at','desc')
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageProducts::route('/'),

        ];
    }


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('product_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('product_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('product_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('product_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('product_delete');
    }
}
