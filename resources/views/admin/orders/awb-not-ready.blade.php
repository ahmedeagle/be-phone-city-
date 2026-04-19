<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوليصة الشحن - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            direction: rtl;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 40px;
            max-width: 480px;
            width: 90%;
            text-align: center;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }
        .message {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 24px;
        }
        .status-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .order-info {
            background: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #374151;
        }
        .order-info div { margin-bottom: 6px; }
        .order-info strong { color: #111827; }
        .btn {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            margin: 4px;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="card">
        @if(isset($status) && $status === 'error')
            <div class="icon">❌</div>
            <div class="title">فشل تحميل بوليصة الشحن</div>
            <div class="status-badge error">خطأ في الاتصال</div>
            <div class="message">
                حدث خطأ أثناء تحميل البوليصة من OTO.<br>
                يرجى المحاولة مرة أخرى بعد قليل.
            </div>
        @else
            <div class="icon">📦</div>
            <div class="title">بوليصة الشحن غير جاهزة بعد</div>
            <div class="status-badge">{{ $status ?? 'قيد المعالجة' }}</div>
            <div class="message">
                لم يتم تعيين شركة شحن لهذا الطلب بعد من قبل OTO.<br>
                البوليصة ستكون متاحة بعد تعيين شركة الشحن ورقم التتبع.
            </div>
        @endif

        <div class="order-info">
            <div><strong>رقم الطلب:</strong> {{ $order->order_number }}</div>
            <div><strong>رقم OTO:</strong> {{ $order->oto_order_id }}</div>
            @if($order->tracking_number)
                <div><strong>رقم التتبع:</strong> {{ $order->tracking_number }}</div>
            @endif
        </div>

        <a href="javascript:location.reload()" class="btn btn-primary">إعادة المحاولة</a>
        <a href="javascript:window.close()" class="btn btn-secondary">إغلاق</a>
    </div>
</body>
</html>
