<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChartWidget extends ChartWidget
{
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getHeading(): string
    {
        return 'توزيع حالة الطلبات';
    }

    public static function getSort(): int
    {
        return 3;
    }

    protected function getData(): array
    {
        $pending = Order::where('status', Order::STATUS_PENDING)->count();
        $confirmed = Order::where('status', Order::STATUS_CONFIRMED)->count();
        $processing = Order::where('status', Order::STATUS_PROCESSING)->count();
        $shipped = Order::where('status', Order::STATUS_SHIPPED)->count();
        $inProgress = Order::where('status', Order::STATUS_IN_PROGRESS)->count();
        $delivered = Order::where('status', Order::STATUS_DELIVERED)->count();
        $completed = Order::where('status', Order::STATUS_COMPLETED)->count();
        $cancelled = Order::where('status', Order::STATUS_CANCELLED)->count();

        return [
            'datasets' => [
                [
                    'data' => [$pending, $confirmed, $processing, $shipped, $inProgress, $delivered, $completed, $cancelled],
                    'backgroundColor' => [
                        'rgb(107, 114, 128)', // gray - pending
                        'rgb(59, 130, 246)',  // info - confirmed
                        'rgb(99, 102, 241)',  // primary - processing
                        'rgb(251, 191, 36)',  // warning - shipped
                        'rgb(251, 191, 36)',  // warning - in progress
                        'rgb(34, 197, 94)',   // success - delivered
                        'rgb(34, 197, 94)',   // success - completed
                        'rgb(239, 68, 68)',   // danger - cancelled
                    ],
                    'borderColor' => [
                        'rgb(107, 114, 128)',
                        'rgb(59, 130, 246)',
                        'rgb(99, 102, 241)',
                        'rgb(251, 191, 36)',
                        'rgb(251, 191, 36)',
                        'rgb(34, 197, 94)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['بانتظار الدفع', 'تم التأكيد', 'جاري التجهيز', 'تم الشحن', 'جاري التوصيل', 'تم التوصيل', 'مكتملة', 'ملغاة'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.label + ": " + context.parsed + " طلب"; }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}

