<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DashboardStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // === Row 1: Revenue (3 cards) ===
        $todayRevenue = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $thisMonthRevenue = DB::table('orders')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $totalRevenue = DB::table('orders')
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        // === Row 2: Action-needed orders (3 cards) ===
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

        $storePickup = Order::where('delivery_method', Order::DELIVERY_STORE_PICKUP)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED])
            ->count();

        // === Row 3: Shipping status (3 cards) ===
        $inShipping = Order::whereIn('status', [Order::STATUS_SHIPPED, Order::STATUS_IN_PROGRESS])->count();

        $failedDelivery = Order::whereIn('tracking_status', [
            'failed', 'cancelled', 'returned', 'return_to_sender', 'delivery_failed', 'attempted_delivery',
        ])->count();

        $todayOrders = Order::whereDate('created_at', today())->count();

        // === Row 4: Products & Customers (3 cards) ===
        $outOfStockProducts = Product::where('quantity', '<=', 0)->count();
        $lowStockProducts = Product::whereBetween('quantity', [1, 10])->count();
        $totalCustomers = User::count();

        return [
            // --- Row 1: Revenue ---
            Stat::make('إيرادات اليوم', number_format($todayRevenue, 2) . ' ر.س')
                ->description('طلبات مدفوعة اليوم')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('إيرادات الشهر', number_format($thisMonthRevenue, 2) . ' ر.س')
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success')
                ->chart($this->getRevenueChartData()),

            Stat::make('إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ر.س')
                ->description('جميع الطلبات المدفوعة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            // --- Row 2: Action needed ---
            Stat::make('⏳ بانتظار المعالجة', $awaitingProcessing)
                ->description('تحتاج مراجعة وتجهيز')
                ->descriptionIcon('heroicon-m-clock')
                ->color($awaitingProcessing > 0 ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('awaiting-processing')),

            Stat::make('📦 جاهزة للشحن', $readyToShip)
                ->description('بانتظار إنشاء شحنة OTO')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color($readyToShip > 0 ? 'info' : 'gray')
                ->url(OrderResource::getUrl('ready-to-ship')),

            Stat::make('🏪 استلام من الفرع', $storePickup)
                ->description('بانتظار حضور العميل')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color($storePickup > 0 ? 'info' : 'gray')
                ->url(OrderResource::getUrl('store-pickup')),

            // --- Row 3: Shipping & Activity ---
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

            Stat::make('طلبات اليوم', $todayOrders)
                ->description('طلبات جديدة اليوم')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary')
                ->chart($this->getOrdersChartData()),

            // --- Row 4: Stock & Customers ---
            Stat::make('🔴 منتجات نفذت', $outOfStockProducts)
                ->description('غير متوفرة في المخزن')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($outOfStockProducts > 0 ? 'danger' : 'gray'),

            Stat::make('🟡 كمية محدودة', $lowStockProducts)
                ->description('أقل من 10 قطع')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockProducts > 0 ? 'warning' : 'gray'),

            Stat::make('👥 العملاء', $totalCustomers)
                ->description('إجمالي العملاء المسجلين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }

    protected function getRevenueChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = DB::table('orders')
                ->whereDate('created_at', $date->toDateString())
                ->where('payment_status', Order::PAYMENT_STATUS_PAID)
                ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
                ->value('total_revenue') ?? 0;
            $data[] = (float) $revenue;
        }
        return $data;
    }

    protected function getOrdersChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $orders = Order::whereDate('created_at', $date->toDateString())->count();
            $data[] = $orders;
        }
        return $data;
    }
}

