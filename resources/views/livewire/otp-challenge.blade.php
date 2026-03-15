<div style="width: 100%; max-width: 400px; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); padding: 40px; text-align: center;">
    <div style="font-size: 64px; margin-bottom: 16px;">🔐</div>
    <h2 style="font-size: 22px; font-weight: 700; color: #111; margin-bottom: 8px;">التحقق بالرمز</h2>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 24px;">تم إرسال رمز التحقق إلى بريدك الإلكتروني</p>

    @if($error)
        <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ $error }}
        </div>
    @endif

    @if($success)
        <div style="background: #f0fdf4; border: 1px solid #86efac; color: #16a34a; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ $success }}
        </div>
    @endif

    <form wire:submit.prevent="verify">
        <input
            wire:model="code"
            type="text"
            maxlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            placeholder="أدخل الرمز المكون من 6 أرقام"
            dir="ltr"
            style="width: 100%; padding: 14px; font-size: 24px; text-align: center; letter-spacing: 8px; border: 2px solid #d1d5db; border-radius: 8px; outline: none; margin-bottom: 16px; font-weight: 600;"
            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#d1d5db'"
        >

        <button type="submit"
            style="width: 100%; padding: 14px; background: #3b82f6; color: #fff; font-size: 16px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer;"
            onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
            تحقق
        </button>
    </form>

    <button wire:click="resend"
        style="margin-top: 16px; background: none; border: none; color: #6b7280; font-size: 14px; cursor: pointer; text-decoration: underline;"
        onmouseover="this.style.color='#3b82f6'" onmouseout="this.style.color='#6b7280'">
        إعادة إرسال الرمز
    </button>
</div>
