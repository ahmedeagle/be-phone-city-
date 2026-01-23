<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'الإيرادات (آخر 30 يوم)';
    }

    public static function getSort(): int
    {
        return 2;
    }

    protected function getData(): array
    {
        $labels = [];
        $revenueData = [];
        $ordersData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->translatedFormat('d M');

            // Revenue
            $revenue = DB::table('orders')
                ->whereDate('created_at', $date->toDateString())
                ->where('status', '!=', Order::STATUS_CANCELLED)
                ->selectRaw('COALESCE(SUM(total), 0) as total_revenue')
                ->value('total_revenue') ?? 0;
            $revenueData[] = (float) $revenue;

            // Orders count
            $orders = Order::whereDate('created_at', $date->toDateString())->count();
            $ordersData[] = $orders;
        }

        return [
            'datasets' => [
                [
                    'label' => 'الإيرادات',
                    'data' => $revenueData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'عدد الطلبات',
                    'data' => $ordersData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => false,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'الإيرادات (ر.س)',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'عدد الطلبات',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}

