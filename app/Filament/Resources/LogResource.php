<?php
namespace App\Filament\Resources;

use App\Models\Log;
use Filament\Tables;
use Filament\Tables\Table;
use Morilog\Jalali\Jalalian;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\LogResource\Pages;

class LogResource extends Resource
{
    protected static ?string $model = Log::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'لاگ‌ها';
    protected static ?string $pluralLabel = 'لاگ‌ها';
    protected static ?string $label = 'لاگ';
    protected static ?string $navigationGroup = 'گزارشات';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->wrap()
                    ->searchable(),
                    Tables\Columns\TextColumn::make('loggable.name')
                    ->label('انجام‌دهنده')
                    ->default('ناشناس')
                    ->formatStateUsing(function ($record) {
                        if ($record->loggable) {
                            $type = class_basename($record->loggable_type);
                            $name = $record->loggable->fullname ?? $record->loggable->username;
                            return "{$name}";
                        }
                        return 'ناشناس';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ و زمان')
                    ->formatStateUsing(fn ($state) => Jalalian::fromCarbon($state)->format('Y/m/d H:i'))
                    ->sortable(),
            ])
            ->defaultSort('created_at','desc')
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogs::route('/'),
        ];
    }



     // Role & Permissions
     public static function canViewAny(): bool
     {
         return Auth::user()?->can('log_view_any');
     }
 
     public static function canView(Model $record): bool
     {
         return Auth::user()?->can('log_view');
     }
 
     public static function canCreate(): bool
     {
         return Auth::user()?->can('log_create');
     }
 
     public static function canEdit(Model $record): bool
     {
         return Auth::user()?->can('log_update');
     }
 
     public static function canDelete(Model $record): bool
     {
         return Auth::user()?->can('log_delete');
     }
}