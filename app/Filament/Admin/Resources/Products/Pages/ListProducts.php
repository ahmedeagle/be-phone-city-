<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Admin\Resources\Products\Widgets\ProductStatsWidget;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة منتج')
                ->visible(fn () => auth()->user()->can('products.create')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductStatsWidget::class,
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->withSum([
                'orderItems as sold_quantity_sum' => function ($query) {
                    $query->whereHas('order', function ($q) {
                        $q->where('status', Order::STATUS_COMPLETED);
                    });
                }
            ], 'quantity')
            ->withSum([
                'orderItems as total_revenue_sum' => function ($query) {
                    $query->whereHas('order', function ($q) {
                        $q->where('status', Order::STATUS_COMPLETED);
                    });
                }
            ], 'total');
    }
}
