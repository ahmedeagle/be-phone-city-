<?php

namespace App\Filament\Admin\Resources\Products\Widgets;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $inStockProducts = Product::where('quantity', '>', 10)->count();
        $outOfStockProducts = Product::where('quantity', '<=', 0)->count();
        $limitedStockProducts = Product::whereBetween('quantity', [1, 10])->count();
        $productsWithOffers = Product::whereHas('offers')->count();

        // $averagePrice = Product::avg('main_price');
        // $totalInventoryValue = Product::selectRaw('SUM(main_price * quantity) as total')->first()->total ?? 0;

        return [
            Stat::make('إجمالي المنتجات', $totalProducts)
                ->description('جميع المنتجات في النظام')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('المنتجات المتوفرة', $inStockProducts)
                ->description('منتجات في المخزون')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('منتجات غير متوفرة', $outOfStockProducts)
                ->description('منتجات نفذت من المخزون')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('كمية محدودة', $limitedStockProducts)
                ->description('منتجات بكمية محدودة(أقل من 10 قطع)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),


            Stat::make('منتجات بعروض', $productsWithOffers)
                ->description('منتجات عليها عروض نشطة')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success'),

            // Stat::make('متوسط السعر', number_format($averagePrice, 2) . ' ر.س')
            //     ->description('متوسط سعر المنتجات')
            //     ->descriptionIcon('heroicon-m-currency-dollar')
            //     ->color('info'),

            // Stat::make('قيمة المخزون', number_format($totalInventoryValue, 2) . ' ر.س')
            //     ->description('القيمة الإجمالية للمخزون')
            //     ->descriptionIcon('heroicon-m-banknotes')
            //     ->color('success'),
        ];
    }
}
