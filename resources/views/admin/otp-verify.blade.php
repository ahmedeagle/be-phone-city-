<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق بالرمز - لوحة التحكم</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { width: 100%; max-width: 400px; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); padding: 40px; text-align: center; }
        .icon { font-size: 64px; margin-bottom: 16px; }
        h2 { font-size: 22px; font-weight: 700; color: #111; margin-bottom: 8px; }
        .subtitle { font-size: 14px; color: #6b7280; margin-bottom: 24px; }
        .error { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .success { background: #f0fdf4; border: 1px solid #86efac; color: #16a34a; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .otp-input { width: 100%; padding: 14px; font-size: 24px; text-align: center; letter-spacing: 8px; border: 2px solid #d1d5db; border-radius: 8px; outline: none; margin-bottom: 16px; font-weight: 600; }
        .otp-input:focus { border-color: #3b82f6; }
        .btn-verify { width: 100%; padding: 14px; background: #3b82f6; color: #fff; font-size: 16px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; }
        .btn-verify:hover { background: #2563eb; }
        .btn-resend { margin-top: 16px; background: none; border: none; color: #6b7280; font-size: 14px; cursor: pointer; text-decoration: underline; }
        .btn-resend:hover { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔐</div>
        <h2>التحقق بالرمز</h2>
        <p class="subtitle">تم إرسال رمز التحقق إلى بريدك الإلكتروني</p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        @if($success)
            <div class="success">{{ $success }}</div>
        @endif

        <form method="POST" action="{{ route('admin.otp-verify.submit') }}">
            @csrf
            <input
                name="code"
                type="text"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                placeholder="أدخل الرمز المكون من 6 أرقام"
                dir="ltr"
                class="otp-input"
                value="{{ old('code') }}"
                autofocus
                required
            >
            <button type="submit" class="btn-verify">تحقق</button>
        </form>

        <form method="POST" action="{{ route('admin.otp-verify.resend') }}">
            @csrf
            <button type="submit" class="btn-resend">إعادة إرسال الرمز</button>
        </form>
    </div>
</body>
</html>
