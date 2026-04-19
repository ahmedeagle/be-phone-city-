<x-filament-panels::page>
    {{-- Print styles --}}
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #report-content, #report-content * { visibility: visible !important; }
            #report-content { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
            .print-break { page-break-before: always; }
            .fi-sidebar, .fi-topbar, nav, header, footer { display: none !important; }
            @page { margin: 10mm; size: A4; }
        }
    </style>

    <div class="space-y-6" id="report-content" dir="rtl">
        {{-- Date Picker & Actions --}}
        <div class="flex items-end justify-between gap-4 flex-wrap no-print">
            <div class="max-w-xs">
                {{ $this->form }}
            </div>
            <x-filament::button icon="heroicon-o-printer" color="gray" onclick="window.print()">
                طباعة التقرير
            </x-filament::button>
        </div>

        @php $data = $this->getReportData(); @endphp

        {{-- ===== Print Header ===== --}}
        <div class="hidden print:block text-center mb-6 border-b-2 border-gray-900 pb-4">
            <h1 class="text-2xl font-bold">التقرير المحاسبي اليومي</h1>
            <p class="text-lg mt-1">{{ $data['date']->format('Y-m-d') }} — {{ $data['date']->translatedFormat('l j F Y') }}</p>
        </div>

        {{-- ===== Alerts ===== --}}
        @if($data['awaitingReview'] > 0 || $data['pendingOrders'] > 0)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-xl p-4 no-print">
            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold mb-1">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                تنبيهات تحتاج اهتمام
            </div>
            <div class="flex gap-6 text-sm text-amber-700 dark:text-amber-400">
                @if($data['awaitingReview'] > 0)
                <span>{{ $data['awaitingReview'] }} طلب بانتظار مراجعة الدفع</span>
                @endif
                @if($data['pendingOrders'] > 0)
                <span>{{ $data['pendingOrders'] }} طلب معلق</span>
                @endif
            </div>
        </div>
        @endif

        {{-- ===== SECTION 1: Key Metrics ===== --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 print:grid-cols-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">إجمالي الطلبات</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $data['totalOrders'] }}</div>
                <div class="text-xs text-gray-400 mt-1">مدفوعة: {{ $data['paidOrdersCount'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">صافي الإيرادات المحصلة</div>
                <div class="text-3xl font-bold text-emerald-600">{{ number_format($data['totalRevenue'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">ر.س</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">إجمالي الخصومات</div>
                <div class="text-3xl font-bold text-red-500">{{ number_format($data['totalAllDiscounts'], 2) }}</div>
                <div class="text-xs text-gray-400 mt-1">ر.س</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">منتجات مباعة</div>
                <div class="text-3xl font-bold text-blue-600">{{ $data['itemsSold'] }}</div>
                <div class="text-xs text-gray-400 mt-1">قطعة</div>
            </div>
        </div>

        {{-- ===== SECTION 2: Financial Summary + Discounts ===== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 print:grid-cols-2">

            {{-- Financial Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 text-base border-b border-gray-200 dark:border-gray-700 pb-2">الملخص المالي (الطلبات المدفوعة)</h3>
                <table class="w-full text-sm">
                    <tbody>
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">إجمالي المبيعات (قبل الخصم)</td>
                            <td class="py-2 text-left font-semibold text-gray-900 dark:text-white">{{ number_format($data['totalSubtotal'], 2) }} ر.س</td>
                        </tr>
                        @if($data['totalDiscount'] > 0)
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">(-) خصم كوبونات</td>
                            <td class="py-2 text-left font-semibold text-red-600">-{{ number_format($data['totalDiscount'], 2) }} ر.س</td>
                        </tr>
                        @endif
                        @if($data['totalVipDiscount'] > 0)
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">(-) خصم عضوية VIP</td>
                            <td class="py-2 text-left font-semibold text-red-600">-{{ number_format($data['totalVipDiscount'], 2) }} ر.س</td>
                        </tr>
                        @endif
                        @if($data['totalPointsDiscount'] > 0)
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">(-) خصم نقاط الولاء</td>
                            <td class="py-2 text-left font-semibold text-red-600">-{{ number_format($data['totalPointsDiscount'], 2) }} ر.س</td>
                        </tr>
                        @endif
                        <tr class="border-t border-gray-100 dark:border-gray-700">
                            <td class="py-2 text-gray-600 dark:text-gray-400">(+) رسوم الشحن</td>
                            <td class="py-2 text-left font-semibold text-gray-900 dark:text-white">{{ number_format($data['totalShipping'], 2) }} ر.س</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">ضريبة القيمة المضافة (مستخرجة)</td>
                            <td class="py-2 text-left font-semibold text-gray-900 dark:text-white">{{ number_format($data['totalTax'], 2) }} ر.س</td>
                        </tr>
                        <tr class="border-t-2 border-gray-900 dark:border-gray-200">
                            <td class="py-3 font-bold text-gray-900 dark:text-white text-base">صافي الإيرادات المحصلة</td>
                            <td class="py-3 text-left font-bold text-emerald-600 text-xl">{{ number_format($data['totalRevenue'], 2) }} ر.س</td>
                        </tr>
                    </tbody>
                </table>
                @if($data['allOrdersTotal'] != $data['totalRevenue'])
                <div class="mt-2 pt-2 border-t border-dashed border-gray-200 dark:border-gray-700 text-xs text-gray-400">
                    إجمالي جميع الطلبات (مدفوعة وغير مدفوعة): {{ number_format($data['allOrdersTotal'], 2) }} ر.س
                </div>
                @endif
            </div>

            {{-- Discounts Breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 text-base border-b border-gray-200 dark:border-gray-700 pb-2">تفاصيل الخصومات</h3>

                @if($data['totalAllDiscounts'] == 0)
                    <p class="text-gray-400 text-sm text-center py-6">لا توجد خصومات في هذا اليوم</p>
                @else
                    {{-- Coupons --}}
                    @if(count($data['couponUsage']) > 0)
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                            كوبونات الخصم
                        </h4>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-gray-500 dark:text-gray-400">
                                        <th class="px-3 py-2 text-right font-medium">الكوبون</th>
                                        <th class="px-3 py-2 text-right font-medium">النوع</th>
                                        <th class="px-3 py-2 text-center font-medium">مرات الاستخدام</th>
                                        <th class="px-3 py-2 text-left font-medium">إجمالي الخصم</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['couponUsage'] as $coupon)
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-3 py-2 font-mono font-bold text-orange-600">{{ $coupon['code'] }}</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">
                                            {{ $coupon['type'] === 'percentage' ? $coupon['value'] . '%' : number_format($coupon['value'], 2) . ' ر.س' }}
                                        </td>
                                        <td class="px-3 py-2 text-center">{{ $coupon['times_used'] }}×</td>
                                        <td class="px-3 py-2 text-left text-red-600 font-semibold">-{{ number_format($coupon['total_discount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-gray-200 dark:border-gray-700 font-bold text-sm">
                                        <td colspan="3" class="px-3 py-2">إجمالي خصم الكوبونات</td>
                                        <td class="px-3 py-2 text-left text-red-600">-{{ number_format($data['totalDiscount'], 2) }} ر.س</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- VIP --}}
                    @if(count($data['vipUsage']) > 0)
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            خصم عضوية VIP
                        </h4>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-gray-500 dark:text-gray-400">
                                        <th class="px-3 py-2 text-right font-medium">الفئة</th>
                                        <th class="px-3 py-2 text-center font-medium">عدد الطلبات</th>
                                        <th class="px-3 py-2 text-left font-medium">إجمالي الخصم</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['vipUsage'] as $vip)
                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                        <td class="px-3 py-2 font-semibold text-purple-600">{{ $vip['label'] }}</td>
                                        <td class="px-3 py-2 text-center">{{ $vip['count'] }}</td>
                                        <td class="px-3 py-2 text-left text-red-600 font-semibold">-{{ number_format($vip['total_discount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-gray-200 dark:border-gray-700 font-bold text-sm">
                                        <td colspan="2" class="px-3 py-2">إجمالي خصم VIP</td>
                                        <td class="px-3 py-2 text-left text-red-600">-{{ number_format($data['totalVipDiscount'], 2) }} ر.س</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Points --}}
                    @if($data['pointsUsage']['count'] > 0)
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            خصم نقاط الولاء
                        </h4>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $data['pointsUsage']['count'] }} طلب استخدم نقاط الولاء</span>
                            <span class="text-red-600 font-bold">-{{ number_format($data['pointsUsage']['total_discount'], 2) }} ر.س</span>
                        </div>
                    </div>
                    @endif

                    {{-- Discounts Total --}}
                    <div class="mt-4 pt-3 border-t-2 border-gray-900 dark:border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-900 dark:text-white text-base">إجمالي جميع الخصومات</span>
                            <span class="font-bold text-red-600 text-xl">-{{ number_format($data['totalAllDiscounts'], 2) }} ر.س</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== SECTION 3: Payment & Delivery ===== --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 print:grid-cols-3">

            {{-- Payment Gateways --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 text-base border-b border-gray-200 dark:border-gray-700 pb-2">طرق الدفع (المحصلة)</h3>
                @if(count($data['gatewayBreakdown']) > 0)
                    @foreach($data['gatewayBreakdown'] as $gw)
                    <div class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $gw['label'] }}</span>
                            <span class="text-xs text-gray-400 mr-1">({{ $gw['count'] }} طلب)</span>
                        </div>
                        <span class="font-bold text-emerald-600">{{ number_format($gw['total'], 2) }}</span>
                    </div>
                    @endforeach
                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between font-bold">
                        <span class="text-gray-900 dark:text-white">المجموع</span>
                        <span class="text-emerald-600">{{ number_format($data['totalRevenue'], 2) }} ر.س</span>
                    </div>
                @else
                    <p class="text-gray-400 text-sm text-center py-4">لا توجد مدفوعات</p>
                @endif
            </div>

            {{-- Order Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 text-base border-b border-gray-200 dark:border-gray-700 pb-2">حالات الطلبات</h3>
                @foreach($data['statusCounts'] as $item)
                <div class="flex justify-between items-center py-1.5">
                    <span class="flex items-center gap-2 text-sm">
                        <span @class([
                            'inline-block w-2.5 h-2.5 rounded-full',
                            'bg-yellow-500' => $item['color'] === 'warning',
                            'bg-blue-500' => $item['color'] === 'info',
                            'bg-indigo-500' => $item['color'] === 'primary',
                            'bg-green-500' => $item['color'] === 'success',
                            'bg-red-500' => $item['color'] === 'danger',
                            'bg-gray-500' => $item['color'] === 'gray',
                        ])></span>
                        <span class="text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                    </span>
                    <span class="text-sm">
                        <span class="font-bold text-gray-900 dark:text-white">{{ $item['count'] }}</span>
                        <span class="text-gray-400 mr-1">({{ number_format($item['total'], 2) }})</span>
                    </span>
                </div>
                @endforeach

                {{-- Payment Status --}}
                <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mt-4 mb-2 pt-3 border-t border-gray-200 dark:border-gray-700">حالات الدفع</h4>
                @foreach($data['paymentStatusCounts'] as $ps)
                <div class="flex justify-between items-center py-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">{{ $ps['label'] }}</span>
                    <span>
                        <span class="font-bold">{{ $ps['count'] }}</span>
                        <span class="text-gray-400 mr-1">({{ number_format($ps['total'], 2) }})</span>
                    </span>
                </div>
                @endforeach
            </div>

            {{-- Delivery & Cancellations --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 text-base border-b border-gray-200 dark:border-gray-700 pb-2">التوصيل</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg px-3">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🚚</span>
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">توصيل منزلي</div>
                                <div class="text-xs text-gray-400">{{ $data['homeDeliveryCount'] }} طلب</div>
                            </div>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($data['homeDeliveryTotal'], 2) }} ر.س</span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg px-3">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🏪</span>
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">استلام من الفرع</div>
                                <div class="text-xs text-gray-400">{{ $data['storePickupCount'] }} طلب</div>
                            </div>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($data['storePickupTotal'], 2) }} ر.س</span>
                    </div>
                </div>

                @if($data['cancelledCount'] > 0)
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 flex justify-between items-center">
                        <div>
                            <div class="text-sm font-medium text-red-700 dark:text-red-400">طلبات ملغية</div>
                            <div class="text-xs text-red-500">{{ $data['cancelledCount'] }} طلب</div>
                        </div>
                        <span class="font-bold text-red-600">{{ number_format($data['cancelledTotal'], 2) }} ر.س</span>
                    </div>
                </div>
                @endif

                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-600 dark:text-gray-400 flex justify-between">
                        <span>رسوم الشحن المحصلة</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($data['totalShipping'], 2) }} ر.س</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== SECTION 4: Orders Detail Table ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 print-break">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">تفاصيل الطلبات ({{ $data['totalOrders'] }} طلب)</h3>
            </div>

            @if($data['orders']->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <th class="px-4 py-3 text-right font-medium">#</th>
                            <th class="px-4 py-3 text-right font-medium">رقم الطلب</th>
                            <th class="px-4 py-3 text-right font-medium">الوقت</th>
                            <th class="px-4 py-3 text-right font-medium">العميل</th>
                            <th class="px-4 py-3 text-right font-medium">الحالة</th>
                            <th class="px-4 py-3 text-right font-medium">الدفع</th>
                            <th class="px-4 py-3 text-right font-medium">طريقة الدفع</th>
                            <th class="px-4 py-3 text-left font-medium">المجموع</th>
                            <th class="px-4 py-3 text-left font-medium">خصم كوبون</th>
                            <th class="px-4 py-3 text-left font-medium">خصم VIP</th>
                            <th class="px-4 py-3 text-left font-medium">خصم نقاط</th>
                            <th class="px-4 py-3 text-left font-medium">الشحن</th>
                            <th class="px-4 py-3 text-left font-medium">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($data['orders'] as $index => $order)
                        @php
                            $statusColor = match($order->status) {
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'processing' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                'shipped', 'in_progress' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
                                'delivered', 'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                default => 'bg-gray-100 text-gray-800',
                            };
                            $payColor = match($order->payment_status) {
                                'paid' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'awaiting_review' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'pending', 'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'failed', 'cancelled', 'expired' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'refunded', 'partially_refunded' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                default => 'bg-gray-100 text-gray-800',
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
                        <tr class="{{ $order->status === 'cancelled' ? 'opacity-50' : '' }} hover:bg-gray-50 dark:hover:bg-gray-900/30">
                            <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-mono font-bold text-xs">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->created_at->format('h:i A') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white text-xs">{{ $order->user?->name ?? 'زائر' }}</div>
                                @if($order->user?->phone)
                                <div class="text-gray-400 text-xs" dir="ltr">{{ $order->user->phone }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">{{ $order->getStatusDisplayName() }}</span></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $payColor }}">{{ $order->getPaymentStatusDisplayName() }}</span></td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $gatewayLabel }}</td>
                            <td class="px-4 py-3 text-left text-xs">{{ number_format($order->subtotal, 2) }}</td>
                            <td class="px-4 py-3 text-left text-xs {{ $order->discount > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                @if($order->discount > 0)
                                    -{{ number_format($order->discount, 2) }}
                                    @if($order->discountCode)
                                    <div class="text-[10px] text-orange-500 font-mono">{{ $order->discountCode->code }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-left text-xs {{ $order->vip_discount > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                @if($order->vip_discount > 0)
                                    -{{ number_format($order->vip_discount, 2) }}
                                    @if($order->vip_tier_label)
                                    <div class="text-[10px] text-purple-500">{{ $order->vip_tier_label }}</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-left text-xs {{ $order->points_discount > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                {{ $order->points_discount > 0 ? '-' . number_format($order->points_discount, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-left text-xs text-gray-600">{{ $order->shipping > 0 ? number_format($order->shipping, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-left font-bold {{ $order->payment_status === 'paid' ? 'text-emerald-600' : 'text-gray-700 dark:text-gray-300' }}">{{ number_format($order->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 dark:bg-gray-900/80 font-bold text-sm border-t-2 border-gray-300 dark:border-gray-600">
                            <td colspan="7" class="px-4 py-3 text-gray-900 dark:text-white">الإجمالي</td>
                            <td class="px-4 py-3 text-left text-gray-900 dark:text-white">{{ number_format($data['orders']->sum('subtotal'), 2) }}</td>
                            <td class="px-4 py-3 text-left text-red-600">{{ $data['orders']->sum('discount') > 0 ? '-' . number_format($data['orders']->sum('discount'), 2) : '-' }}</td>
                            <td class="px-4 py-3 text-left text-red-600">{{ $data['orders']->sum('vip_discount') > 0 ? '-' . number_format($data['orders']->sum('vip_discount'), 2) : '-' }}</td>
                            <td class="px-4 py-3 text-left text-red-600">{{ $data['orders']->sum('points_discount') > 0 ? '-' . number_format($data['orders']->sum('points_discount'), 2) : '-' }}</td>
                            <td class="px-4 py-3 text-left text-gray-600">{{ number_format($data['orders']->sum('shipping'), 2) }}</td>
                            <td class="px-4 py-3 text-left text-emerald-600 text-base">{{ number_format($data['orders']->sum('total'), 2) }} ر.س</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
                <div class="text-center py-16 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-lg">لا توجد طلبات في هذا اليوم</p>
                </div>
            @endif
        </div>

        {{-- ===== SECTION 5: Order Details (Products) ===== --}}
        @if($data['orders']->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 print-break">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">تفاصيل المنتجات لكل طلب</h3>
            </div>

            @foreach($data['orders'] as $index => $order)
            <div class="border-b border-gray-100 dark:border-gray-700 last:border-b-0 {{ $order->status === 'cancelled' ? 'opacity-50' : '' }}">
                {{-- Order header row --}}
                <div class="px-5 py-3 flex flex-wrap items-center gap-3 bg-gray-50/50 dark:bg-gray-900/20">
                    <span class="text-gray-400 font-mono text-xs w-6">{{ $index + 1 }}</span>
                    <span class="font-mono font-bold text-sm text-gray-900 dark:text-white">{{ $order->order_number }}</span>
                    <span class="text-xs text-gray-400">{{ $order->created_at->format('h:i A') }}</span>
                    <span class="text-xs text-gray-500">{{ $order->user?->name ?? 'زائر' }}</span>
                    <span class="mr-auto font-bold {{ $order->payment_status === 'paid' ? 'text-emerald-600' : 'text-gray-500' }}">{{ number_format($order->total, 2) }} ر.س</span>
                </div>

                {{-- Order discount details --}}
                @if($order->discount > 0 || $order->vip_discount > 0 || $order->points_discount > 0)
                <div class="px-5 py-2 flex flex-wrap gap-4 text-xs bg-red-50/50 dark:bg-red-900/10">
                    @if($order->discount > 0)
                    <span class="text-red-600">
                        كوبون{{ $order->discountCode ? ' (' . $order->discountCode->code . ')' : '' }}: -{{ number_format($order->discount, 2) }} ر.س
                    </span>
                    @endif
                    @if($order->vip_discount > 0)
                    <span class="text-purple-600">
                        VIP{{ $order->vip_tier_label ? ' (' . $order->vip_tier_label . ')' : '' }}: -{{ number_format($order->vip_discount, 2) }} ر.س
                    </span>
                    @endif
                    @if($order->points_discount > 0)
                    <span class="text-amber-600">
                        نقاط ولاء: -{{ number_format($order->points_discount, 2) }} ر.س
                    </span>
                    @endif
                </div>
                @endif

                {{-- Products --}}
                @if($order->items->count() > 0)
                <div class="px-5 pb-3">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-gray-400">
                                <th class="py-1.5 text-right font-medium w-8">#</th>
                                <th class="py-1.5 text-right font-medium">المنتج</th>
                                <th class="py-1.5 text-right font-medium">الخيار</th>
                                <th class="py-1.5 text-center font-medium">السعر</th>
                                <th class="py-1.5 text-center font-medium">الكمية</th>
                                <th class="py-1.5 text-left font-medium">المجموع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach($order->items as $itemIdx => $item)
                            <tr>
                                <td class="py-1.5 text-gray-400">{{ $itemIdx + 1 }}</td>
                                <td class="py-1.5 font-medium text-gray-900 dark:text-gray-200">{{ $item->product?->name ?? 'منتج محذوف' }}</td>
                                <td class="py-1.5 text-gray-500">
                                    @if($item->productOption)
                                        {{ $item->productOption->value ?? '' }}
                                        @if($item->productOption->sku)
                                            <span class="text-gray-400">({{ $item->productOption->sku }})</span>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-1.5 text-center">{{ number_format($item->price, 2) }}</td>
                                <td class="py-1.5 text-center font-medium">{{ $item->quantity }}</td>
                                <td class="py-1.5 text-left font-semibold">{{ number_format($item->total, 2) }}</td>
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
