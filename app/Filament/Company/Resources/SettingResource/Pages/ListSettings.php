<?php

namespace App\Filament\Company\Resources\SettingResource\Pages;

use Filament\Actions;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Company\Resources\SettingResource;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;
    public function mount(): void
    {
        // بررسی لاگین بودن کاربر
        if (!Auth::check()) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $company = auth()->user('company');
        if (!$company) {
            abort(403, 'شرکت یافت نشد');
        }

        $setting = \App\Models\CompanySetting::where('company_id', $company->id)->first();
        if ($setting) {
            // هدایت به صفحه ویرایش
            redirect()->to($this->getResource()::getUrl('edit', ['record' => $setting->id]));
        } else {
            // چون صفحه create ندارید، می‌توانید خطا یا هدایت به جای دیگر انجام دهید
            abort(404, 'تنظیمات یافت نشد');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
