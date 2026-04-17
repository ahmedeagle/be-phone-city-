<?php

namespace App\Filament\Admin\Pages;

use App\Models\Order;
use App\Models\PaymentTransaction;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class DailyAccountingReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Calculator;
    protected static ?string $navigationLabel = 'التقرير اليومي';
    protected static ?string $title = 'التقرير المحاسبي اليومي';
    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'المبيعات والمدفوعات';
    }
    protected string $view = 'filament.admin.pages.daily-accounting-report';

    public ?string $report_date = null;

    public function mount(): void
    {
        $this->report_date = today()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('report_date')
                    ->label('تاريخ التقرير')
                    ->default(today())
                    ->native(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => null),
            ]);
    }

    public function getReportData(): array
    {
        $date = Carbon::parse($this->report_date);

        // All orders for the day
        $orders = Order::with(['user', 'currentPaymentTransaction', 'items.product', 'shippingCompany'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        // Summary stats
        $totalOrders = $orders->count();
        $totalRevenue = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('total');
        $totalSubtotal = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('subtotal');
        $totalDiscount = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('discount');
        $totalVipDiscount = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('vip_discount');
        $totalPointsDiscount = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('points_discount');
        $totalShipping = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('shipping');
        $totalTax = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID)->sum('tax');

        // Orders by status
        $statusCounts = [];
        foreach (Order::getAvailableStatuses() as $status) {
            $count = $orders->where('status', $status)->count();
            if ($count > 0) {
                $statusCounts[] = [
                    'status' => $status,
                    'label' => $this->getStatusLabel($status),
                    'count' => $count,
                    'color' => $this->getStatusColor($status),
                ];
            }
        }

        // Payment status breakdown
        $paymentStatusCounts = [];
        $paymentStatuses = [
            Order::PAYMENT_STATUS_PENDING,
            Order::PAYMENT_STATUS_AWAITING_REVIEW,
            Order::PAYMENT_STATUS_PROCESSING,
            Order::PAYMENT_STATUS_PAID,
            Order::PAYMENT_STATUS_FAILED,
            Order::PAYMENT_STATUS_CANCELLED,
            Order::PAYMENT_STATUS_EXPIRED,
            Order::PAYMENT_STATUS_REFUNDED,
            Order::PAYMENT_STATUS_PARTIALLY_REFUNDED,
        ];
        foreach ($paymentStatuses as $ps) {
            $count = $orders->where('payment_status', $ps)->count();
            if ($count > 0) {
                $paymentStatusCounts[] = [
                    'status' => $ps,
                    'label' => $this->getPaymentStatusLabel($ps),
                    'count' => $count,
                    'total' => $orders->where('payment_status', $ps)->sum('total'),
                ];
            }
        }

        // Payment gateway breakdown (from paid orders)
        $paidOrders = $orders->where('payment_status', Order::PAYMENT_STATUS_PAID);
        $gatewayBreakdown = [];
        $gateways = [
            PaymentTransaction::GATEWAY_CASH => 'الدفع عند الاستلام',
            PaymentTransaction::GATEWAY_BANK_TRANSFER => 'تحويل بنكي',
            PaymentTransaction::GATEWAY_TAMARA => 'تمارا',
            PaymentTransaction::GATEWAY_TABBY => 'تابي',
            PaymentTransaction::GATEWAY_AMWAL => 'أموال',
        ];

        foreach ($gateways as $gateway => $label) {
            $gatewayOrders = $paidOrders->filter(function ($order) use ($gateway) {
                return $order->currentPaymentTransaction && $order->currentPaymentTransaction->gateway === $gateway;
            });
            if ($gatewayOrders->count() > 0) {
                $gatewayBreakdown[] = [
                    'gateway' => $gateway,
                    'label' => $label,
                    'count' => $gatewayOrders->count(),
                    'total' => $gatewayOrders->sum('total'),
                ];
            }
        }

        // Delivery method breakdown
        $homeDelivery = $orders->where('delivery_method', Order::DELIVERY_HOME)->count();
        $storePickup = $orders->where('delivery_method', Order::DELIVERY_STORE_PICKUP)->count();

        // Items sold count
        $itemsSold = 0;
        foreach ($paidOrders as $order) {
            $itemsSold += $order->items->sum('quantity');
        }

        // Awaiting action
        $awaitingReview = $orders->where('payment_status', Order::PAYMENT_STATUS_AWAITING_REVIEW)->count();
        $pendingOrders = $orders->where('status', Order::STATUS_PENDING)->count();
        $cancelledOrders = $orders->where('status', Order::STATUS_CANCELLED);
        $cancelledTotal = $cancelledOrders->sum('total');
        $cancelledCount = $cancelledOrders->count();

        return [
            'date' => $date,
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'totalSubtotal' => $totalSubtotal,
            'totalDiscount' => $totalDiscount,
            'totalVipDiscount' => $totalVipDiscount,
            'totalPointsDiscount' => $totalPointsDiscount,
            'totalShipping' => $totalShipping,
            'totalTax' => $totalTax,
            'statusCounts' => $statusCounts,
            'paymentStatusCounts' => $paymentStatusCounts,
            'gatewayBreakdown' => $gatewayBreakdown,
            'homeDelivery' => $homeDelivery,
            'storePickup' => $storePickup,
            'itemsSold' => $itemsSold,
            'awaitingReview' => $awaitingReview,
            'pendingOrders' => $pendingOrders,
            'cancelledCount' => $cancelledCount,
            'cancelledTotal' => $cancelledTotal,
        ];
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'معلق',
            Order::STATUS_CONFIRMED => 'مؤكد',
            Order::STATUS_PROCESSING => 'قيد التجهيز',
            Order::STATUS_SHIPPED => 'تم الشحن',
            Order::STATUS_IN_PROGRESS => 'جاري التوصيل',
            Order::STATUS_DELIVERED => 'تم التوصيل',
            Order::STATUS_COMPLETED => 'مكتمل',
            Order::STATUS_CANCELLED => 'ملغي',
            default => $status,
        };
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'warning',
            Order::STATUS_CONFIRMED => 'info',
            Order::STATUS_PROCESSING => 'primary',
            Order::STATUS_SHIPPED => 'info',
            Order::STATUS_IN_PROGRESS => 'info',
            Order::STATUS_DELIVERED => 'success',
            Order::STATUS_COMPLETED => 'success',
            Order::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    private function getPaymentStatusLabel(string $status): string
    {
        return match ($status) {
            Order::PAYMENT_STATUS_PENDING => 'بانتظار الدفع',
            Order::PAYMENT_STATUS_AWAITING_REVIEW => 'بانتظار المراجعة',
            Order::PAYMENT_STATUS_PROCESSING => 'جاري المعالجة',
            Order::PAYMENT_STATUS_PAID => 'مدفوع',
            Order::PAYMENT_STATUS_FAILED => 'فشل الدفع',
            Order::PAYMENT_STATUS_CANCELLED => 'ملغي',
            Order::PAYMENT_STATUS_EXPIRED => 'منتهي',
            Order::PAYMENT_STATUS_REFUNDED => 'مسترجع',
            Order::PAYMENT_STATUS_PARTIALLY_REFUNDED => 'مسترجع جزئياً',
            default => $status,
        };
    }
}
