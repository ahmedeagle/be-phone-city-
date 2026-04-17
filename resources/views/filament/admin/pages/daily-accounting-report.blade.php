<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date Picker --}}
        <div class="max-w-xs">
            {{ $this->form }}
        </div>

        @php $data = $this->getReportData(); @endphp

        {{-- Print Button --}}
        <div class="flex justify-end">
            <x-filament::button
                icon="heroicon-o-printer"
                color="gray"
                onclick="window.print()"
            >
                طباعة التقرير
            </x-filament::button>
        </div>

        {{-- Report Header (visible in print) --}}
        <div class="hidden print:block text-center mb-6">
            <h1 class="text-2xl font-bold">التقرير المحاسبي اليومي</h1>
            <p class="text-lg">{{ $data['date']->format('Y-m-d') }} - {{ $data['date']->translatedFormat('l j F Y') }}</p>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 print:grid-cols-4">
            {{-- Total Orders --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $data['totalOrders'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">إجمالي الطلبات</div>
                </div>
            </x-filament::section>

            {{-- Total Revenue --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-success-600">{{ number_format($data['totalRevenue'], 2) }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">الإيرادات (ر.س)</div>
                </div>
            </x-filament::section>

            {{-- Items Sold --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-info-600">{{ $data['itemsSold'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">المنتجات المباعة</div>
                </div>
            </x-filament::section>

            {{-- Awaiting Review --}}
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $data['awaitingReview'] > 0 ? 'text-warning-600' : 'text-gray-400' }}">{{ $data['awaitingReview'] }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">بانتظار المراجعة</div>
                </div>
            </x-filament::section>
        </div>

        {{-- Financial Summary --}}
        <x-filament::section>
            <x-slot name="heading">الملخص المالي</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="py-2 font-medium text-gray-700 dark:text-gray-300">إجمالي المبيعات (قبل الخصم)</td>
                            <td class="py-2 text-left font-semibold">{{ number_format($data['totalSubtotal'], 2) }} ر.س</td>
                        </tr>
                        @if($data['totalDiscount'] > 0)
                        <tr>
                            <td class="py-2 font-medium text-gray-700 dark:text-gray-300">خصومات الكوبونات</td>
                            <td class="py-2 text-left text-danger-600 font-semibold">- {{ number_format($data['totalDiscount'], 2) }} ر.س</td>
                        </tr>
                        @endif
                        @if($data['totalVipDiscount'] > 0)
                        <tr>
                            <td class="py-2 font-medium text-gray-700 dark:text-gray-300">خصومات VIP</td>
                            <td class="py-2 text-left text-danger-600 font-semibold">- {{ number_format($data['totalVipDiscount'], 2) }} ر.س</td>
                        </tr>
                        @endif
                        @if($data['totalPointsDiscount'] > 0)
                        <tr>
                            <td class="py-2 font-medium text-gray-700 dark:text-gray-300">خصومات النقاط</td>
                            <td class="py-2 text-left text-danger-600 font-semibold">- {{ number_format($data['totalPointsDiscount'], 2) }} ر.س</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="py-2 font-medium text-gray-700 dark:text-gray-300">رسوم الشحن</td>
                            <td class="py-2 text-left font-semibold">{{ number_format($data['totalShipping'], 2) }} ر.س</td>
                        </tr>
                        <tr>
                            <td class="py-2 font-medium text-gray-700 dark:text-gray-300">الضريبة</td>
                            <td class="py-2 text-left font-semibold">{{ number_format($data['totalTax'], 2) }} ر.س</td>
                        </tr>
                        <tr class="border-t-2 border-gray-900 dark:border-gray-100">
                            <td class="py-3 font-bold text-gray-900 dark:text-white text-lg">صافي الإيرادات</td>
                            <td class="py-3 text-left font-bold text-success-600 text-lg">{{ number_format($data['totalRevenue'], 2) }} ر.س</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:grid-cols-2">
            {{-- Order Status Breakdown --}}
            <x-filament::section>
                <x-slot name="heading">حالة الطلبات</x-slot>
                @if(count($data['statusCounts']) > 0)
                    <div class="space-y-2">
                        @foreach($data['statusCounts'] as $item)
                            <div class="flex items-center justify-between py-1.5">
                                <span class="flex items-center gap-2">
                                    <span @class([
                                        'inline-block w-3 h-3 rounded-full',
                                        'bg-warning-500' => $item['color'] === 'warning',
                                        'bg-info-500' => $item['color'] === 'info',
                                        'bg-primary-500' => $item['color'] === 'primary',
                                        'bg-success-500' => $item['color'] === 'success',
                                        'bg-danger-500' => $item['color'] === 'danger',
                                        'bg-gray-500' => $item['color'] === 'gray',
                                    ])></span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                                </span>
                                <span class="text-sm font-bold">{{ $item['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">لا توجد طلبات في هذا اليوم</p>
                @endif
            </x-filament::section>

            {{-- Payment Gateway Breakdown --}}
            <x-filament::section>
                <x-slot name="heading">طرق الدفع (المدفوع فقط)</x-slot>
                @if(count($data['gatewayBreakdown']) > 0)
                    <div class="space-y-2">
                        @foreach($data['gatewayBreakdown'] as $gw)
                            <div class="flex items-center justify-between py-1.5">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $gw['label'] }}</span>
                                <span class="text-sm">
                                    <span class="font-bold">{{ $gw['count'] }}</span>
                                    <span class="text-gray-500 mx-1">|</span>
                                    <span class="font-semibold text-success-600">{{ number_format($gw['total'], 2) }} ر.س</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">لا توجد مدفوعات</p>
                @endif
            </x-filament::section>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:grid-cols-2">
            {{-- Payment Status --}}
            <x-filament::section>
                <x-slot name="heading">حالة الدفع</x-slot>
                @if(count($data['paymentStatusCounts']) > 0)
                    <div class="space-y-2">
                        @foreach($data['paymentStatusCounts'] as $ps)
                            <div class="flex items-center justify-between py-1.5">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $ps['label'] }}</span>
                                <span class="text-sm">
                                    <span class="font-bold">{{ $ps['count'] }}</span>
                                    <span class="text-gray-500 mx-1">|</span>
                                    <span class="font-semibold">{{ number_format($ps['total'], 2) }} ر.س</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">لا توجد طلبات</p>
                @endif
            </x-filament::section>

            {{-- Delivery Method --}}
            <x-filament::section>
                <x-slot name="heading">طريقة التوصيل</x-slot>
                <div class="space-y-2">
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">🚚 توصيل منزلي</span>
                        <span class="text-sm font-bold">{{ $data['homeDelivery'] }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">🏪 استلام من الفرع</span>
                        <span class="text-sm font-bold">{{ $data['storePickup'] }}</span>
                    </div>
                    @if($data['cancelledCount'] > 0)
                    <div class="flex items-center justify-between py-1.5 border-t border-gray-200 dark:border-gray-700 mt-2 pt-2">
                        <span class="text-sm font-medium text-danger-600">❌ طلبات ملغية</span>
                        <span class="text-sm">
                            <span class="font-bold text-danger-600">{{ $data['cancelledCount'] }}</span>
                            <span class="text-gray-500 mx-1">|</span>
                            <span class="font-semibold text-danger-600">{{ number_format($data['cancelledTotal'], 2) }} ر.س</span>
                        </span>
                    </div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        {{-- Orders Table --}}
        <x-filament::section>
            <x-slot name="heading">تفاصيل الطلبات ({{ $data['totalOrders'] }})</x-slot>
            @if($data['orders']->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">#</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">رقم الطلب</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">العميل</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">الحالة</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">حالة الدفع</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">طريقة الدفع</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">المجموع</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">الخصم</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">الشحن</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">الإجمالي</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border-b">الوقت</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($data['orders'] as $index => $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $order->status === 'cancelled' ? 'opacity-50' : '' }}">
                                <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $order->order_number }}</td>
                                <td class="px-3 py-2">{{ $order->user?->name ?? 'زائر' }}</td>
                                <td class="px-3 py-2">
                                    @php
                                        $statusColor = match($order->status) {
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-blue-100 text-blue-800',
                                            'processing' => 'bg-indigo-100 text-indigo-800',
                                            'shipped' => 'bg-cyan-100 text-cyan-800',
                                            'in_progress' => 'bg-sky-100 text-sky-800',
                                            'delivered' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-emerald-100 text-emerald-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                        {{ $order->getStatusDisplayName() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    @php
                                        $payColor = match($order->payment_status) {
                                            'paid' => 'bg-green-100 text-green-800',
                                            'awaiting_review' => 'bg-yellow-100 text-yellow-800',
                                            'pending', 'processing' => 'bg-blue-100 text-blue-800',
                                            'failed', 'cancelled', 'expired' => 'bg-red-100 text-red-800',
                                            'refunded', 'partially_refunded' => 'bg-purple-100 text-purple-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $payColor }}">
                                        {{ $order->getPaymentStatusDisplayName() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    @if($order->currentPaymentTransaction)
                                        {{ match($order->currentPaymentTransaction->gateway) {
                                            'cash' => 'كاش',
                                            'bank_transfer' => 'تحويل',
                                            'tamara' => 'تمارا',
                                            'tabby' => 'تابي',
                                            'amwal' => 'أموال',
                                            default => $order->currentPaymentTransaction->gateway,
                                        } }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs">{{ number_format($order->subtotal, 2) }}</td>
                                <td class="px-3 py-2 text-xs {{ ($order->discount + $order->vip_discount + $order->points_discount) > 0 ? 'text-danger-600' : '' }}">
                                    @php $totalDisc = $order->discount + $order->vip_discount + $order->points_discount; @endphp
                                    {{ $totalDisc > 0 ? '-' . number_format($totalDisc, 2) : '-' }}
                                </td>
                                <td class="px-3 py-2 text-xs">{{ number_format($order->shipping, 2) }}</td>
                                <td class="px-3 py-2 font-semibold">{{ number_format($order->total, 2) }}</td>
                                <td class="px-3 py-2 text-xs text-gray-500">{{ $order->created_at->format('H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-gray-800 font-bold">
                                <td colspan="6" class="px-3 py-2 text-left">الإجمالي (المدفوع فقط)</td>
                                <td class="px-3 py-2">{{ number_format($data['totalSubtotal'], 2) }}</td>
                                <td class="px-3 py-2 text-danger-600">
                                    @php $allDiscounts = $data['totalDiscount'] + $data['totalVipDiscount'] + $data['totalPointsDiscount']; @endphp
                                    {{ $allDiscounts > 0 ? '-' . number_format($allDiscounts, 2) : '-' }}
                                </td>
                                <td class="px-3 py-2">{{ number_format($data['totalShipping'], 2) }}</td>
                                <td class="px-3 py-2 text-success-600">{{ number_format($data['totalRevenue'], 2) }}</td>
                                <td class="px-3 py-2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                    <p>لا توجد طلبات في هذا اليوم</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            /* Hide sidebar, topbar, and non-essential elements */
            .fi-sidebar, .fi-topbar, .fi-header, nav, header,
            .fi-sidebar-nav, [class*="fi-sidebar"],
            button, .filament-page-header-actions,
            .fi-page-header-actions { display: none !important; }

            .fi-main { margin: 0 !important; padding: 0 !important; }
            .fi-main-ctn { max-width: 100% !important; }

            body { font-size: 11px !important; }

            .print\:block { display: block !important; }
            .print\:grid-cols-4 { grid-template-columns: repeat(4, 1fr) !important; }
            .print\:grid-cols-2 { grid-template-columns: repeat(2, 1fr) !important; }

            /* Remove shadows and borders for clean print */
            .fi-section { box-shadow: none !important; border: 1px solid #ddd !important; }

            /* Ensure table fits on page */
            table { font-size: 10px !important; }
        }
    </style>
</x-filament-panels::page>
