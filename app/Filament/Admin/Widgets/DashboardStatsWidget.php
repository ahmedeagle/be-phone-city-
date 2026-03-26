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
        // Orders Stats
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', today())->count();
        $inProgressOrders = Order::where('status', Order::STATUS_IN_PROGRESS)->count();
        $awaitingProcessing = Order::where(function ($q) {
            $q->where(function ($q2) {
                $q2->where('status', Order::STATUS_CONFIRMED)
                   ->where('payment_status', Order::PAYMENT_STATUS_PAID);
            })->orWhere('payment_status', Order::PAYMENT_STATUS_AWAITING_REVIEW);
        })->count();
        $activeOrders = Order::whereNotIn('status', [
            Order::STATUS_PENDING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED,
        ])->count();

        // Revenue Stats
        $todayRevenue = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $totalRevenue = DB::table('orders')
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $thisMonthRevenue = DB::table('orders')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        // Products Stats
        $totalProducts = Product::count();
        $outOfStockProducts = Product::where('quantity', '<=', 0)->count();
        $lowStockProducts = Product::whereBetween('quantity', [1, 10])->count();

        // Customers Stats
        $totalCustomers = User::count();
        $newCustomersToday = User::whereDate('created_at', today())->count();
        $newCustomersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            Stat::make('إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ر.س')
                ->description('إجمالي الإيرادات الإجمالية')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getRevenueChartData()),

            Stat::make('إيرادات الشهر الحالي', number_format($thisMonthRevenue, 2) . ' ر.س')
                ->description('إجمالي إيرادات ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('إيرادات اليوم', number_format($todayRevenue, 2) . ' ر.س')
                ->description('إجمالي إيرادات اليوم')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('إجمالي الطلبات', $totalOrders)
                ->description('جميع الطلبات في النظام')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary')
                ->chart($this->getOrdersChartData()),

            Stat::make('طلبات اليوم', $todayOrders)
                ->description('عدد الطلبات اليوم')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('طلبات بانتظار المعالجة', $awaitingProcessing)
                ->description('تحتاج مراجعة وتجهيز')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(OrderResource::getUrl('awaiting-processing')),

            Stat::make('طلبات قيد التجهيز والتوصيل', $activeOrders)
                ->description('مؤكدة + تجهيز + شحن + توصيل')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info')
                ->url(OrderResource::getUrl('index')),

            Stat::make('إجمالي المنتجات', $totalProducts)
                ->description('جميع المنتجات في المتجر')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('منتجات نفذت', $outOfStockProducts)
                ->description('منتجات غير متوفرة')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('كمية محدودة', $lowStockProducts)
                ->description('منتجات بكمية أقل من 10')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('إجمالي العملاء', $totalCustomers)
                ->description('جميع العملاء المسجلين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('عملاء جدد هذا الشهر', $newCustomersThisMonth)
                ->description('عملاء مسجلين في ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            Stat::make('عملاء جدد اليوم', $newCustomersToday)
                ->description('عملاء مسجلين اليوم')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
        ];
    }

    protected function getRevenueChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = DB::table('orders')
                ->whereDate('created_at', $date->toDateString())
                ->where('status', '!=', Order::STATUS_CANCELLED)
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

