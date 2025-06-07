<?php

namespace App\Http\Controllers\Company;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\CompanyOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // نمایش فرم وارد کردن شماره موبایل
    public function showLoginForm()
    {
        return view('company.auth.login');
    }

    // ارسال OTP
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|numeric|digits:11', // فرض: شماره موبایل 11 رقمی
        ]);

        $mobile = $request->mobile;

        // چک کردن وجود شرکت با این شماره
        $company = Company::where('mobile', $mobile)->first();
        if (!$company) {
            return back()->withErrors(['mobile' => 'شماره موبایل ثبت‌نشده است.']);
        }

        // تولید کد OTP
        $otp = rand(100000, 999999); // کد 6 رقمی
        $expiresAt = Carbon::now()->addMinutes(5); // انقضا بعد از 5 دقیقه

        // ذخیره OTP
        CompanyOtp::updateOrCreate(
            ['mobile' => $mobile],
            ['otp' => $otp, 'expires_at' => $expiresAt]
        );

        // ارسال OTP (برای تست توی لاگ می‌ذاریم)
        Log::info("OTP for $mobile: $otp");
        // بعداً می‌تونید با API SMS جایگزین کنید، مثلاً:
        // sendSms($mobile, "کد ورود شما: $otp");

        return redirect()->route('company.verify')->with('mobile', $mobile);
    }

    // نمایش فرم وارد کردن OTP
    public function showVerifyForm()
    {
        $mobile = session('mobile');
        if (!$mobile) {
            return redirect()->route('company.login');
        }
        return view('company.auth.verify', compact('mobile'));
    }

    // اعتبارسنجی OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:6',
            'mobile' => 'required',
        ]);

        $mobile = $request->mobile;
        $otp = $request->otp;

        $companyOtp = CompanyOtp::where('mobile', $mobile)->first();

        if (!$companyOtp || $companyOtp->otp !== $otp || $companyOtp->expires_at < now()) {
            return back()->withErrors(['otp' => 'کد نامعتبر است یا منقضی شده.']);
        }

        // پیدا کردن شرکت و لاگین
        $company = Company::where('mobile', $mobile)->first();
        Auth::guard('company')->login($company); // فرض: گارد برای شرکت‌ها

        // پاک کردن OTP بعد از لاگین موفق
        $companyOtp->delete();

        return redirect()->route('company.dashboard');
    }

    // خروج
    public function logout()
    {
        Auth::guard('company')->logout();
        $request->session()->invalidate();
    $request->session()->regenerateToken();
        return redirect()->route('company.login');
    }
}
