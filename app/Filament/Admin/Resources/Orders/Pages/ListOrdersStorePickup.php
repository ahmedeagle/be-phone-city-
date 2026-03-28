<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Notifications\OrderNotification;
use BackedEnum;
use Filament\Actions\Action;
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
            )
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->visible(fn () => auth()->user()->can('orders.show')),
                // ✅ تم الاستلام - Customer picked up
                Action::make('mark_picked_up')
                    ->label('✅ تم الاستلام')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد استلام العميل')
                    ->modalDescription('هل تم استلام الطلب من قبل العميل؟ سيتم تحديث الحالة إلى "مكتمل" وإشعار العميل.')
                    ->modalSubmitActionLabel('نعم، تم الاستلام')
                    ->visible(fn (Order $record) => auth()->user()->can('orders.update')
                        && !in_array($record->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED]))
                    ->action(function (Order $record) {
                        $record->update(['status' => Order::STATUS_COMPLETED]);

                        // Notify customer
                        if ($record->user) {
                            $record->user->notify(new OrderNotification($record, 'completed'));
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('تم تحديث الطلب')
                            ->body("الطلب #{$record->order_number} — تم الاستلام من الفرع ✅")
                            ->success()
                            ->send();
                    }),
                // 📞 تذكير العميل - Remind customer to pick up
                Action::make('notify_ready')
                    ->label('📞 جاهز للاستلام')
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('إشعار العميل')
                    ->modalDescription('سيتم إرسال إشعار للعميل بأن طلبه جاهز للاستلام من الفرع.')
                    ->modalSubmitActionLabel('إرسال الإشعار')
                    ->visible(fn (Order $record) => auth()->user()->can('orders.update')
                        && !in_array($record->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED]))
                    ->action(function (Order $record) {
                        // Update status to processing if still pending/confirmed
                        if (in_array($record->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])) {
                            $record->update(['status' => Order::STATUS_PROCESSING]);
                        }

                        // Notify customer
                        if ($record->user) {
                            $record->user->notify(new OrderNotification($record, 'ready_for_pickup'));
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('تم إرسال الإشعار')
                            ->body("تم إشعار العميل بأن الطلب #{$record->order_number} جاهز للاستلام")
                            ->success()
                            ->send();
                    }),
                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Order $record) => route('admin.orders.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => auth()->user()->can('orders.show')),
            ]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrderResource::getUrl('index') => 'الطلبات',
            '#' => 'استلام من الفرع',
        ];
    }
}
