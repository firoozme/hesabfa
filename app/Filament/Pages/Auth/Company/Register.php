<?php

namespace App\Filament\Pages\Auth\Company;

use Carbon\Carbon;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Company;
use App\Models\OtpCode;
use Filament\Forms\Form;
use App\Models\Subscription;
use Filament\Actions\Action;
use App\Models\CompanySetting;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use App\Models\ProductCategory;
use Database\Seeders\TaxSeeder;
use App\Events\CompanyRegistered;
use Database\Seeders\BanksTableSeeder;
use Database\Seeders\ProductTypeSeeder;
use Database\Seeders\ProductUnitSeeder;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

/**
 * @property Form $form
 */
class Register extends SimplePage
{
    use CanUseDatabaseTransactions;
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'filament.pages.auth.Company.register';

    public ?array $data = [];
    public ?string $mobile = null;
    public bool $canResend = false;
    public int $resendTimer = 60;

    public function mount()
    {
        if (!session('mobile')) {
            return redirect('/company/login');
        }

        $this->mobile = session('mobile');
        $this->form->fill([
            'mobile' => session('mobile'),
        ]);

        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->callHook('beforeFill');
        $this->form->fill();
        $this->callHook('afterFill');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getMobileFormComponent(),
            $this->getFirstnameFormComponent(),
            $this->getLastnameFormComponent(),
            $this->getOTPFormComponent(),
        ])->statePath('data');
    }

    protected function getFirstnameFormComponent(): Component
    {
        return TextInput::make('firstname')
            ->label('نام')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getLastnameFormComponent(): Component
    {
        return TextInput::make('lastname')
            ->label('نام خانوادگی')
            ->required()
            ->maxLength(255);
    }

    protected function getMobileFormComponent(): Component
    {
        return TextInput::make('mobile')
            ->label('شماره موبایل')
            ->required()
            ->disabled()
            ->default($this->mobile);
    }

    protected function getOTPFormComponent(): Component
    {
        return TextInput::make('otp_code')
            ->label('کد دریافتی')
            ->required();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('register')
                ->label('ثبت نام')
                ->submit('register')
                ->extraAttributes(['class' => 'w-full']),
            Action::make('resend')
                ->label('ارسال دوباره کد')
                ->action('resendOtp')
                ->disabled(fn () => !$this->canResend)
                ->extraAttributes(['class' => 'w-full']),
        ];
    }

    public function getTitle(): string
    {
        return 'ثبت نام';
    }

    public function register()
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();

        // اعتبارسنجی شماره موبایل
        if (!preg_match('/^09\d{9}$/', $this->mobile)) {
            throw ValidationException::withMessages([
                'data.mobile' => 'شماره موبایل وارد شده معتبر نیست',
            ]);
        }

        $exist_company = Company::where('mobile', $this->mobile)->first();
        if ($exist_company) {
            throw ValidationException::withMessages([
                'data.mobile' => 'این شماره موبایل قبلا ثبت شده است',
            ]);
        }

        // اعتبارسنجی OTP
        $otp = OtpCode::where('mobile', $this->mobile)
            ->where('otp_code', $data['otp_code'])
            ->where('expires_at', '>=', Carbon::now())
            ->where('is_used', false)
            ->first();

        if (!$otp) {
            throw ValidationException::withMessages([
                'data.otp_code' => ['کد یک‌بارمصرف نامعتبر است یا منقضی شده است.'],
            ]);
        }

        // علامت‌گذاری کد به‌عنوان استفاده‌شده
        $otp->update(['is_used' => true]);

        $validated = $this->validate([
            'data.mobile' => 'required|digits:11',
            'data.firstname' => 'required',
            'data.lastname' => 'required',
        ], [
            'data.mobile.required' => 'وارد کردن شماره موبایل الزامی است.',
            'data.mobile.digits' => 'شماره موبایل باید دقیقاً ۱۱ رقم باشد.',
            'data.firstname.required' => 'وارد کردن نام الزامی است.',
            'data.lastname.required' => 'وارد کردن نام خانوادگی الزامی است.',
        ]);

        // Company Creation
        $company = Company::create([
            'mobile' => $this->mobile,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
        ]);

        // Store Creation
        Store::create([
            'title' => 'انبار من',
            'company_id' => $company->id,
            'address' => 'تهران',
            'phone_number' => '0',
            'is_default' => true,
        ]);

        app(BanksTableSeeder::class)->runWithCompanyId($company->id);
        app(ProductTypeSeeder::class)->runWithCompanyId($company->id);
        app(ProductUnitSeeder::class)->runWithCompanyId($company->id);
        app(TaxSeeder::class)->runWithCompanyId($company->id);

        // Set Default Plan
        $defaultPlan = Plan::where('is_default', true)->first();
        // if ($defaultPlan) {
        //     Subscription::create([
        //         'company_id' => $company->id,
        //         'plan_id' => $defaultPlan->id,
        //         'status' => 'active',
        //         'starts_at' => now(),
        //         'ends_at' => now()->addDays($defaultPlan->duration),
        //     ]);
        // }

        // Company Setting
        CompanySetting::create([
            'menu_position'=>'top',
            'company_id'=> $company->id,
        ]);

        // Product Category
        ProductCategory::create([
            'title'=>'گروه بندی محصول پیشفرض',
            'company_id'=> $company->id,
        ]);

        // لاگین شرکت
        auth()->guard('company')->login($company);

        Notification::make()
            ->title('ثبت‌نام موفق')
            ->body('ثبت‌نام شما با موفقیت انجام شد.')
            ->success()
            ->send();

        // هدایت به داشبورد
        return redirect()->intended(Filament::getUrl());
    }

    public function resendOtp(KavenegarService $smsService)
    {
        try {
            $this->rateLimit(3, 60);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('خطا')
                ->body('لطفاً 60 ثانیه صبر کنید و دوباره تلاش کنید.')
                ->danger()
                ->send();
            return;
        }

       // ایجاد کد OTP جدید
       $otpCode = $this->generateOtpCode($this->mobile);

        // اینجا باید کد ارسال SMS را پیاده‌سازی کنید
        // مثلاً: SmsService::send($this->mobile, "کد تایید شما: $otpCode");
        $smsService->send($this->mobile, "کد تایید شما: $otpCode->otp_code");
        Notification::make()
            ->title('کد جدید ارسال شد')
            ->body('کد تایید جدید به شماره شما ارسال شد.')
            ->success()
            ->send();

        // ریست تایمر و غیرفعال کردن دکمه
        $this->canResend = false;
        $this->resendTimer = 60;
        $this->dispatch('reset-timer');
    }

    protected function generateOtpCode(string $mobile): OtpCode
    {
        // حذف کدهای قدیمی برای این شماره موبایل
        OtpCode::where('mobile', $mobile)->delete();

        // تولید کد 6 رقمی تصادفی
        $code = mt_rand(100000, 999999);

        // ذخیره کد OTP در دیتابیس
        return OtpCode::create([
            'mobile' => $mobile,
            'otp_code' => $code,
            'expires_at' => Carbon::now()->addMinutes(5),
            'is_used' => false,
        ]);
    }
}