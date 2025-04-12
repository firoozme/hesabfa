<!DOCTYPE html>
<html>
<head>
    <title>تأیید کد</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>تأیید کد ورود</h1>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('company.verify-otp') }}">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            <div>
                <label for="otp">کد تأیید (برای تست توی لاگ چک کنید):</label>
                <input type="text" name="otp" id="otp" required>
            </div>
            <button type="submit">ورود</button>
        </form>
    </div>
</body>
</html>