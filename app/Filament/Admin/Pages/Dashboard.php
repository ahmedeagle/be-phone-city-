<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\DashboardStatsWidget;
use App\Filament\Admin\Widgets\OrderStatusChartWidget;
use App\Filament\Admin\Widgets\RecentOrdersWidget;
use App\Filament\Admin\Widgets\RevenueChartWidget;
use App\Filament\Admin\Widgets\TopProductsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            RevenueChartWidget::class,
            // OrderStatusChartWidget::class,
            TopProductsWidget::class,
            RecentOrdersWidget::class,
        ];
    }
}

