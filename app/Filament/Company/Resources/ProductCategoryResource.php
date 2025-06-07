<?php

namespace App\Filament\Company\Resources;

use stdClass;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProductCategory;
use Filament\Resources\Resource;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\ProductCategoryResource\Pages;
use App\Filament\Company\Resources\ProductCategoryResource\RelationManagers;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;
    protected static ?string $modelLabel = 'گروه بندی محصول';
    protected static ?string $pluralModelLabel = 'گروه بندی محصولات';
    protected static ?string $navigationGroup = 'کالا و خدمات';

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Item')
                    ->label('')
                    ->schema([

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->label('عنوان گروه ')
                            ->maxLength(255),

                        SelectTree::make('parent_id')
                            ->label('زیرگروه')
                            ->relationship(relationship: 'category', titleAttribute: 'title', parentAttribute: 'parent_id', modifyQueryUsing: fn (Builder $query) => $query->where('company_id', auth()->user('company')->id))
                            ->enableBranchNode()
                            ->placeholder('انتخاب زیرگروه')
                            ->withCount()
                            ->searchable()
                            ->emptyLabel('بدون نتیجه'),
                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات')
                            ->columnSpanFull(),
                    ])->columns(2)
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
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان دسته')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.title')
                    ->label('گروه بندی')
                    ->numeric()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('products_count')
                ->counts('products')
                    ->label('تعداد محصولات')
                    ->sortable(['title']),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
    protected static ?int $navigationSort = 4;
        public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
    }
}
