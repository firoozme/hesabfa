<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود شرکت</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">ورود به پنل شرکت</h1>

            @isset($errors)
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <ul class="list-disc pr-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
                
            @endisset
            

            <form method="POST" action="{{ route('company.send-otp') }}">
                @csrf
                <div class="mb-4">
                    <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل</label>
                    <input type="text" name="phone" id="phone" required
                           class="w-full p-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit"
                        class="w-full bg-indigo-600 text-white p-2 rounded-md hover:bg-indigo-700 transition">
                    ارسال کد
                </button>
            </form>
        </div>
    </div>
</body>
</html>