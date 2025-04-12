<?php

return [

    'title' => 'ورود',

    'heading' => 'ورود به حساب کاربری',

    'actions' => [

        'register' => [
            'before' => 'یا',
            'label' => 'ایجاد حساب کاربری',
        ],

        'request_password_reset' => [
            'label' => 'رمز عبور خود را فراموش کرده‌اید؟',
        ],

    ],

    'form' => [

        'fiscalyear' => [
            'label' => 'سال مالی',
        ],
        'email' => [
            'label' => 'آدرس ایمیل',
        ],
        'mobile' => [
            'label' => 'شماره موبایل',
        ],
        'username' => [
            'label' => 'نام کاربری',
        ],

        'password' => [
            'label' => 'رمز عبور',
        ],

        'remember' => [
            'label' => 'مرا به خاطر بسپار',
        ],

        'actions' => [

            'authenticate' => [
                'label' => 'ورود',
            ],

        ],

    ],

    'messages' => [

        'failed' => 'اطلاعات وارد شده اشتباه است.',

    ],

    'notifications' => [

        'throttled' => [
            'title' => 'شما بیش از حد مجاز درخواست ورود داشته‌اید.',
            'body' => 'لطفاً :seconds ثانیه دیگر تلاش کنید.',
        ],

    ],

];
