<?php
namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $title;
    
    public string $titr1;
    public string $titr2;
    public string $titr3;
    public string $image;
    public string $logo;
    
    public static function group(): string
    {
        return 'landpage';
    }
}