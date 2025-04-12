<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Company;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CompanyResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CompanyResource\RelationManagers;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationLabel = 'شرکت ها';
    protected static ?string $pluralLabel = 'شرکتها';
    protected static ?string $label = 'شرکت';
    protected static ?string $navigationGroup = 'اشخاص';
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
                Tables\Columns\TextColumn::make('fullname')
                ->label('مالک')
                ->searchable(['firstname','lastnamne'])
                ->sortable(['firstname','lastnamne']),
                Tables\Columns\TextColumn::make('company_name')
                ->label('نام شرکت')
                ->default('-')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('email')
                ->label('ایمیل')
                ->default('-')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('mobile')
                ->label('مالک')
                ->searchable()
                ->sortable(),
                Tables\Columns\TextColumn::make('created_at_jalali')
                ->label('تاریخ عضویت')
                ->searchable(['created_at'])
                ->sortable(['created_at']),
            ])
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCompanies::route('/'),
        ];
    }


    // Role & Permissions
    public static function canViewAny(): bool
    {
        return Auth::user()?->can('company_view_any');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()?->can('company_view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('company_create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->can('company_update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->can('company_delete');
    }
}
