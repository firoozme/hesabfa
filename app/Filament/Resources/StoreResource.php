<?php

namespace App\Filament\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use App\Models\Store;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StoreResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StoreResource\RelationManagers;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;
    protected static ?string $navigationLabel = 'انبار';
    protected static ?string $pluralLabel = 'انبارها';
    protected static ?string $label = 'انبار';
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
                Tables\Columns\TextColumn::make('company.mobile')
                    ->label('شرکت'),
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
                Tables\Columns\TextColumn::make('created_at_jalali')
                    ->label('تاریخ ایجاد')
                    ->searchable(['created_at']),

            ])->defaultSort('id', 'desc')
            ->headerActions([])
            ->filters([
                //
            ])
            ->actions([
              


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
        ];
    }
    protected static ?int $navigationSort = 8;


     // Role & Permissions
     public static function canViewAny(): bool
     {
         return Auth::user()?->can('store_view_any');
     }
 
     public static function canView(Model $record): bool
     {
         return Auth::user()?->can('store_view');
     }
 
     public static function canCreate(): bool
     {
         return Auth::user()?->can('store_create');
     }
 
     public static function canEdit(Model $record): bool
     {
         return Auth::user()?->can('store_update');
     }
 
     public static function canDelete(Model $record): bool
     {
         return Auth::user()?->can('store_delete');
     }
}
