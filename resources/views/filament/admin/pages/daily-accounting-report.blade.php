<x-filament-panels::page>
    <div class="space-y-6" id="report-content">
        {{-- Date Picker & Print --}}
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div class="max-w-xs">
                {{ $this->form }}
            </div>
            <x-filament::button icon="heroicon-o-printer" color="gray" onclick="window.print()">
                طباعة التقرير
            </x-filament::button>
        </div>

        @php $data = $this->getReportData(); @endphp

        {{-- Print Header --}}
        <div class="hidden print:block text-center mb-4 border-b-2 border-gray-900 pb-4">
            <h1 class="text-2xl font-bold">التقرير المحاسبي اليومي</h1>
            <p class="text-lg mt-1">{{ $data['date']->format('Y-m-d') }} — {{ $data['date']->translatedFormat('l j F Y') }}</p>
        </div>

        {{-- ============ SECTION 1: Summary Stats ============ --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 print:grid-cols-5">
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-4 text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $data['totalOrders'] }}</div>
                <div class="text-xs text-gray-500 mt-1">إجمالي الطلبات</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-4 text-center">
                <div class="text-2xl font-bold text-success-600">{{ $data['paidOrdersCount'] }}</div>
                <div class="text-xs text-gray-500 mt-1">طلبات مدفوعة</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-4 text-center">
                <div class="text-2xl font-bold text-success-600">{{ number_format($data['totalRevenue'], 2) }}</div>
                <div class="text-xs text-gray-500 mt-1">إيرادات محصلة (ر.س)</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-4 text-center">
                <div class="text-2xl font-bold text-info-600">{{ $data['itemsSold'] }}</div>
                <div class="text-xs text-gray-500 mt-1">منتجات مباعة</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-4 text-center">
                <div class="text-2xl font-bold {{ $data['cancelledCount'] > 0 ? 'text-danger-600' : 'text-gray-400' }}">{{ $data['cancelledCount'] }}</div>
                <div class="text-xs text-gray-500 mt-1">طلبات ملغية</div>
            </div>
        </div>

        {{-- ============ SECTION 2: Financial + Breakdown ============ --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 print:grid-cols-3">
            {{-- Financial Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-5 md:col-span-1">
                <h3 class="font-bold text-gray-900 dark:text-white mb-3 text-base border-b pb-2">💰 الملخص المالي</h3>
                <table class="w-full text-sm">
                    <tr><td class="py-1.5 text-gray-600 dark:text-gray-400">إجمالي المبيعات</td><td class="py-1.5 text-left font-semibold">{{ number_format($data['totalSubtotal'], 2) }}</td></tr>
                    @if($data['totalDiscount'] > 0)
                    <tr><td class="py-1.5 text-gray-600 dark:text-gray-400">خصم كوبونات</td><td class="py-1.5 text-left text-red-600">-{{ number_format($data['totalDiscount'], 2) }}</td></tr>
                    @endif
                    @if($data['totalVipDiscount'] > 0)
                    <tr><td class="py-1.5 text-gray-600 dark:text-gray-400">خصم VIP</td><td class="py-1.5 text-left text-red-600">-{{ number_format($data['totalVipDiscount'], 2) }}</td></tr>
                    @endif
                    @if($data['totalPointsDiscount'] > 0)
                    <tr><td class="py-1.5 text-gray-600 dark:text-gray-400">خصم نقاط</td><td class="py-1.5 text-left text-red-600">-{{ number_format($data['totalPointsDiscount'], 2) }}</td></tr>
                    @endif
                    <tr><td class="py-1.5 text-gray-600 dark:text-gray-400">رسوم شحن</td><td class="py-1.5 text-left">{{ number_format($data['totalShipping'], 2) }}</td></tr>
                    <tr><td class="py-1.5 text-gray-600 dark:text-gray-400">ضريبة</td><td class="py-1.5 text-left">{{ number_format($data['totalTax'], 2) }}</td></tr>
                    <tr class="border-t-2 border-gray-900 dark:border-white">
                        <td class="py-2 font-bold text-gray-900 dark:text-white">صافي الإيرادات</td>
                        <td class="py-2 text-left font-bold text-green-600 text-lg">{{ number_format($data['totalRevenue'], 2) }} ر.س</td>
                    </tr>
                </table>
            </div>

            {{-- Payment Gateway --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-3 text-base border-b pb-2">💳 طرق الدفع</h3>
                @if(count($data['gatewayBreakdown']) > 0)
                    @foreach($data['gatewayBreakdown'] as $gw)
                    <div class="flex justify-between py-1.5 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ $gw['label'] }}</span>
                        <span><span class="font-bold">{{ $gw['count'] }}</span> طلب — <span class="font-semibold text-green-600">{{ number_format($gw['total'], 2) }}</span></span>
                    </div>
                    @endforeach
                @else
                    <p class="text-gray-400 text-sm">لا توجد مدفوعات</p>
                @endif

                <div class="mt-4 pt-3 border-t">
                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">حالات الطلبات</h4>
                    @foreach($data['statusCounts'] as $item)
                    <div class="flex justify-between py-1 text-sm">
                        <span class="flex items-center gap-1.5">
                            <span @class([
                                'inline-block w-2 h-2 rounded-full',
                                'bg-yellow-500' => $item['color'] === 'warning',
                                'bg-blue-500' => $item['color'] === 'info',
                                'bg-indigo-500' => $item['color'] === 'primary',
                                'bg-green-500' => $item['color'] === 'success',
                                'bg-red-500' => $item['color'] === 'danger',
                                'bg-gray-500' => $item['color'] === 'gray',
                            ])></span>
                            {{ $item['label'] }}
                        </span>
                        <span class="font-bold">{{ $item['count'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Delivery & Payment Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-3 text-base border-b pb-2">🚚 التوصيل والدفع</h3>
                <div class="flex justify-between py-1.5 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">توصيل منزلي</span>
                    <span class="font-bold">{{ $data['homeDelivery'] }}</span>
                </div>
                <div class="flex justify-between py-1.5 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">استلام من الفرع</span>
                    <span class="font-bold">{{ $data['storePickup'] }}</span>
                </div>
                @if($data['cancelledCount'] > 0)
                <div class="flex justify-between py-1.5 text-sm text-red-600 border-t mt-2 pt-2">
                    <span>ملغية</span>
                    <span>{{ $data['cancelledCount'] }} — {{ number_format($data['cancelledTotal'], 2) }} ر.س</span>
                </div>
                @endif

                <div class="mt-4 pt-3 border-t">
                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">حالات الدفع</h4>
                    @foreach($data['paymentStatusCounts'] as $ps)
                    <div class="flex justify-between py-1 text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ $ps['label'] }}</span>
                        <span><span class="font-bold">{{ $ps['count'] }}</span> — {{ number_format($ps['total'], 2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ============ SECTION 3: Detailed Orders ============ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border">
            <div class="p-4 border-b flex items-center justify-between">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">📋 تفاصيل الطلبات ({{ $data['totalOrders'] }} طلب)</h3>
            </div>

            @if($data['orders']->count() > 0)
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
                            'cash' => 'الدفع عند الاستلام',
                            'bank_transfer' => 'تحويل بنكي',
                            'tamara' => 'تمارا',
                            'tabby' => 'تابي',
                            'amwal' => 'أموال',
                            default => $order->currentPaymentTransaction->gateway,
                        };
                    }
                @endphp

                <div class="border-b last:border-b-0 {{ $order->status === 'cancelled' ? 'opacity-60' : '' }}">
                    {{-- Order Header --}}
                    <div class="p-4 flex flex-wrap items-center gap-3 bg-gray-50/50 dark:bg-gray-900/30">
                        <span class="text-gray-400 font-mono text-sm w-8">{{ $index + 1 }}</span>
                        <span class="font-mono font-bold text-sm">{{ $order->order_number }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">{{ $order->getStatusDisplayName() }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $payColor }}">{{ $order->getPaymentStatusDisplayName() }}</span>
                        <span class="text-xs text-gray-500">{{ $gatewayLabel }}</span>
                        <span class="text-xs text-gray-400 mr-auto">{{ $order->created_at->format('h:i A') }}</span>
                        <span class="font-bold text-lg {{ $order->payment_status === 'paid' ? 'text-green-600' : 'text-gray-700 dark:text-gray-300' }}">{{ number_format($order->total, 2) }} ر.س</span>
                    </div>

                    {{-- Order Details --}}
                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Left: Customer & delivery info --}}
                        <div class="space-y-1.5 text-sm">
                            <div class="flex gap-2">
                                <span class="text-gray-500 w-20 shrink-0">العميل:</span>
                                <span class="font-medium">{{ $order->user?->name ?? 'زائر' }}</span>
                                @if($order->user?->phone)
                                    <span class="text-gray-400">{{ $order->user->phone }}</span>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <span class="text-gray-500 w-20 shrink-0">التوصيل:</span>
                                <span>{{ $order->delivery_method === 'store_pickup' ? '🏪 استلام من الفرع' : '🚚 توصيل منزلي' }}</span>
                                @if($order->shippingCompany)
                                    <span class="text-gray-400">— {{ $order->shippingCompany->name_ar ?? $order->shippingCompany->name_en }}</span>
                                @endif
                            </div>
                            @if($order->branch)
                            <div class="flex gap-2">
                                <span class="text-gray-500 w-20 shrink-0">الفرع:</span>
                                <span>{{ $order->branch->name_ar ?? $order->branch->name_en ?? '' }}</span>
                            </div>
                            @endif
                            @if($order->location)
                            <div class="flex gap-2">
                                <span class="text-gray-500 w-20 shrink-0">العنوان:</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ $order->location->city ?? '' }} {{ $order->location->district ?? '' }}</span>
                            </div>
                            @endif
                            @if($order->tracking_number)
                            <div class="flex gap-2">
                                <span class="text-gray-500 w-20 shrink-0">رقم التتبع:</span>
                                <span class="font-mono text-xs">{{ $order->tracking_number }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- Right: Financial breakdown --}}
                        <div class="text-sm">
                            <table class="w-full">
                                <tr><td class="py-0.5 text-gray-500">المجموع الفرعي</td><td class="py-0.5 text-left">{{ number_format($order->subtotal, 2) }}</td></tr>
                                @if($order->discount > 0)
                                <tr><td class="py-0.5 text-gray-500">خصم كوبون</td><td class="py-0.5 text-left text-red-600">-{{ number_format($order->discount, 2) }}</td></tr>
                                @endif
                                @if($order->vip_discount > 0)
                                <tr><td class="py-0.5 text-gray-500">خصم VIP</td><td class="py-0.5 text-left text-red-600">-{{ number_format($order->vip_discount, 2) }}</td></tr>
                                @endif
                                @if($order->points_discount > 0)
                                <tr><td class="py-0.5 text-gray-500">خصم نقاط</td><td class="py-0.5 text-left text-red-600">-{{ number_format($order->points_discount, 2) }}</td></tr>
                                @endif
                                @if($order->shipping > 0)
                                <tr><td class="py-0.5 text-gray-500">شحن</td><td class="py-0.5 text-left">{{ number_format($order->shipping, 2) }}</td></tr>
                                @endif
                                @if($order->tax > 0)
                                <tr><td class="py-0.5 text-gray-500">ضريبة</td><td class="py-0.5 text-left">{{ number_format($order->tax, 2) }}</td></tr>
                                @endif
                                <tr class="border-t font-bold">
                                    <td class="py-1 text-gray-900 dark:text-white">الإجمالي</td>
                                    <td class="py-1 text-left text-green-600">{{ number_format($order->total, 2) }} ر.س</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Products Table --}}
                    @if($order->items->count() > 0)
                    <div class="px-4 pb-4">
                        <table class="w-full text-xs border rounded overflow-hidden">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="px-2 py-1.5 text-right text-gray-600 dark:text-gray-300">#</th>
                                    <th class="px-2 py-1.5 text-right text-gray-600 dark:text-gray-300">المنتج</th>
                                    <th class="px-2 py-1.5 text-right text-gray-600 dark:text-gray-300">الخيار</th>
                                    <th class="px-2 py-1.5 text-right text-gray-600 dark:text-gray-300">السعر</th>
                                    <th class="px-2 py-1.5 text-right text-gray-600 dark:text-gray-300">الكمية</th>
                                    <th class="px-2 py-1.5 text-right text-gray-600 dark:text-gray-300">المجموع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $itemIdx => $item)
                                <tr class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="px-2 py-1.5 text-gray-400">{{ $itemIdx + 1 }}</td>
                                    <td class="px-2 py-1.5 font-medium">{{ $item->product?->name ?? 'منتج محذوف' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500">
                                        @if($item->productOption)
                                            {{ $item->productOption->value ?? '' }}
                                            @if($item->productOption->sku)
                                                <span class="text-gray-400">({{ $item->productOption->sku }})</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-2 py-1.5">{{ number_format($item->price, 2) }}</td>
                                    <td class="px-2 py-1.5 text-center">{{ $item->quantity }}</td>
                                    <td class="px-2 py-1.5 font-semibold">{{ number_format($item->total, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endforeach
            @else
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-lg">لا توجد طلبات في هذا اليوم</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            .fi-sidebar, .fi-topbar, .fi-header, nav, header,
            .fi-sidebar-nav, [class*="fi-sidebar"],
            button, .filament-page-header-actions,
            .fi-page-header-actions, .fi-breadcrumbs { display: none !important; }
            .fi-main { margin: 0 !important; padding: 0 !important; }
            .fi-main-ctn { max-width: 100% !important; }
            body { font-size: 10px !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .print\:block { display: block !important; }
            .print\:grid-cols-5 { grid-template-columns: repeat(5, 1fr) !important; }
            .print\:grid-cols-3 { grid-template-columns: repeat(3, 1fr) !important; }
            .rounded-xl { border-radius: 4px !important; }
            * { box-shadow: none !important; }
            @page { margin: 10mm; size: A4 landscape; }
        }
    </style>
</x-filament-panels::page>
