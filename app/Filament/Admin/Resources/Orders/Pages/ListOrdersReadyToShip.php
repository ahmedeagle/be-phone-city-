<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Orders\Tables\OrdersTable;
use App\Models\Branch;
use App\Models\Order;
use App\Services\Shipping\OtoShippingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class ListOrdersReadyToShip extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithFormActions;

    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.pages.list-records';

    protected static ?string $slug = 'orders/ready-to-ship';

    protected static ?string $title = 'طلبات جاهزة للشحن';

    protected static ?string $navigationLabel = 'جاهزة للشحن';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'جاهزة للشحن';
    }

    public function table(Table $table): Table
    {
        return OrdersTable::configure($table)
            ->query(Order::query()
                ->where('status', Order::STATUS_PROCESSING)
                ->where('delivery_method', Order::DELIVERY_HOME)
                ->whereNull('oto_order_id')
                ->where(function ($query) {
                    $query->whereNull('tracking_number')
                          ->orWhere('tracking_number', '');
                })
            )
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->visible(fn () => auth()->user()->can('orders.show')),
                // 🚚 إنشاء شحنة OTO — Create OTO shipment with branch selection
                Action::make('create_shipment')
                    ->label('🚚 إنشاء شحنة')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->form([
                        Select::make('branch_id')
                            ->label('الفرع / المستودع')
                            ->options(
                                Branch::query()
                                    ->where('is_active', true)
                                    ->whereNotNull('oto_warehouse_id')
                                    ->pluck('name_ar', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->helperText('اختر الفرع الذي سيتم الشحن منه'),
                        Textarea::make('notes')
                            ->label('ملاحظات للشحنة')
                            ->rows(2)
                            ->placeholder('ملاحظات إضافية (اختياري)'),
                    ])
                    ->modalHeading('إنشاء شحنة OTO')
                    ->modalDescription(fn (Order $record) => "إنشاء شحنة للطلب #{$record->order_number} — {$record->location?->first_name} {$record->location?->last_name}")
                    ->modalSubmitActionLabel('إنشاء الشحنة')
                    ->visible(fn (Order $record) => auth()->user()->can('orders.update'))
                    ->action(function (Order $record, array $data) {
                        $branch = Branch::find($data['branch_id']);

                        if (!$branch || !$branch->oto_warehouse_id) {
                            \Filament\Notifications\Notification::make()
                                ->title('خطأ')
                                ->body('الفرع المحدد ليس لديه معرّف مستودع OTO.')
                                ->danger()
                                ->send();
                            return;
                        }

                        try {
                            $shippingService = app(OtoShippingService::class);
                            $shipmentDto = $shippingService->createOrderAndShipment(
                                order: $record,
                                notes: $data['notes'] ?? null,
                                warehouseId: $branch->oto_warehouse_id,
                            );

                            $record->refresh();

                            \Filament\Notifications\Notification::make()
                                ->title('تم إنشاء الشحنة ✅')
                                ->body("الطلب #{$record->order_number} — تم إنشاء شحنة من فرع {$branch->name_ar}"
                                    . ($record->tracking_number ? "\nرقم التتبع: {$record->tracking_number}" : ''))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل إنشاء الشحنة')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
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
            '#' => 'جاهزة للشحن',
        ];
    }
}
