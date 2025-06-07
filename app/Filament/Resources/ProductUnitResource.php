<?php

namespace App\Filament\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductUnit;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProductUnitResource\Pages;
use App\Filament\Resources\ProductUnitResource\RelationManagers;

class ProductUnitResource extends Resource
{
    protected static ?string $model = ProductUnit::class;
    protected static ?string $navigationLabel = 'واحد مالیاتی';
    protected static ?string $pluralLabel = 'واحدهای مالیاتی';
    protected static ?string $label = 'واحدهای مالیاتی';
    protected static ?string $navigationGroup = 'کالا و خدمات';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('عنوان واحد')
                    ->required()
                    ->unique(ignoreRecord: true,modifyRuleUsing: function (Unique $rule) {
                        return $rule->where('deleted_at', null);
                    })
                    ->maxLength(255)
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
                Tables\Columns\TextColumn::make('name')
                    ->label('عنوان واحد')
                    ->searchable()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('company.fullname')
                    ->label('شرکت')
                    ->searchable()
                    ->sortable(),
            ])
        ->defaultSort('created_at','desc')
        ->filters([
            SelectFilter::make('status')
                ->label('شرکت')
                ->relationship('company','fullname')
        ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ManageProductUnits::route('/'),
        ];
    }


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('product_unit_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('product_unit_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('product_unit_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('product_unit_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('product_unit_delete');
    }
}
