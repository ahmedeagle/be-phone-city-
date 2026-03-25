<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ListOrdersNoShipment extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithFormActions;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.pages.list-records';

    protected static ?string $slug = 'orders/no-shipment';

    protected static ?string $title = 'طلبات بدون شحنة';

    protected static ?string $navigationLabel = 'بدون شحنة';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'بدون شحنة';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Order::query()
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->where('delivery_method', Order::DELIVERY_HOME)
            ->whereNotIn('status', [Order::STATUS_PENDING, Order::STATUS_CANCELLED, Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
            ->where(function ($query) {
                $query->whereNull('tracking_number')
                      ->orWhere('tracking_number', '');
            })
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return OrdersTable::configure($table)
            ->query(Order::query()
                ->where('payment_status', Order::PAYMENT_STATUS_PAID)
                ->where('delivery_method', Order::DELIVERY_HOME)
                ->whereNotIn('status', [Order::STATUS_PENDING, Order::STATUS_CANCELLED, Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
                ->where(function ($query) {
                    $query->whereNull('tracking_number')
                          ->orWhere('tracking_number', '');
                })
            );
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrderResource::getUrl('index') => 'الطلبات',
            '#' => 'بدون شحنة',
        ];
    }
}
