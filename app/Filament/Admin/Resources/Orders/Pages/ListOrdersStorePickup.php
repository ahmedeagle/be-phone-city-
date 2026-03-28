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
use UnitEnum;

class ListOrdersStorePickup extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithFormActions;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.pages.list-records';

    protected static ?string $slug = 'orders/store-pickup';

    protected static ?string $title = 'طلبات استلام من الفرع';

    protected static ?string $navigationLabel = 'استلام من الفرع';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return 'استلام من الفرع';
    }

    public function table(Table $table): Table
    {
        return OrdersTable::configure($table)
            ->query(Order::query()
                ->where('delivery_method', Order::DELIVERY_STORE_PICKUP)
                ->whereNotIn('status', [
                    Order::STATUS_CANCELLED,
                ])
            );
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrderResource::getUrl('index') => 'الطلبات',
            '#' => 'استلام من الفرع',
        ];
    }
}
