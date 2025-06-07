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

class Login extends SimplePage
{

    use InteractsWithFormActions;

    use WithRateLimiting;

    protected static string $view = 'filament.pages.auth.Company.login';

    public ?array $data = [];
    public ?string $mobile = null;

    public function mount(): void
    {
        if (auth('company')->check()) {
            redirect()->intended(Filament::getUrl());
        }
        $this->form->fill();
    }

    public function getTitle(): string
    {

        return 'ورود | ثبت نام';

    }

    public function form(Form $form): Form
    {

        return $form->schema([
            TextInput::make('mobile')
                ->label('شماره موبایل')
                ->required()
                ->autofocus()
                ->extraInputAttributes(['tabindex' => 1]),
        ])->statePath('data');

    }
    protected function getFormActions(): array
    {
        return [
            Action::make('login')
                ->label(__('ورود'))
                ->submit('login')
                ->extraAttributes(['class' => 'w-full']),
        ];
    }
    public function login(KavenegarService $smsService)
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();
        // اعتبارسنجی شماره موبایل
        if (! preg_match('/^09\d{9}$/', $data['mobile'])) {
            throw ValidationException::withMessages([
                'data.mobile' => 'شماره موبایل وارد شده معتبر نیست',
            ]);
        }

       // بررسی وجود شماره موبایل در جدول companies
        $company = Company::where('mobile', $data['mobile'])->first();
        $this->mobile = $data['mobile'];

        // // تولید OTP
       $otpCode = $this->generateOtpCode($this->mobile);
       $smsService->send($this->mobile, "کد تایید شما: $otpCode->otp_code");
        // ارسال OTP (اینجا باید از سرویس پیامکی یا ایمیلی استفاده کنید)
        // مثال: SmsService::send($data['mobile'], "Your OTP is: $otpCode");


        Notification::make()
            ->title(__('کد تایید برای شماره '. $data['mobile'] .' پیامک شد'))
            ->success()
            ->send();

        if (! $company) {
            // هدایت مستقیم به فرم ثبت‌نام
            return redirect('/company/register')->with('mobile', $data['mobile']);
        } else {
            // هدایت به فرم  OTP
            return redirect('/company/send-otp')->with('mobile', $data['mobile']);
        }

        // اطمینان از آپدیت فرم
        // $this->reset(['data']);

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
