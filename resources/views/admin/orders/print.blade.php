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
            color: #222;
            font-size: 13px;
            line-height: 1.6;
            padding: 20px;
            direction: rtl;
        }

        .invoice-container {
            max-width: 780px;
            margin: 0 auto;
            background: white;
            padding: 30px 36px;
        }

        .print-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #1a7a4c;
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
            background: #15673f;
            transform: translateY(-2px);
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .invoice-header .company-side h1 {
            font-size: 18px;
            font-weight: 700;
            color: #222;
            margin-bottom: 2px;
        }

        .invoice-header .company-side p {
            font-size: 12px;
            color: #666;
        }

        .invoice-header .title-side {
            text-align: left;
        }

        .invoice-header .title-side h2 {
            font-size: 22px;
            font-weight: 800;
            color: #222;
            margin-bottom: 4px;
        }

        .invoice-header .title-side p {
            font-size: 12px;
            color: #666;
        }

        .divider {
            border: none;
            border-top: 2px solid #e0e0e0;
            margin: 0 0 18px 0;
        }

        /* Info section */
        .info-section {
            display: flex;
            gap: 28px;
            margin-bottom: 18px;
        }

        .info-section .info-col {
            flex: 1;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .info-table tr:nth-child(even) {
            background: #f7f7f7;
        }

        .info-table td {
            padding: 5px 8px;
        }

        .info-table td:first-child {
            color: #888;
            white-space: nowrap;
        }

        .info-table td:last-child {
            font-weight: 600;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .items-table thead tr {
            background: #f0f0f0;
        }

        .items-table th {
            padding: 10px 8px;
            text-align: right;
            font-weight: 700;
            border-bottom: 2px solid #ddd;
        }

        .items-table th.center {
            text-align: center;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .items-table .option-text {
            color: #999;
            font-size: 11px;
        }

        /* Totals */
        .totals-wrapper {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        .totals-table {
            width: 340px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .totals-table tr {
            border-bottom: 1px solid #eee;
        }

        .totals-table td {
            padding: 7px 10px;
        }

        .totals-table td:first-child {
            color: #555;
        }

        .totals-table td:last-child {
            text-align: left;
            font-weight: 600;
        }

        .totals-table .discount-row td {
            color: #16a34a;
        }

        .totals-table .vip-row td {
            color: #2563eb;
        }

        .totals-table .shipping-free td:last-child {
            color: #16a34a;
        }

        .totals-table .grand-total {
            background: #f0f0f0;
        }

        .totals-table .grand-total td {
            padding: 10px;
            border: none;
        }

        .totals-table .grand-total td:first-child {
            font-weight: 800;
            font-size: 15px;
            color: #222;
        }

        .totals-table .grand-total td:last-child {
            font-weight: 800;
            font-size: 16px;
            color: #222;
        }

        .grand-total-sub {
            font-size: 11px;
            font-weight: 400;
            color: #888;
        }

        /* Notes */
        .notes-section {
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border-right: 3px solid #1a7a4c;
        }

        .notes-section h3 {
            font-size: 14px;
            color: #222;
            margin-bottom: 6px;
        }

        .notes-section p {
            font-size: 12px;
            color: #666;
        }

        /* Footer */
        .footer-bar {
            background: #1a7a4c;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            text-align: center;
            font-size: 12px;
            margin-top: 14px;
        }

        .footer-bar span {
            margin: 0 14px;
        }

        .footer-note {
            text-align: center;
            font-size: 11px;
            color: #aaa;
            margin-top: 10px;
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

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
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
            $taxNumber = \App\Models\Setting::get('tax_number');
            $settingsModel = \App\Models\Setting::getSettings();
            $logoUrl = $settingsModel->logo_url;
        @endphp

        <!-- Header -->
        <div class="invoice-header">
            <div class="company-side">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" style="max-width: 130px; max-height: 70px; margin-bottom: 6px; display: block;">
                @endif
                <h1>{{ config('app.name', 'City Phones') }}</h1>
                <p>المملكة العربية السعودية</p>
            </div>
            <div class="title-side">
                <h2>فاتورة ضريبة مبسطة</h2>
                <p>فاتورة إلكترونية</p>
            </div>
        </div>

        <hr class="divider">

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-col">
                <table class="info-table">
                    <tr>
                        <td>الاسم التجاري</td>
                        <td>{{ config('app.name', 'City Phones') }}</td>
                    </tr>
                    @if($taxNumber)
                    <tr>
                        <td>الرقم الضريبي</td>
                        <td>{{ $taxNumber }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>رقم الفاتورة</td>
                        <td>{{ $order->invoice ? $order->invoice->invoice_number : $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td>طريقة الدفع</td>
                        <td>{{ $order->paymentMethod->name }}</td>
                    </tr>
                    <tr>
                        <td>التاريخ</td>
                        <td>{{ $order->invoice ? $order->invoice->invoice_date->format('Y/m/d') : $order->created_at->format('Y/m/d') }}</td>
                    </tr>
                </table>
            </div>
            <div class="info-col">
                <table class="info-table">
                    <tr>
                        <td>اسم العميل</td>
                        <td>{{ $order->user->name }}</td>
                    </tr>
                    <tr>
                        <td>رقم الطلب</td>
                        <td>{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td>قيمة الطلب</td>
                        <td>{{ number_format($order->total, 2) }} ر.س</td>
                    </tr>
                    @if ($order->location)
                    <tr>
                        <td>العنوان</td>
                        <td>{{ $order->location->city->name ?? '' }}{{ $order->location->street_address ? '، ' . $order->location->street_address : '' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td>الهاتف</td>
                        <td>{{ $order->user->phone ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th class="center">الكمية</th>
                    <th class="center">السعر بدون ضريبة</th>
                    <th class="center">الضريبة %</th>
                    <th class="center">السعر شامل الضريبة</th>
                    <th class="center">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $index => $item)
                    @php
                        $unitPriceInclTax = $item->price;
                        $unitPriceExclTax = $unitPriceInclTax / (1 + $taxPercentage / 100);
                        $lineTotal = $item->price * $item->quantity;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $item->product->name }}</strong>
                            @if ($item->productOption)
                                <br><span class="option-text">{{ $item->productOption->value }}</span>
                            @endif
                        </td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="center">{{ number_format($unitPriceExclTax, 2) }} ر.س</td>
                        <td class="center">{{ $taxPercentage }}%</td>
                        <td class="center">{{ number_format($unitPriceInclTax, 2) }} ر.س</td>
                        <td class="center" style="font-weight: 600;">{{ number_format($lineTotal, 2) }} ر.س</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-wrapper">
            <table class="totals-table">
                @php
                    $subtotalExclTax = $order->subtotal / (1 + $taxPercentage / 100);
                @endphp
                <tr>
                    <td>الإجمالي</td>
                    <td>{{ number_format($subtotalExclTax, 2) }} ر.س</td>
                </tr>
                @if ($order->discount > 0)
                    <tr class="discount-row">
                        <td>
                            قسيمة / خصم
                            @if ($order->discountCode)
                                ({{ $order->discountCode->code }})
                            @endif
                        </td>
                        <td>-{{ number_format($order->discount, 2) }} ر.س</td>
                    </tr>
                @endif
                @if (($order->vip_discount ?? 0) > 0)
                    <tr class="vip-row">
                        <td>
                            خصم VIP
                            @if ($order->vip_tier_label)
                                ({{ $order->vip_tier_label }})
                            @endif
                        </td>
                        <td>-{{ number_format($order->vip_discount, 2) }} ر.س</td>
                    </tr>
                @endif
                @if ($order->points_discount > 0)
                    <tr class="discount-row">
                        <td>خصم النقاط</td>
                        <td>-{{ number_format($order->points_discount, 2) }} ر.س</td>
                    </tr>
                @endif
                <tr class="{{ $order->shipping > 0 ? '' : 'shipping-free' }}">
                    <td>رسوم الشحن</td>
                    <td>{{ $order->shipping > 0 ? number_format($order->shipping, 2) . ' ر.س' : 'مجاني' }}</td>
                </tr>
                @if ($order->tax > 0)
                    <tr>
                        <td>ضريبة القيمة المضافة ({{ $taxPercentage }}%)</td>
                        <td>{{ number_format($order->tax, 2) }} ر.س</td>
                    </tr>
                @endif
                <tr class="grand-total">
                    <td>الإجمالي النهائي<br><span class="grand-total-sub">شامل الضريبة</span></td>
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
        <div class="footer-bar">
            <span>info@cityphonesa.com</span>
            <span>cityphonesa.com</span>
        </div>
        <div class="footer-note">
            هذه فاتورة إلكترونية صالحة بدون توقيع
        </div>
    </div>

    <script>
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>

</html>
