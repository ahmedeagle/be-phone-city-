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

class ListOrdersOtoInProgress extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithFormActions;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.pages.list-records';

    protected static ?string $slug = 'orders/oto-in-progress';

    protected static ?string $title = 'طلبات قيد التنفيذ (OTO)';

    protected static ?string $navigationLabel = 'قيد التنفيذ (OTO)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'قيد التنفيذ (OTO)';
    }

    public function table(Table $table): Table
    {
        return OrdersTable::configure($table)
            ->query(Order::query()
                ->whereNotNull('oto_order_id')
                ->whereNotIn('status', [
                    Order::STATUS_DELIVERED,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED,
                ])
            );
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrderResource::getUrl('index') => 'الطلبات',
            '#' => 'قيد التنفيذ (OTO)',
        ];
    }
}
