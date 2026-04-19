<x-filament-panels::page>
    <style>
        /* ===== Report Styles ===== */
        .rpt-container { direction: rtl; font-family: inherit; }

        /* Cards */
        .rpt-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }
        .dark .rpt-card { background: #1f2937; border-color: #374151; }

        .rpt-card-title {
            font-weight: 700;
            font-size: 15px;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .dark .rpt-card-title { color: #f9fafb; border-color: #374151; }

        /* Grid */
        .rpt-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
        .rpt-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 16px; }
        .rpt-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px; }

        @media (max-width: 768px) {
            .rpt-grid-4, .rpt-grid-3, .rpt-grid-2 { grid-template-columns: repeat(2, 1fr); }
        }

        /* Stat card */
        .rpt-stat {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .dark .rpt-stat { background: #1f2937; border-color: #374151; }
        .rpt-stat-value { font-size: 28px; font-weight: 800; line-height: 1.2; }
        .rpt-stat-label { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .rpt-stat-sub { font-size: 11px; color: #9ca3af; margin-top: 4px; }

        /* Tables */
        .rpt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .rpt-table th {
            background: #f9fafb;
            font-weight: 600;
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            padding: 10px 14px;
            text-align: right;
            border-bottom: 2px solid #e5e7eb;
        }
        .dark .rpt-table th { background: #111827; color: #9ca3af; border-color: #374151; }
        .rpt-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
        }
        .dark .rpt-table td { border-color: #1f2937; color: #d1d5db; }
        .rpt-table tr:hover td { background: #f9fafb; }
        .dark .rpt-table tr:hover td { background: #111827; }
        .rpt-table .text-left { text-align: left; }
        .rpt-table .text-center { text-align: center; }
        .rpt-table tfoot td {
            background: #f3f4f6;
            font-weight: 700;
            border-top: 2px solid #d1d5db;
        }
        .dark .rpt-table tfoot td { background: #111827; border-color: #4b5563; }

        /* Financial table */
        .rpt-fin-table { width: 100%; font-size: 14px; }
        .rpt-fin-table td { padding: 8px 0; }
        .rpt-fin-row-total td {
            border-top: 2px solid #111827;
            padding-top: 12px;
            font-weight: 700;
        }
        .dark .rpt-fin-row-total td { border-color: #e5e7eb; }

        /* Colors */
        .c-green { color: #059669; }
        .c-red { color: #dc2626; }
        .c-purple { color: #7c3aed; }
        .c-orange { color: #ea580c; }
        .c-amber { color: #d97706; }
        .c-blue { color: #2563eb; }
        .c-gray { color: #6b7280; }
        .c-dark { color: #111827; }
        .dark .c-dark { color: #f9fafb; }

        /* Badges */
        .rpt-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; }
        .badge-indigo { background: #e0e7ff; color: #3730a3; }
        .badge-cyan { background: #cffafe; color: #155e75; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .dark .badge-green { background: rgba(22,101,52,.2); color: #4ade80; }
        .dark .badge-yellow { background: rgba(133,77,14,.2); color: #fbbf24; }
        .dark .badge-blue { background: rgba(30,64,175,.2); color: #60a5fa; }
        .dark .badge-red { background: rgba(153,27,27,.2); color: #f87171; }
        .dark .badge-purple { background: rgba(107,33,168,.2); color: #c084fc; }
        .dark .badge-indigo { background: rgba(55,48,163,.2); color: #818cf8; }
        .dark .badge-cyan { background: rgba(21,94,117,.2); color: #22d3ee; }

        /* Discount section */
        .rpt-discount-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .dark .rpt-discount-section { background: rgba(17,24,39,.5); }
        .rpt-discount-header {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rpt-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

        /* Alert */
        .rpt-alert {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 16px;
        }
        .dark .rpt-alert { background: rgba(217,119,6,.1); border-color: #92400e; }

        /* Order section header */
        .rpt-order-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: rgba(249,250,251,.5);
            border-bottom: 1px solid #f3f4f6;
        }
        .dark .rpt-order-header { background: rgba(17,24,39,.3); border-color: #1f2937; }

        /* Discount row on orders */
        .rpt-order-discounts {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            padding: 6px 16px;
            font-size: 12px;
            background: rgba(254,242,242,.5);
            border-bottom: 1px solid #f3f4f6;
        }
        .dark .rpt-order-discounts { background: rgba(153,27,27,.05); border-color: #1f2937; }

        /* Delivery box */
        .rpt-delivery-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .dark .rpt-delivery-box { background: rgba(17,24,39,.5); }

        /* Cancel box */
        .rpt-cancel-box {
            background: #fef2f2;
            border-radius: 8px;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }
        .dark .rpt-cancel-box { background: rgba(153,27,27,.15); }

        /* Mono */
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        /* Cancelled row */
        .rpt-cancelled { opacity: 0.5; }

        /* Totals bar */
        .rpt-total-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #111827;
            padding-top: 14px;
            margin-top: 14px;
        }
        .dark .rpt-total-bar { border-color: #e5e7eb; }

        /* Print */
        @media print {
            body * { visibility: hidden !important; }
            #report-content, #report-content * { visibility: visible !important; }
            #report-content { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
            .print-break { page-break-before: always; }
            .fi-sidebar, .fi-topbar, nav, header, footer { display: none !important; }
            @page { margin: 10mm; size: A4; }
            .rpt-grid-4 { grid-template-columns: repeat(4, 1fr); }
            .rpt-grid-3 { grid-template-columns: repeat(3, 1fr); }
            .rpt-grid-2 { grid-template-columns: repeat(2, 1fr); }
        }

        /* No results */
        .rpt-empty { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .rpt-empty svg { width: 64px; height: 64px; margin: 0 auto 12px; }

        /* Status dot */
        .rpt-status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
        }
    </style>

    <div class="rpt-container" id="report-content">
        {{-- Date Picker & Actions --}}
        <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:20px" class="no-print">
            <div style="max-width:280px">
                {{ $this->form }}
            </div>
            <x-filament::button icon="heroicon-o-printer" color="gray" onclick="window.print()">
                طباعة التقرير
            </x-filament::button>
        </div>

        @php $data = $this->getReportData(); @endphp

        {{-- Print Header --}}
        <div style="display:none;text-align:center;border-bottom:2px solid #111;padding-bottom:14px;margin-bottom:20px" class="print:block">
            <h1 style="font-size:22px;font-weight:700">التقرير المحاسبي اليومي</h1>
            <p style="font-size:16px;margin-top:4px">{{ $data['date']->format('Y-m-d') }} — {{ $data['date']->translatedFormat('l j F Y') }}</p>
        </div>

        {{-- Alerts --}}
        @if($data['awaitingReview'] > 0 || $data['pendingOrders'] > 0)
        <div class="rpt-alert no-print">
            <div style="font-weight:700;color:#92400e;margin-bottom:4px">⚠️ تنبيهات تحتاج اهتمام</div>
            <div style="display:flex;gap:20px;font-size:13px;color:#b45309">
                @if($data['awaitingReview'] > 0)
                <span>{{ $data['awaitingReview'] }} طلب بانتظار مراجعة الدفع</span>
                @endif
                @if($data['pendingOrders'] > 0)
                <span>{{ $data['pendingOrders'] }} طلب معلق</span>
                @endif
            </div>
        </div>
        @endif

        {{-- SECTION 1: Key Metrics --}}
        <div class="rpt-grid-4">
            <div class="rpt-stat">
                <div class="rpt-stat-value c-dark">{{ $data['totalOrders'] }}</div>
                <div class="rpt-stat-label">إجمالي الطلبات</div>
                <div class="rpt-stat-sub">مدفوعة: {{ $data['paidOrdersCount'] }}</div>
            </div>
            <div class="rpt-stat">
                <div class="rpt-stat-value c-green">{{ number_format($data['totalRevenue'], 2) }}</div>
                <div class="rpt-stat-label">صافي الإيرادات (ر.س)</div>
            </div>
            <div class="rpt-stat">
                <div class="rpt-stat-value c-red">{{ number_format($data['totalAllDiscounts'], 2) }}</div>
                <div class="rpt-stat-label">إجمالي الخصومات (ر.س)</div>
            </div>
            <div class="rpt-stat">
                <div class="rpt-stat-value c-blue">{{ $data['itemsSold'] }}</div>
                <div class="rpt-stat-label">منتجات مباعة</div>
            </div>
        </div>

        {{-- SECTION 2: Financial + Discounts --}}
        <div class="rpt-grid-2">
            {{-- Financial Summary --}}
            <div class="rpt-card">
                <div class="rpt-card-title">💰 الملخص المالي (الطلبات المدفوعة)</div>
                <table class="rpt-fin-table">
                    <tr>
                        <td>إجمالي المبيعات (قبل الخصم)</td>
                        <td class="text-left" style="font-weight:600">{{ number_format($data['totalSubtotal'], 2) }} ر.س</td>
                    </tr>
                    @if($data['totalDiscount'] > 0)
                    <tr>
                        <td>(-) خصم كوبونات</td>
                        <td class="text-left c-red" style="font-weight:600">-{{ number_format($data['totalDiscount'], 2) }} ر.س</td>
                    </tr>
                    @endif
                    @if($data['totalVipDiscount'] > 0)
                    <tr>
                        <td>(-) خصم عضوية VIP</td>
                        <td class="text-left c-red" style="font-weight:600">-{{ number_format($data['totalVipDiscount'], 2) }} ر.س</td>
                    </tr>
                    @endif
                    @if($data['totalPointsDiscount'] > 0)
                    <tr>
                        <td>(-) خصم نقاط الولاء</td>
                        <td class="text-left c-red" style="font-weight:600">-{{ number_format($data['totalPointsDiscount'], 2) }} ر.س</td>
                    </tr>
                    @endif
                    <tr style="border-top:1px solid #e5e7eb">
                        <td>(+) رسوم الشحن</td>
                        <td class="text-left" style="font-weight:600">{{ number_format($data['totalShipping'], 2) }} ر.س</td>
                    </tr>
                    <tr>
                        <td>ضريبة القيمة المضافة (مستخرجة)</td>
                        <td class="text-left" style="font-weight:600">{{ number_format($data['totalTax'], 2) }} ر.س</td>
                    </tr>
                    <tr class="rpt-fin-row-total">
                        <td style="font-size:16px" class="c-dark">صافي الإيرادات المحصلة</td>
                        <td class="text-left c-green" style="font-size:22px">{{ number_format($data['totalRevenue'], 2) }} ر.س</td>
                    </tr>
                </table>
                @if($data['allOrdersTotal'] != $data['totalRevenue'])
                <div style="margin-top:8px;padding-top:8px;border-top:1px dashed #e5e7eb;font-size:11px;color:#9ca3af">
                    إجمالي جميع الطلبات (مدفوعة وغير مدفوعة): {{ number_format($data['allOrdersTotal'], 2) }} ر.س
                </div>
                @endif
            </div>

            {{-- Discounts Breakdown --}}
            <div class="rpt-card">
                <div class="rpt-card-title">🏷️ تفاصيل الخصومات</div>

                @if($data['totalAllDiscounts'] == 0)
                    <p style="text-align:center;color:#9ca3af;padding:30px 0;font-size:14px">لا توجد خصومات في هذا اليوم</p>
                @else
                    {{-- Coupons --}}
                    @if(count($data['couponUsage']) > 0)
                    <div class="rpt-discount-section">
                        <div class="rpt-discount-header">
                            <span class="rpt-dot" style="background:#ea580c"></span>
                            <span>كوبونات الخصم</span>
                        </div>
                        <table class="rpt-table" style="font-size:12px">
                            <thead>
                                <tr>
                                    <th>الكوبون</th>
                                    <th>النوع</th>
                                    <th class="text-center">مرات الاستخدام</th>
                                    <th class="text-left">إجمالي الخصم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['couponUsage'] as $coupon)
                                <tr>
                                    <td class="font-mono c-orange" style="font-weight:700">{{ $coupon['code'] }}</td>
                                    <td class="c-gray">{{ $coupon['type'] === 'percentage' ? $coupon['value'] . '%' : number_format($coupon['value'], 2) . ' ر.س' }}</td>
                                    <td class="text-center">{{ $coupon['times_used'] }}×</td>
                                    <td class="text-left c-red" style="font-weight:600">-{{ number_format($coupon['total_discount'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="font-weight:700">إجمالي خصم الكوبونات</td>
                                    <td class="text-left c-red" style="font-weight:700">-{{ number_format($data['totalDiscount'], 2) }} ر.س</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                    {{-- VIP --}}
                    @if(count($data['vipUsage']) > 0)
                    <div class="rpt-discount-section">
                        <div class="rpt-discount-header">
                            <span class="rpt-dot" style="background:#7c3aed"></span>
                            <span>خصم عضوية VIP</span>
                        </div>
                        <table class="rpt-table" style="font-size:12px">
                            <thead>
                                <tr>
                                    <th>الفئة</th>
                                    <th class="text-center">عدد الطلبات</th>
                                    <th class="text-left">إجمالي الخصم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['vipUsage'] as $vip)
                                <tr>
                                    <td class="c-purple" style="font-weight:600">{{ $vip['label'] }}</td>
                                    <td class="text-center">{{ $vip['count'] }}</td>
                                    <td class="text-left c-red" style="font-weight:600">-{{ number_format($vip['total_discount'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="font-weight:700">إجمالي خصم VIP</td>
                                    <td class="text-left c-red" style="font-weight:700">-{{ number_format($data['totalVipDiscount'], 2) }} ر.س</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                    {{-- Points --}}
                    @if($data['pointsUsage']['count'] > 0)
                    <div class="rpt-discount-section">
                        <div class="rpt-discount-header">
                            <span class="rpt-dot" style="background:#d97706"></span>
                            <span>خصم نقاط الولاء</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:13px;color:#6b7280">{{ $data['pointsUsage']['count'] }} طلب استخدم نقاط الولاء</span>
                            <span class="c-red" style="font-weight:700">-{{ number_format($data['pointsUsage']['total_discount'], 2) }} ر.س</span>
                        </div>
                    </div>
                    @endif

                    {{-- Discounts Total --}}
                    <div class="rpt-total-bar">
                        <span style="font-weight:700;font-size:15px" class="c-dark">إجمالي جميع الخصومات</span>
                        <span class="c-red" style="font-weight:800;font-size:20px">-{{ number_format($data['totalAllDiscounts'], 2) }} ر.س</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- SECTION 3: Payment, Status, Delivery --}}
        <div class="rpt-grid-3">
            {{-- Payment Gateways --}}
            <div class="rpt-card">
                <div class="rpt-card-title">💳 طرق الدفع (المحصلة)</div>
                @if(count($data['gatewayBreakdown']) > 0)
                    @foreach($data['gatewayBreakdown'] as $gw)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f3f4f6">
                        <div>
                            <span style="font-size:13px;font-weight:500">{{ $gw['label'] }}</span>
                            <span style="font-size:11px;color:#9ca3af;margin-right:4px">({{ $gw['count'] }} طلب)</span>
                        </div>
                        <span class="c-green" style="font-weight:700">{{ number_format($gw['total'], 2) }}</span>
                    </div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;font-weight:700">
                        <span class="c-dark">المجموع</span>
                        <span class="c-green">{{ number_format($data['totalRevenue'], 2) }} ر.س</span>
                    </div>
                @else
                    <p style="text-align:center;color:#9ca3af;padding:20px 0;font-size:14px">لا توجد مدفوعات</p>
                @endif
            </div>

            {{-- Order Status --}}
            <div class="rpt-card">
                <div class="rpt-card-title">📊 حالات الطلبات</div>
                @foreach($data['statusCounts'] as $item)
                <div class="rpt-status-row">
                    <span style="display:flex;align-items:center;gap:8px;font-size:13px">
                        <span class="rpt-dot" style="background:{{ match($item['color']) { 'warning' => '#eab308', 'info' => '#3b82f6', 'primary' => '#6366f1', 'success' => '#22c55e', 'danger' => '#ef4444', default => '#6b7280' } }}"></span>
                        {{ $item['label'] }}
                    </span>
                    <span style="font-size:13px">
                        <strong>{{ $item['count'] }}</strong>
                        <span class="c-gray">({{ number_format($item['total'], 2) }})</span>
                    </span>
                </div>
                @endforeach

                <div style="margin-top:14px;padding-top:10px;border-top:1px solid #e5e7eb">
                    <div style="font-weight:600;font-size:13px;margin-bottom:8px" class="c-dark">حالات الدفع</div>
                    @foreach($data['paymentStatusCounts'] as $ps)
                    <div class="rpt-status-row" style="font-size:12px">
                        <span class="c-gray">{{ $ps['label'] }}</span>
                        <span><strong>{{ $ps['count'] }}</strong> <span class="c-gray">({{ number_format($ps['total'], 2) }})</span></span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Delivery --}}
            <div class="rpt-card">
                <div class="rpt-card-title">🚚 التوصيل</div>
                <div class="rpt-delivery-box">
                    <div>
                        <div style="font-size:13px;font-weight:500">🚚 توصيل منزلي</div>
                        <div style="font-size:11px;color:#9ca3af">{{ $data['homeDeliveryCount'] }} طلب</div>
                    </div>
                    <span style="font-weight:700" class="c-dark">{{ number_format($data['homeDeliveryTotal'], 2) }} ر.س</span>
                </div>
                <div class="rpt-delivery-box">
                    <div>
                        <div style="font-size:13px;font-weight:500">🏪 استلام من الفرع</div>
                        <div style="font-size:11px;color:#9ca3af">{{ $data['storePickupCount'] }} طلب</div>
                    </div>
                    <span style="font-weight:700" class="c-dark">{{ number_format($data['storePickupTotal'], 2) }} ر.س</span>
                </div>

                @if($data['cancelledCount'] > 0)
                <div class="rpt-cancel-box">
                    <div>
                        <div style="font-size:13px;font-weight:500;color:#991b1b">طلبات ملغية</div>
                        <div style="font-size:11px;color:#dc2626">{{ $data['cancelledCount'] }} طلب</div>
                    </div>
                    <span class="c-red" style="font-weight:700">{{ number_format($data['cancelledTotal'], 2) }} ر.س</span>
                </div>
                @endif

                <div style="margin-top:14px;padding-top:10px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;font-size:13px">
                    <span class="c-gray">رسوم الشحن المحصلة</span>
                    <span style="font-weight:700" class="c-dark">{{ number_format($data['totalShipping'], 2) }} ر.س</span>
                </div>
            </div>
        </div>

        {{-- SECTION 4: Orders Detail Table --}}
        <div class="rpt-card" style="padding:0;overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb">
                <div class="rpt-card-title" style="border:none;margin:0;padding:0">📋 تفاصيل الطلبات ({{ $data['totalOrders'] }} طلب)</div>
            </div>

            @if($data['orders']->count() > 0)
            <div style="overflow-x:auto">
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الطلب</th>
                            <th>الوقت</th>
                            <th>العميل</th>
                            <th>الحالة</th>
                            <th>الدفع</th>
                            <th>طريقة الدفع</th>
                            <th class="text-left">المجموع</th>
                            <th class="text-left">خصم كوبون</th>
                            <th class="text-left">خصم VIP</th>
                            <th class="text-left">خصم نقاط</th>
                            <th class="text-left">الشحن</th>
                            <th class="text-left">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['orders'] as $index => $order)
                        @php
                            $statusBadge = match($order->status) {
                                'pending' => 'badge-yellow',
                                'confirmed' => 'badge-blue',
                                'processing' => 'badge-indigo',
                                'shipped', 'in_progress' => 'badge-cyan',
                                'delivered', 'completed' => 'badge-green',
                                'cancelled' => 'badge-red',
                                default => 'badge-gray',
                            };
                            $payBadge = match($order->payment_status) {
                                'paid' => 'badge-green',
                                'awaiting_review' => 'badge-yellow',
                                'pending', 'processing' => 'badge-blue',
                                'failed', 'cancelled', 'expired' => 'badge-red',
                                'refunded', 'partially_refunded' => 'badge-purple',
                                default => 'badge-gray',
                            };
                            $gatewayLabel = '-';
                            if($order->currentPaymentTransaction) {
                                $gatewayLabel = match($order->currentPaymentTransaction->gateway) {
                                    'cash' => 'كاش',
                                    'bank_transfer' => 'تحويل',
                                    'tamara' => 'تمارا',
                                    'tabby' => 'تابي',
                                    'amwal' => 'أموال',
                                    default => $order->currentPaymentTransaction->gateway,
                                };
                            }
                        @endphp
                        <tr class="{{ $order->status === 'cancelled' ? 'rpt-cancelled' : '' }}">
                            <td class="c-gray font-mono" style="font-size:11px">{{ $index + 1 }}</td>
                            <td class="font-mono" style="font-weight:700;font-size:11px">{{ $order->order_number }}</td>
                            <td class="c-gray" style="font-size:11px">{{ $order->created_at->format('h:i A') }}</td>
                            <td>
                                <div style="font-weight:500;font-size:12px" class="c-dark">{{ $order->user?->name ?? 'زائر' }}</div>
                                @if($order->user?->phone)
                                <div style="font-size:11px;color:#9ca3af" dir="ltr">{{ $order->user->phone }}</div>
                                @endif
                            </td>
                            <td><span class="rpt-badge {{ $statusBadge }}">{{ $order->getStatusDisplayName() }}</span></td>
                            <td><span class="rpt-badge {{ $payBadge }}">{{ $order->getPaymentStatusDisplayName() }}</span></td>
                            <td style="font-size:12px" class="c-gray">{{ $gatewayLabel }}</td>
                            <td class="text-left" style="font-size:12px">{{ number_format($order->subtotal, 2) }}</td>
                            <td class="text-left" style="font-size:12px;{{ $order->discount > 0 ? 'color:#dc2626;font-weight:600' : 'color:#d1d5db' }}">
                                @if($order->discount > 0)
                                    -{{ number_format($order->discount, 2) }}
                                    @if($order->discountCode)
                                    <div style="font-size:10px;color:#ea580c" class="font-mono">{{ $order->discountCode->code }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-left" style="font-size:12px;{{ $order->vip_discount > 0 ? 'color:#dc2626;font-weight:600' : 'color:#d1d5db' }}">
                                @if($order->vip_discount > 0)
                                    -{{ number_format($order->vip_discount, 2) }}
                                    @if($order->vip_tier_label)
                                    <div style="font-size:10px;color:#7c3aed">{{ $order->vip_tier_label }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-left" style="font-size:12px;{{ $order->points_discount > 0 ? 'color:#dc2626;font-weight:600' : 'color:#d1d5db' }}">
                                {{ $order->points_discount > 0 ? '-' . number_format($order->points_discount, 2) : '-' }}
                            </td>
                            <td class="text-left c-gray" style="font-size:12px">{{ $order->shipping > 0 ? number_format($order->shipping, 2) : '-' }}</td>
                            <td class="text-left" style="font-weight:700;{{ $order->payment_status === 'paid' ? 'color:#059669' : '' }}">{{ number_format($order->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" style="font-weight:700" class="c-dark">الإجمالي</td>
                            <td class="text-left c-dark">{{ number_format($data['orders']->sum('subtotal'), 2) }}</td>
                            <td class="text-left c-red">{{ $data['orders']->sum('discount') > 0 ? '-' . number_format($data['orders']->sum('discount'), 2) : '-' }}</td>
                            <td class="text-left c-red">{{ $data['orders']->sum('vip_discount') > 0 ? '-' . number_format($data['orders']->sum('vip_discount'), 2) : '-' }}</td>
                            <td class="text-left c-red">{{ $data['orders']->sum('points_discount') > 0 ? '-' . number_format($data['orders']->sum('points_discount'), 2) : '-' }}</td>
                            <td class="text-left c-gray">{{ number_format($data['orders']->sum('shipping'), 2) }}</td>
                            <td class="text-left c-green" style="font-size:15px">{{ number_format($data['orders']->sum('total'), 2) }} ر.س</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
                <div class="rpt-empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p style="font-size:16px">لا توجد طلبات في هذا اليوم</p>
                </div>
            @endif
        </div>

        {{-- SECTION 5: Order Products Detail --}}
        @if($data['orders']->count() > 0)
        <div class="rpt-card" style="padding:0;overflow:hidden">
            <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb">
                <div class="rpt-card-title" style="border:none;margin:0;padding:0">📦 تفاصيل المنتجات لكل طلب</div>
            </div>

            @foreach($data['orders'] as $index => $order)
            <div class="{{ $order->status === 'cancelled' ? 'rpt-cancelled' : '' }}" style="border-bottom:1px solid #f3f4f6">
                {{-- Order header --}}
                <div class="rpt-order-header">
                    <span class="c-gray font-mono" style="font-size:12px">{{ $index + 1 }}</span>
                    <span class="font-mono c-dark" style="font-weight:700;font-size:13px">{{ $order->order_number }}</span>
                    <span class="c-gray" style="font-size:12px">{{ $order->created_at->format('h:i A') }}</span>
                    <span style="font-size:12px;color:#6b7280">{{ $order->user?->name ?? 'زائر' }}</span>
                    <span style="margin-right:auto;font-weight:700;{{ $order->payment_status === 'paid' ? 'color:#059669' : 'color:#6b7280' }}">{{ number_format($order->total, 2) }} ر.س</span>
                </div>

                {{-- Discount details --}}
                @if($order->discount > 0 || $order->vip_discount > 0 || $order->points_discount > 0)
                <div class="rpt-order-discounts">
                    @if($order->discount > 0)
                    <span class="c-red">كوبون{{ $order->discountCode ? ' (' . $order->discountCode->code . ')' : '' }}: -{{ number_format($order->discount, 2) }} ر.س</span>
                    @endif
                    @if($order->vip_discount > 0)
                    <span class="c-purple">VIP{{ $order->vip_tier_label ? ' (' . $order->vip_tier_label . ')' : '' }}: -{{ number_format($order->vip_discount, 2) }} ر.س</span>
                    @endif
                    @if($order->points_discount > 0)
                    <span class="c-amber">نقاط ولاء: -{{ number_format($order->points_discount, 2) }} ر.س</span>
                    @endif
                </div>
                @endif

                {{-- Products table --}}
                @if($order->items->count() > 0)
                <div style="padding:8px 16px 14px">
                    <table class="rpt-table" style="font-size:12px">
                        <thead>
                            <tr>
                                <th style="width:30px">#</th>
                                <th>المنتج</th>
                                <th>الخيار</th>
                                <th class="text-center">السعر</th>
                                <th class="text-center">الكمية</th>
                                <th class="text-left">المجموع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $itemIdx => $item)
                            <tr>
                                <td class="c-gray">{{ $itemIdx + 1 }}</td>
                                <td style="font-weight:500" class="c-dark">{{ $item->product?->name ?? 'منتج محذوف' }}</td>
                                <td class="c-gray">
                                    @if($item->productOption)
                                        {{ $item->productOption->value ?? '' }}
                                        @if($item->productOption->sku)
                                            <span style="color:#9ca3af">({{ $item->productOption->sku }})</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">{{ number_format($item->price, 2) }}</td>
                                <td class="text-center" style="font-weight:600">{{ $item->quantity }}</td>
                                <td class="text-left" style="font-weight:600">{{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-filament-panels::page>
