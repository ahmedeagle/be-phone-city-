<?php

namespace App\Filament\Admin\Widgets;

use App\Models\OrderItem;
use App\Models\Product;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getHeading(): string
    {
        return 'أفضل المنتجات مبيعاً';
    }

    public static function getSort(): int
    {
        return 4;
    }

    public function getTableRecordsPerPage(): int | string | null
    {
        return 5;
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $cancelledStatus = \App\Models\Order::STATUS_CANCELLED;

        // Use subquery to calculate total_sold to avoid GROUP BY issues with MySQL strict mode
        $totalSoldSubquery = DB::table('order_items')
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(CASE WHEN orders.status IS NULL OR orders.status != ? THEN order_items.quantity ELSE 0 END), 0) as total_sold', [$cancelledStatus])
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->groupBy('product_id');

        return Product::query()
            ->leftJoinSub($totalSoldSubquery, 'sales', function ($join) {
                $join->on('products.id', '=', 'sales.product_id');
            })
            ->select('products.*', DB::raw('COALESCE(sales.total_sold, 0) as total_sold'))
            ->orderByDesc('sales.total_sold')
            ->orderByDesc('products.created_at')
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            ImageColumn::make('main_image')
                ->label('الصورة')
                ->circular()
                ->size(40),
            TextColumn::make('name')
                ->label('اسم المنتج')
                ->searchable()
                ->limit(30)
                ->weight('bold'),
            TextColumn::make('total_sold')
                ->label('الكمية المباعة')
                ->numeric()
                ->sortable()
                ->badge()
                ->color('success'),
            TextColumn::make('quantity')
                ->label('المخزون')
                ->numeric()
                ->sortable()
                ->color(fn ($record) => $record->quantity <= 0 ? 'danger' : ($record->quantity <= 10 ? 'warning' : 'success')),
            TextColumn::make('main_price')
                ->label('السعر')
                ->money('SAR')
                ->sortable(),
        ];
    }

    protected function getTableHeading(): ?string
    {
        return 'أفضل المنتجات مبيعاً (أعلى 5)';
    }
}

