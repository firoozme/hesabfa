<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Plan;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PlanResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PlanResource\RelationManagers;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationLabel = 'پلن‌ها';
    protected static ?string $pluralLabel = 'پلن‌ها';
    protected static ?string $label = 'پلن';
    protected static ?string $navigationGroup = 'اشتراک‌ها';
    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns([
                        'sm' => 1,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('نام پلن')
                            ->required(),
                        TextInput::make('price')
                            ->label('قیمت (ریال)')
                            ->default(0)
                            ->required()
                            ->mask(RawJs::make(<<<'JS'
                                    $money($input)
                                JS))
                            ->dehydrateStateUsing(function ($state) {
                                return (float) str_replace(',', '', $state);
                            })
                            ->postfix('ریال')
                            ->hidden(fn ($record) => $record?->is_default),
                        TextInput::make('duration')
                            ->label('مدت زمان (روز)')
                            ->numeric()
                            ->required(),
                    ]),
                RichEditor::make('features')
                    ->label('ویژگی‌ها')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true)
                    ->hidden(fn ($record) => $record?->is_default),
                
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام پلن')->searchable(),
                TextColumn::make('price')->label('قیمت')->money('irr')->sortable(),
                TextColumn::make('duration')->label('مدت زمان')->formatStateUsing(fn ($state) => "$state روز"),
                TextColumn::make('is_active')->label('وضعیت')->formatStateUsing(fn ($state) => $state ? 'فعال' : 'غیرفعال'),
                TextColumn::make('is_default')->label('پیش‌فرض')->formatStateUsing(fn ($state) => $state ? 'بله' : 'خیر'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('وضعیت')
                    ->trueLabel('فعال')
                    ->falseLabel('غیرفعال'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('پیش‌فرض')
                    ->trueLabel('بله')
                    ->falseLabel('خیر'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->is_default),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make()
                //     ->hidden(fn ($records) => $records->contains(fn ($record) => $record->is_default)),
            ])
            ->defaultPaginationPageOption(5);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('plan_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('plan_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('plan_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('plan_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('plan_delete');
    }
}