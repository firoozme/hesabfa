<?php

namespace App\Filament\Company\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Setting;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CompanySetting;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Company\Resources\SettingResource\Pages;
use App\Filament\Company\Resources\SettingResource\RelationManagers;

class SettingResource extends Resource
{
    protected static ?string $model = CompanySetting::class;
    protected static ?string $navigationLabel = 'تنظیمات';
    protected static ?string $pluralLabel = 'تنظیمات';
    protected static ?string $label = 'تنظیمات';
    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    protected static ?int $navigationSort = 100;
    // هدایت مستقیم به صفحه ویرایش
    // public static function getNavigationUrl(): string
    // {
    //     $company = auth()->user('company');
       
    //     $setting = CompanySetting::where('company_id', $company->id)->first();
    //     if ($setting) {
    //         return static::getUrl('edit', ['record' => $setting->id]);
    //     }

    //     // اگر تنظیمات وجود نداشت، به صفحه ایجاد هدایت شود
    //     return static::getUrl('create');
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               
                Forms\Components\Select::make('menu_position')
                    ->label('موقعیت منو')
                    ->options([
                        'right' => 'راست',
                        'top' => 'بالا',
                    ])
                    ->default(auth()->user()->settings?->menu_position ?? 'right')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            // 'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
    // محدود کردن دسترسی به تنظیمات کاربر فعلی
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', auth()->user('company')->id);
    }
    // protected static bool $shouldRegisterNavigation = false;


    

   
}
