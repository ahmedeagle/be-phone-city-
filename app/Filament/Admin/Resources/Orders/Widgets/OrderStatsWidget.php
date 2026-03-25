<?php

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OrderStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOrders = Order::count();
        $inProgressOrders = Order::where('status', Order::STATUS_IN_PROGRESS)->count();
        $completedOrders = Order::where('status', Order::STATUS_COMPLETED)->count();
        $cancelledOrders = Order::where('status', Order::STATUS_CANCELLED)->count();

        $todayOrders = Order::whereDate('created_at', today())->count();

        // Calculate revenue
        $todayRevenue = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $totalRevenue = DB::table('orders')
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
            ->value('total_revenue') ?? 0;

        $averageOrderValue = DB::table('orders')
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->selectRaw('COALESCE(AVG(total), 0) as avg_total')
            ->value('avg_total') ?? 0;

        return [
            Stat::make('إجمالي الطلبات', $totalOrders)
                ->description('جميع الطلبات في النظام')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('طلبات جاري توصيلها', $inProgressOrders)
                ->description('طلبات قيد التجهيز والتوصيل')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('طلبات مكتملة', $completedOrders)
                ->description('طلبات تم توصيلها بنجاح')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('طلبات ملغاة', $cancelledOrders)
                ->description('طلبات تم إلغاؤها')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('طلبات اليوم', $todayOrders)
                ->description('عدد الطلبات اليوم')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('إيرادات اليوم', number_format($todayRevenue, 2) . ' ر.س')
                ->description('إجمالي الإيرادات اليوم')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ر.س')
                ->description('إجمالي الإيرادات الإجمالية')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('متوسط قيمة الطلب', number_format($averageOrderValue ?? 0, 2) . ' ر.س')
                ->description('متوسط قيمة الطلب')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}

