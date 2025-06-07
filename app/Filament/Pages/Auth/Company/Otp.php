<?php
namespace App\Filament\Pages\Auth\Company;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\OtpCode;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\SimplePage;
use App\Services\Sms\KavenegarService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Pages\Concerns\InteractsWithFormActions;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

/**
 * @property Form $form
 */
class Otp extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'filament.pages.auth.Company.otp';

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

        if (auth('company')->check()) {
            redirect()->intended(Filament::getUrl());
        }

        // ایجاد و ارسال کد OTP در بارگذاری اولیه
        $otpCode = rand(100000, 999999);
        // OtpCode::create([
        //     'mobile' => $this->mobile,
        //     'otp_code' => $otpCode,
        //     'expires_at' => Carbon::now()->addMinutes(5),
        //     'is_used' => false,
        // ]);

        try {
            // $smsService->send($this->mobile, "کد تایید شما: $otpCode");
            // Notification::make()
            //     ->title('کد ارسال شد')
            //     ->body('کد تایید به شماره شما ارسال شد.')
            //     ->success()
            //     ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getTitle(): string
    {
        return 'کد تایید';
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('otp_code')
                ->label('کد تایید')
                ->required()
                ->autofocus()
                ->extraInputAttributes(['tabindex' => 1]),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('verify')
                ->label(__('تایید کد ارسالی'))
                ->submit('verify')
                ->extraAttributes(['class' => 'w-full']),
            Action::make('resend')
                ->label(__('ارسال دوباره کد'))
                ->action('resendOtp')
                ->disabled(fn () => !$this->canResend)
                ->extraAttributes(['class' => 'w-full']),
        ];
    }

    public function verify()
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();

        if (!preg_match('/^09\d{9}$/', $this->mobile)) {
            throw ValidationException::withMessages([
                'data.mobile' => 'شماره موبایل وارد شده معتبر نیست',
            ]);
        }

        $otpRecord = OtpCode::where('mobile', $this->mobile)
            ->where('otp_code', $data['otp_code'])
            ->where('expires_at', '>', Carbon::now())
            ->where('is_used', false)
            ->first();

        if (!$otpRecord) {
            throw ValidationException::withMessages([
                'data.otp_code' => ['کد یک‌بارمصرف نامعتبر است یا منقضی شده است.'],
            ]);
        }

        $company = Company::where('mobile', $this->mobile)->first();

        if (!$company) {
            return redirect('/company/login');
        }

        auth()->guard('company')->login($company);

        Notification::make()
            ->title('ورود موفق')
            ->body('ورود شما با موفقیت انجام شد.')
            ->success()
            ->send();

            session()->forget('subscription_expiration_notified');

        return redirect('/company');
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
    // ارسال کد OTP از طریق سرویس کاوه‌نگار
    try {
        $smsService->send($this->mobile, "کد تایید شما: $otpCode->otp_code");
        Notification::make()
            ->title('کد جدید ارسال شد')
            ->body('کد تایید جدید به شماره شما ارسال شد.')
            ->success()
            ->send();
    } catch (\Exception $e) {
        Notification::make()
            ->title('خطا')
            ->body($e->getMessage())
            ->danger()
            ->send();
        return;
    }

    // ریست تایمر و غیرفعال کردن دکمه
    $this->canResend = false;
    $this->resendTimer = 60;
    $this->dispatch('reset-timer');

    // لاگ برای دیباگ
    \Log::info('Resend OTP triggered', ['canResend' => $this->canResend, 'resendTimer' => $this->resendTimer]);
}
}