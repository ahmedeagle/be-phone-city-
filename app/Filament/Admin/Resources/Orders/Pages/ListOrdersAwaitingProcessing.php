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

class ListOrdersAwaitingProcessing extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithFormActions;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.pages.list-records';

    protected static ?string $slug = 'orders/awaiting-processing';

    protected static ?string $title = 'طلبات بانتظار المعالجة';

    protected static ?string $navigationLabel = 'بانتظار المعالجة';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pause-circle';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'بانتظار المعالجة';
    }

    public function table(Table $table): Table
    {
        return OrdersTable::configure($table)
            ->query(Order::query()
                ->where(function ($query) {
                    // Confirmed orders awaiting admin processing (auto-confirm off)
                    $query->where('status', Order::STATUS_CONFIRMED)
                          ->where('payment_status', Order::PAYMENT_STATUS_PAID);
                })
                ->orWhere(function ($query) {
                    // Bank transfer awaiting admin review
                    $query->where('payment_status', Order::PAYMENT_STATUS_AWAITING_REVIEW);
                })
            );
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrderResource::getUrl('index') => 'الطلبات',
            '#' => 'بانتظار المعالجة',
        ];
    }
}
