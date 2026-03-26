<?php

namespace App\Filament\Admin\Resources\Orders;

use App\Filament\Admin\Resources\Orders\Pages\EditOrder;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Orders\Schemas\OrderForm;
use App\Filament\Admin\Resources\Orders\Tables\OrdersTable;
use App\Filament\Admin\Resources\Orders\Widgets\OrderStatsWidget;
use App\Models\Order;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static ?string $navigationLabel = 'الطلبات';

    protected static ?string $pluralLabel = 'الطلبات';

    protected static ?string $label = 'طلب';

    protected static ?string $recordTitleAttribute = 'order_number';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'المبيعات والمدفوعات';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', \App\Models\Order::STATUS_PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();

        if (isset($items[0])) {
            $items[0]->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.orders.index'));
        }

        if (auth()->user()->can('orders.show')) {
            $items[] = \Filament\Navigation\NavigationItem::make('بانتظار المعالجة')
                ->group('المبيعات والمدفوعات')
                ->icon('heroicon-o-pause-circle')
                ->sort(2)
                ->url(static::getUrl('awaiting-processing'))
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.orders.awaiting-processing'))
                ->badge(fn () => Order::where(function ($q) {
                    $q->where('status', Order::STATUS_CONFIRMED)
                      ->where('payment_status', Order::PAYMENT_STATUS_PAID);
                })->orWhere('payment_status', Order::PAYMENT_STATUS_AWAITING_REVIEW)->count() ?: null);

            $items[] = \Filament\Navigation\NavigationItem::make('جاهزة للشحن')
                ->group('المبيعات والمدفوعات')
                ->icon('heroicon-o-clock')
                ->sort(3)
                ->url(static::getUrl('ready-to-ship'))
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.orders.ready-to-ship'))
                ->badge(fn () => Order::where('status', Order::STATUS_PROCESSING)
                    ->where('delivery_method', Order::DELIVERY_HOME)
                    ->whereNull('oto_order_id')
                    ->where(fn ($q) => $q->whereNull('tracking_number')->orWhere('tracking_number', ''))
                    ->count() ?: null);

            $items[] = \Filament\Navigation\NavigationItem::make('قيد التنفيذ (OTO)')
                ->group('المبيعات والمدفوعات')
                ->icon('heroicon-o-arrow-path')
                ->sort(4)
                ->url(static::getUrl('oto-in-progress'))
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.orders.oto-in-progress'))
                ->badge(fn () => Order::whereNotNull('oto_order_id')
                    ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])
                    ->count() ?: null);

            $items[] = \Filament\Navigation\NavigationItem::make('قيد الشحن')
                ->group('المبيعات والمدفوعات')
                ->icon('heroicon-o-truck')
                ->sort(5)
                ->url(static::getUrl('shipped'))
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.orders.shipped'))
                ->badge(fn () => Order::whereIn('status', [Order::STATUS_SHIPPED, Order::STATUS_IN_PROGRESS])
                    ->count() ?: null);

            $items[] = \Filament\Navigation\NavigationItem::make('تم التوصيل')
                ->group('المبيعات والمدفوعات')
                ->icon('heroicon-o-check-circle')
                ->sort(6)
                ->url(static::getUrl('delivered'))
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.orders.delivered'))
                ->badge(fn () => Order::whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                    ->count() ?: null);
        }

        return $items;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('orders.show');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('orders.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('orders.update');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('orders.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('معلومات الطلب')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('order_number')
                            ->label('رقم الطلب')
                            ->copyable(),
                        \Filament\Infolists\Components\TextEntry::make('user.name')
                            ->label('العميل'),
                        \Filament\Infolists\Components\TextEntry::make('user.email')
                            ->label('البريد الإلكتروني')
                            ->copyable(),
                        \Filament\Infolists\Components\TextEntry::make('user.phone')
                            ->label('رقم الهاتف')
                            ->copyable()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                Order::STATUS_PENDING => 'gray',
                                Order::STATUS_CONFIRMED => 'info',
                                Order::STATUS_PROCESSING => 'primary',
                                Order::STATUS_SHIPPED => 'warning',
                                Order::STATUS_IN_PROGRESS => 'warning',
                                Order::STATUS_DELIVERED => 'success',
                                Order::STATUS_COMPLETED => 'success',
                                Order::STATUS_CANCELLED => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                Order::STATUS_PENDING => 'بانتظار الدفع',
                                Order::STATUS_CONFIRMED => 'تم تأكيد الطلب',
                                Order::STATUS_PROCESSING => 'جاري تجهيز الطلب',
                                Order::STATUS_SHIPPED => 'تم الشحن',
                                Order::STATUS_IN_PROGRESS => 'جاري التوصيل',
                                Order::STATUS_DELIVERED => 'تم التوصيل',
                                Order::STATUS_COMPLETED => 'مكتمل',
                                Order::STATUS_CANCELLED => 'ملغي',
                                default => $state,
                            }),
                        \Filament\Infolists\Components\TextEntry::make('delivery_method')
                            ->label('طريقة التوصيل')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                Order::DELIVERY_HOME => 'info',
                                Order::DELIVERY_STORE_PICKUP => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                Order::DELIVERY_HOME => 'توصيل منزلي',
                                Order::DELIVERY_STORE_PICKUP => 'استلام من المتجر',
                                default => $state,
                            }),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الطلب')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),
                \Filament\Schemas\Components\Section::make('معلومات التوصيل')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('location.first_name')
                            ->label('الاسم الأول')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('location.last_name')
                            ->label('اسم العائلة')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('location.country')
                            ->label('الدولة')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('location.city.name_ar')
                            ->label('المدينة')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('location.street_address')
                            ->label('العنوان')
                            ->wrap()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('location.phone')
                            ->label('رقم الهاتف')
                            ->copyable()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('location.email')
                            ->label('البريد الإلكتروني')
                            ->copyable()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('location.label')
                            ->label('التصنيف')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->location),
                \Filament\Schemas\Components\Section::make('معلومات الفرع (استلام من المعرض)')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('branch.name_ar')
                            ->label('اسم الفرع')
                            ->size('lg')
                            ->weight('bold'),
                        \Filament\Infolists\Components\TextEntry::make('branch.address_ar')
                            ->label('العنوان')
                            ->wrap(),
                        \Filament\Infolists\Components\TextEntry::make('branch.city_ar')
                            ->label('المدينة')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('branch.phone')
                            ->label('رقم الهاتف')
                            ->copyable()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('branch.phone2')
                            ->label('رقم الهاتف الثاني')
                            ->copyable()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('branch.working_hours_ar')
                            ->label('أوقات العمل')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('branch.google_maps_url')
                            ->label('رابط الخريطة')
                            ->url(fn ($record) => $record->branch?->google_maps_url, shouldOpenInNewTab: true)
                            ->color('primary')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->icon('heroicon-o-building-storefront')
                    ->iconColor('success')
                    ->visible(fn ($record) => $record->branch_id && $record->branch),
                \Filament\Schemas\Components\Section::make('معلومات الدفع')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('paymentMethod.name')
                            ->label('طريقة الدفع'),
                        \Filament\Infolists\Components\TextEntry::make('payment_status')
                            ->label('حالة الدفع')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'awaiting_review' => 'info',
                                'processing' => 'primary',
                                'failed', 'cancelled' => 'danger',
                                'expired' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'بانتظار الدفع',
                                'awaiting_review' => 'بانتظار المراجعة',
                                'processing' => 'قيد المعالجة',
                                'paid' => 'مدفوع',
                                'failed' => 'فشل',
                                'cancelled' => 'ملغي',
                                'expired' => 'منتهي',
                                'refunded' => 'مسترجع',
                                'partially_refunded' => 'مسترجع جزئياً',
                                default => $state,
                            }),
                        \Filament\Infolists\Components\TextEntry::make('currentPaymentTransaction.gateway')
                            ->label('بوابة الدفع')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'cash' => 'كاش',
                                'bank_transfer' => 'تحويل بنكي',
                                'tamara' => 'تمارا',
                                'tabby' => 'تابي',
                                'amwal' => 'أموال',
                                default => $state ?? '-',
                            })
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('currentPaymentTransaction.transaction_id')
                            ->label('رقم المعاملة')
                            ->copyable()
                            ->placeholder('-')
                            ->url(fn ($record) => $record->currentPaymentTransaction
                                ? route('filament.admin.resources.payment-transactions.view', ['record' => $record->currentPaymentTransaction->id])
                                : null),
                    ])
                    ->columns(2),
                \Filament\Schemas\Components\Section::make('معلومات الشحن (OTO)')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('shipping_provider')
                            ->label('مزود الشحن')
                            ->badge()
                            ->color('info')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('tracking_number')
                            ->label('رقم التتبع')
                            ->copyable()
                            ->copyMessage('تم نسخ رقم التتبع')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('tracking_url')
                            ->label('رابط التتبع')
                            ->url(fn ($record) => $record->tracking_url, shouldOpenInNewTab: true)
                            ->color('primary')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->placeholder('-')
                            ->visible(fn ($record) => !empty($record->tracking_url)),
                        \Filament\Infolists\Components\TextEntry::make('tracking_status')
                            ->label('حالة الشحنة')
                            ->badge()
                            ->color(fn ($state) => $state ? \App\Services\Shipping\Oto\OtoStatusMapper::getBadgeColor($state) : 'gray')
                            ->formatStateUsing(fn ($state) => $state ? \App\Services\Shipping\Oto\OtoStatusMapper::getStatusLabel($state) : '-')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('shipping_eta')
                            ->label('الوقت المتوقع للتسليم')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('shipping_status_updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->icon('heroicon-o-truck')
                    ->iconColor('success')
                    ->visible(fn ($record) => $record->hasActiveShipment()),
                \Filament\Schemas\Components\Section::make('ملاحظات')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label('ملاحظات')
                            ->wrap()
                            ->placeholder('-'),
                    ])
                    ->visible(fn ($record) => $record->notes),
                \Filament\Schemas\Components\Section::make('التفاصيل المالية')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('subtotal')
                                    ->label('المجموع الفرعي')
                                    ->money('SAR')
                                    ->icon('heroicon-o-calculator')
                                    ->iconColor('primary')
                                    ->size('lg'),
                                \Filament\Infolists\Components\TextEntry::make('discount')
                                    ->label('الخصم')
                                    ->money('SAR')
                                    ->placeholder('0.00 ر.س')
                                    ->icon('heroicon-o-tag')
                                    ->iconColor('success')
                                    ->color('success')
                                    ->size('lg')
                                    ->visible(fn ($record) => $record->discount > 0),
                                \Filament\Infolists\Components\TextEntry::make('discountCode.code')
                                    ->label('كود الخصم')
                                    ->placeholder('-')
                                    ->icon('heroicon-o-ticket')
                                    ->iconColor('info')
                                    ->badge()
                                    ->color('info')
                                    ->visible(fn ($record) => $record->discountCode),
                            ]),
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('shipping')
                                    ->label('الشحن')
                                    ->money('SAR')
                                    ->icon('heroicon-o-truck')
                                    ->iconColor('warning')
                                    ->size('lg'),
                                // Tax is included in product prices, hidden from general view as per request
                                /*
                                \Filament\Infolists\Components\TextEntry::make('tax')
                                    ->label('الضريبة')
                                    ->money('SAR')
                                    ->icon('heroicon-o-document-text')
                                    ->iconColor('danger')
                                    ->size('lg'),
                                */
                            ]),
                        \Filament\Infolists\Components\TextEntry::make('points_discount')
                            ->label('خصم النقاط')
                            ->money('SAR')
                            ->placeholder('0.00 ر.س')
                            ->icon('heroicon-o-star')
                            ->iconColor('warning')
                            ->color('warning')
                            ->size('lg')
                            ->visible(fn ($record) => $record->points_discount > 0),
                        \Filament\Infolists\Components\TextEntry::make('total')
                            ->label('الإجمالي النهائي')
                            ->money('SAR')
                            ->weight('bold')
                            ->size('xl')
                            ->color('success')
                            ->icon('heroicon-o-banknotes')
                            ->iconColor('success'),
                    ])
                    ->icon('heroicon-o-currency-dollar')
                    ->iconColor('success'),
                \Filament\Schemas\Components\Section::make('عناصر الطلب')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('items')
                            ->label('العناصر')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('product.name')
                                    ->label('المنتج'),
                                \Filament\Infolists\Components\TextEntry::make('productOption.value')
                                    ->label('الخيار')
                                    ->placeholder('-'),
                                \Filament\Infolists\Components\TextEntry::make('price')
                                    ->label('السعر')
                                    ->money('SAR'),
                                \Filament\Infolists\Components\TextEntry::make('quantity')
                                    ->label('الكمية'),
                                \Filament\Infolists\Components\TextEntry::make('total')
                                    ->label('الإجمالي')
                                    ->money('SAR'),
                            ])
                            ->columns(5),
                    ]),
                \Filament\Schemas\Components\Section::make('الفاتورة')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('invoice.invoice_number')
                            ->label('رقم الفاتورة')
                            ->copyable()
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('invoice.invoice_date')
                            ->label('تاريخ الفاتورة')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                        \Filament\Infolists\Components\TextEntry::make('invoice.type')
                            ->label('نوع الفاتورة')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'original' => 'فاتورة أصلية',
                                'credit_note' => 'إشعار دائن',
                                'refund' => 'استرداد',
                                default => $state,
                            })
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->invoice),

            ]);
    }

    public static function getWidgets(): array
    {
        return [
            OrderStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'awaiting-processing' => Pages\ListOrdersAwaitingProcessing::route('/awaiting-processing'),
            'ready-to-ship' => Pages\ListOrdersReadyToShip::route('/ready-to-ship'),
            'no-shipment' => Pages\ListOrdersNoShipment::route('/no-shipment'),
            'oto-in-progress' => Pages\ListOrdersOtoInProgress::route('/oto-in-progress'),
            'shipped' => Pages\ListOrdersShipped::route('/shipped'),
            'delivered' => Pages\ListOrdersDelivered::route('/delivered'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
