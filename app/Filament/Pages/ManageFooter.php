<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class ManageFooter extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;
    protected static ?string $navigationLabel = 'صفحه اصلی';
    protected static ?string $pluralLabel = 'صفحه اصلی';
    protected static ?string $label = 'تنظیمات صفحه اصلی';
    protected static ?string $navigationGroup = 'تنظیمات';
    protected static ?string $title = 'تنظیمات صفحه اصلی';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('logo')
                ->label('لوگو')
                ->disk('public')
                ->directory('setting/logo')
                ->visibility('private')
                ->deleteUploadedFileUsing(function ($file) {
                    // Optional: Define how to delete the file
                    $imagePath = env('APP_ROOT').'upload/setting/' . $file;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }),
                FileUpload::make('image')
                ->label('عکس')
                ->disk('public')
                ->directory('setting/image')
                ->visibility('private')
                ->deleteUploadedFileUsing(function ($file) {
                    // Optional: Define how to delete the file
                    $imagePath = env('APP_ROOT').'upload/setting/' . $file;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }),
                TextInput::make('title')
                ->label('عنوان')
                ->columnSpanFull(),
                Section::make()
                ->columns([
                    'sm' => 1,
                    'xl' => 3,
                    '2xl' => 8,
                ])
                ->schema([
                    TextInput::make('titr1')
                    ->label('تیتر1'),
                    TextInput::make('titr2')
                    ->label('تیتر2'),
                    TextInput::make('titr3')
                    ->label('تیتر3'),
                    
                ])
            ]);
    }
}
