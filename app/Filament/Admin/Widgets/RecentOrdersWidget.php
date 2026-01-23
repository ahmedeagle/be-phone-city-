<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getHeading(): string
    {
        return 'آخر الطلبات';
    }

    public static function getSort(): int
    {
        return 5;
    }

    public function getTableRecordsPerPage(): int | string | null
    {
        return 5;
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Order::query()
            ->with(['user', 'paymentMethod'])
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('order_number')
                ->label('رقم الطلب')
                ->searchable()
                ->copyable()
                ->weight('bold'),
            TextColumn::make('user.name')
                ->label('العميل')
                ->searchable()
                ->limit(20),
            TextColumn::make('total')
                ->label('الإجمالي')
                ->money('SAR')
                ->sortable()
                ->weight('bold')
                ->color('success'),
            TextColumn::make('status')
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
                    Order::STATUS_PENDING => 'قيد الانتظار',
                    Order::STATUS_CONFIRMED => 'مؤكد',
                    Order::STATUS_PROCESSING => 'قيد المعالجة',
                    Order::STATUS_SHIPPED => 'تم الشحن',
                    Order::STATUS_IN_PROGRESS => 'قيد التوصيل',
                    Order::STATUS_DELIVERED => 'تم التسليم',
                    Order::STATUS_COMPLETED => 'مكتمل',
                    Order::STATUS_CANCELLED => 'ملغي',
                    default => $state,
                }),
            TextColumn::make('created_at')
                ->label('التاريخ')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label('عرض')
                ->icon('heroicon-o-eye')
                ->url(fn (Order $record): string => route('filament.admin.resources.orders.view', $record))
                ->color('primary'),
        ];
    }

    protected function getTableHeading(): ?string
    {
        return 'آخر الطلبات (أحدث 5)';
    }
}

