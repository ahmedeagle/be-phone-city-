<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة {{ $order->invoice ? $order->invoice->invoice_number : $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            direction: rtl;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }

        .print-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .print-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .print-button:active {
            transform: translateY(0);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
        }

        .company-details h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .company-details p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .invoice-meta {
            text-align: left;
        }

        .invoice-meta h2 {
            color: #e74c3c;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .invoice-meta p {
            font-size: 14px;
            margin: 5px 0;
        }

        .invoice-meta strong {
            color: #2c3e50;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
        }

        .detail-block {
            flex: 1;
        }

        .detail-block h3 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-block p {
            font-size: 14px;
            margin: 5px 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            direction: rtl;
        }

        .items-table thead {
            background: #34495e;
            color: white;
        }

        .items-table th {
            padding: 12px;
            text-align: right;
            font-weight: 600;
            font-size: 14px;
            direction: rtl;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
            text-align: right;
            direction: rtl;
        }

        .items-table tbody tr:hover {
            background: #f8f9fa;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 40px;
            direction: rtl;
        }

        .totals-table {
            width: 350px;
            direction: rtl;
        }

        .totals-table tr {
            border-bottom: 1px solid #ecf0f1;
        }

        .totals-table td {
            padding: 10px;
            font-size: 14px;
        }

        .totals-table td:first-child {
            color: #7f8c8d;
            font-weight: 500;
            text-align: right;
        }

        .totals-table td:last-child {
            text-align: left;
            font-weight: 600;
        }

        .totals-table .total-row {
            background: #2c3e50;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }

        .totals-table .total-row td {
            padding: 15px 10px;
            border: none;
        }

        .notes-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-right: 4px solid #3498db;
        }

        .notes-section h3 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .notes-section p {
            font-size: 14px;
            color: #7f8c8d;
        }

        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-completed {
            background: #27ae60;
            color: white;
        }

        .badge-progress {
            background: #3498db;
            color: white;
        }

        .badge-cancelled {
            background: #e74c3c;
            color: white;
        }

        @media print {
            body {
                padding: 0;
            }

            .invoice-container {
                padding: 20px;
            }

            .no-print,
            .print-button {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Print Button -->
    <button class="print-button no-print" onclick="window.print()">
        🖨️ طباعة الفاتورة
    </button>

    <div class="invoice-container">
        @php
            $taxPercentage = \App\Models\Setting::get('tax_percentage', 0);
        @endphp
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-details">
                <h1>{{ config('app.name', 'اسم الشركة') }}</h1>
                <p>{{ config('company.address', '123 شارع الأعمال') }}</p>
                <p>{{ config('company.city', 'المدينة') }}، {{ config('company.country', 'الدولة') }}</p>
                <p>الهاتف: {{ config('company.phone', '+1234567890') }}</p>
                <p>البريد الإلكتروني: {{ config('company.email', 'info@company.com') }}</p>
            </div>
            <div class="invoice-meta">
                <h2>فاتورة</h2>
                @if ($order->invoice)
                    <p><strong>رقم الفاتورة:</strong> {{ $order->invoice->invoice_number }}</p>
                    <p><strong>تاريخ الفاتورة:</strong> {{ $order->invoice->invoice_date->format('Y/m/d') }}</p>
                @endif
                <p><strong>رقم الطلب:</strong> {{ $order->order_number }}</p>
                <p><strong>تاريخ الطلب:</strong> {{ $order->created_at->format('Y/m/d') }}</p>

            </div>
        </div>

        <!-- Invoice Details -->
        <div class="invoice-details">
            <!-- Bill To -->
            <div class="detail-block">
                <h3>الفاتورة إلى</h3>
                <p><strong>{{ $order->user->name }}</strong></p>
                <p>{{ $order->user->email }}</p>
                <p>{{ $order->user->phone }}</p>
            </div>

            <!-- Ship To -->
            @if ($order->location)
                <div class="detail-block">
                    <h3>الشحن إلى</h3>
                    <p><strong>{{ $order->location->first_name }} {{ $order->location->last_name }}</strong></p>
                    <p>{{ $order->location->street_address }}</p>
                    <p>{{ $order->location->city->name }}، {{ $order->location->country }}</p>
                    <p>{{ $order->location->phone }}</p>
                    @if ($order->location->email)
                        <p>{{ $order->location->email }}</p>
                    @endif
                </div>
            @endif

            <!-- Payment Info -->
            <div class="detail-block">
                <h3>معلومات الدفع</h3>
                <p><strong>طريقة الدفع:</strong> {{ $order->paymentMethod->name }}</p>
                <p><strong>طريقة التوصيل:</strong>
                    @if ($order->delivery_method === 'home_delivery')
                        توصيل منزلي
                    @else
                        استلام من المتجر
                    @endif
                </p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-left">المجموع</th>
                    <th class="text-left">السعر</th>
                    <th class="text-center">الكمية</th>
                    <th>الخيار</th>
                    <th>المنتج</th>
                    <th>#</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $priceExclTax = $item->price / (1 + $taxPercentage / 100);
                        $totalExclTax = $item->price * $item->quantity / (1 + $taxPercentage / 100);
                    @endphp
                    <tr>
                        <td class="text-left">{{ number_format($totalExclTax, 2) }} ر.س</td>
                        <td class="text-left">{{ number_format($priceExclTax, 2) }} ر.س</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td>
                            @if ($item->productOption)
                                @php
                                    $optionType = $item->productOption->type === 'color' ? 'اللون' : 'الحجم';
                                @endphp
                                {{ $optionType }}: {{ $item->productOption->value }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <strong>{{ $item->product->name }}</strong>
                        </td>
                        <td>{{ $index + 1 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                @php
                    $subtotalExclTax = $order->subtotal / (1 + $taxPercentage / 100);
                    $discountExclTax = $order->discount / (1 + $taxPercentage / 100);
                    $pointsDiscountExclTax = $order->points_discount / (1 + $taxPercentage / 100);
                @endphp
                <tr>
                    <td>المجموع الفرعي (غير شامل الضريبة)</td>
                    <td>{{ number_format($subtotalExclTax, 2) }} ر.س</td>
                </tr>
                @if ($order->discount > 0)
                    <tr>
                        <td>
                            الخصم (غير شامل الضريبة)
                            @if ($order->discountCode)
                                ({{ $order->discountCode->code }})
                            @endif
                        </td>
                        <td>-{{ number_format($discountExclTax, 2) }} ر.س</td>
                    </tr>
                @endif
                @if ($order->points_discount > 0)
                    <tr>
                        <td>خصم النقاط (غير شامل الضريبة)</td>
                        <td>-{{ number_format($pointsDiscountExclTax, 2) }} ر.س</td>
                    </tr>
                @endif
                @if ($order->shipping > 0)
                    <tr>
                        <td>الشحن</td>
                        <td>{{ number_format($order->shipping, 2) }} ر.س</td>
                    </tr>
                @endif
                @if ($order->tax > 0)
                    <tr>
                        <td>الضريبة ({{ $taxPercentage }}%)</td>
                        <td>{{ number_format($order->tax, 2) }} ر.س</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td>{{ number_format($order->total, 2) }} ر.س</td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        @if ($order->notes || ($order->invoice && $order->invoice->notes))
            <div class="notes-section">
                <h3>ملاحظات</h3>
                @if ($order->invoice && $order->invoice->notes)
                    <p><strong>ملاحظات الفاتورة:</strong> {{ $order->invoice->notes }}</p>
                @endif
                @if ($order->notes)
                    <p><strong>ملاحظات الطلب:</strong> {{ $order->notes }}</p>
                @endif
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>شكراً لتعاملكم معنا!</p>
            <p>هذه فاتورة إلكترونية صالحة بدون توقيع.</p>
            <p>تم الإنشاء في {{ now()->locale('ar')->translatedFormat('j F Y الساعة h:i A') }}</p>
        </div>
    </div>

    <script>
        // Print keyboard shortcut
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>

</html>
