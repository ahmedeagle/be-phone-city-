<?php

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OrderStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Workflow counts — matching sidebar tabs
        $awaitingProcessing = Order::where(function ($q) {
            $q->where(function ($q2) {
                $q2->where('status', Order::STATUS_CONFIRMED)
                   ->where('payment_status', Order::PAYMENT_STATUS_PAID);
            })->orWhere('payment_status', Order::PAYMENT_STATUS_AWAITING_REVIEW);
        })->count();

        $readyToShip = Order::where('status', Order::STATUS_PROCESSING)
            ->where('delivery_method', Order::DELIVERY_HOME)
            ->whereNull('oto_order_id')
            ->where(fn ($q) => $q->whereNull('tracking_number')->orWhere('tracking_number', ''))
            ->count();

        $inShipping = Order::whereIn('status', [Order::STATUS_SHIPPED, Order::STATUS_IN_PROGRESS])->count();

        $storePickup = Order::where('delivery_method', Order::DELIVERY_STORE_PICKUP)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED])
            ->count();

        $failedDelivery = Order::whereIn('tracking_status', [
            'failed', 'cancelled', 'returned', 'return_to_sender', 'delivery_failed', 'attempted_delivery',
        ])->count();

        $completedOrders = Order::whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->count();

        // Revenue
        $todayRevenue = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $todayOrders = Order::whereDate('created_at', today())->count();

        $totalOrders = Order::count();

        return [
            // --- Row 1: What needs action NOW ---
            Stat::make('⏳ بانتظار المعالجة', $awaitingProcessing)
                ->description('تحتاج مراجعة وتجهيز')
                ->descriptionIcon('heroicon-m-clock')
                ->color($awaitingProcessing > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('awaiting-processing')),

            Stat::make('📦 جاهزة للشحن', $readyToShip)
                ->description('بانتظار إنشاء شحنة')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color($readyToShip > 0 ? 'info' : 'gray')
                ->url(OrderResource::getUrl('ready-to-ship')),

            Stat::make('🏪 استلام من الفرع', $storePickup)
                ->description('بانتظار حضور العميل')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color($storePickup > 0 ? 'info' : 'gray')
                ->url(OrderResource::getUrl('store-pickup')),

            // --- Row 2: Shipping + Overview ---
            Stat::make('🚚 قيد الشحن', $inShipping)
                ->description('شحنات في الطريق')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info')
                ->url(OrderResource::getUrl('shipped')),

            Stat::make('⚠️ فشل التوصيل', $failedDelivery)
                ->description('شحنات مرتجعة أو فاشلة')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($failedDelivery > 0 ? 'danger' : 'gray')
                ->url(OrderResource::getUrl('failed-delivery')),

            Stat::make('✅ تم التوصيل/مكتمل', $completedOrders)
                ->description('طلبات ناجحة')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->url(OrderResource::getUrl('delivered')),

            // --- Row 3: Today + Totals ---
            Stat::make('طلبات اليوم', $todayOrders)
                ->description('طلبات جديدة اليوم')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('إيرادات اليوم', number_format($todayRevenue, 2) . ' ر.س')
                ->description('طلبات مدفوعة اليوم')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('إجمالي الطلبات', $totalOrders)
                ->description('جميع الطلبات')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),
        ];
    }
}

