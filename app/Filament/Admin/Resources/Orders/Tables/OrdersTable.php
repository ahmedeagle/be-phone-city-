<?php

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->with(['user', 'paymentMethod', 'location', 'invoice']);
            })
            ->columns([
                TextColumn::make('order_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('تم نسخ رقم الطلب'),
                TextColumn::make('user.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('SAR')
                    ->sortable(),
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
                        Order::STATUS_PENDING => 'بانتظار الدفع',
                        Order::STATUS_CONFIRMED => 'تم تأكيد الطلب',
                        Order::STATUS_PROCESSING => 'جاري تجهيز الطلب',
                        Order::STATUS_SHIPPED => 'تم الشحن',
                        Order::STATUS_IN_PROGRESS => 'جاري التوصيل',
                        Order::STATUS_DELIVERED => 'تم التوصيل',
                        Order::STATUS_COMPLETED => 'مكتمل',
                        Order::STATUS_CANCELLED => 'ملغي',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('payment_status')
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
                    })
                    ->sortable(),
                TextColumn::make('delivery_method')
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
                    })
                    ->sortable(),
                TextColumn::make('paymentMethod.name')
                    ->label('طريقة الدفع')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('discount')
                    ->label('الخصم')
                    ->money('SAR')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items_count')
                    ->label('عدد العناصر')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        Order::STATUS_PENDING => 'قيد الانتظار',
                        Order::STATUS_CONFIRMED => 'مؤكد',
                        Order::STATUS_PROCESSING => 'قيد المعالجة',
                        Order::STATUS_SHIPPED => 'تم الشحن',
                        Order::STATUS_IN_PROGRESS => 'قيد التوصيل',
                        Order::STATUS_DELIVERED => 'تم التسليم',
                        Order::STATUS_COMPLETED => 'مكتمل',
                        Order::STATUS_CANCELLED => 'ملغي',
                    ])
                    ->multiple(),
                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'pending' => 'بانتظار الدفع',
                        'awaiting_review' => 'بانتظار المراجعة',
                        'processing' => 'قيد المعالجة',
                        'paid' => 'مدفوع',
                        'failed' => 'فشل',
                        'cancelled' => 'ملغي',
                    ])
                    ->multiple(),
                Filter::make('awaiting_payment_review')
                    ->label('بانتظار مراجعة الدفع')
                    ->query(fn ($query) => $query->where('payment_status', 'awaiting_review'))
                    ->toggle(),
                Filter::make('paid_without_shipment')
                    ->label('مدفوع بدون شحنة')
                    ->query(fn ($query) => $query
                        ->where('payment_status', 'paid')
                        ->where('delivery_method', Order::DELIVERY_HOME)
                        ->whereNull('tracking_number')
                        ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
                    )
                    ->toggle(),
                SelectFilter::make('delivery_method')
                    ->label('طريقة التوصيل')
                    ->options([
                        Order::DELIVERY_HOME => 'توصيل منزلي',
                        Order::DELIVERY_STORE_PICKUP => 'استلام من المتجر',
                    ]),
                Filter::make('created_at')
                    ->label('تاريخ الطلب')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('total')
                    ->label('قيمة الطلب')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('total_from')
                            ->label('من')
                            ->numeric(),
                        \Filament\Forms\Components\TextInput::make('total_until')
                            ->label('إلى')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['total_from'],
                                fn ($query, $amount) => $query->where('total', '>=', $amount),
                            )
                            ->when(
                                $data['total_until'],
                                fn ($query, $amount) => $query->where('total', '<=', $amount),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => auth()->user()->can('orders.show')),
                EditAction::make()
                    ->visible(fn () => auth()->user()->can('orders.update')),
                Action::make('print')
                    ->label('طباعة')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Order $record) => route('admin.orders.print', $record))
                    ->openUrlInNewTab()
                    ->visible(fn () => auth()->user()->can('orders.show')),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('orders.delete'))
                    ->requiresConfirmation()
                    ->before(function (Order $record, $action) {
                        if ($record->payment_status === Order::PAYMENT_STATUS_PAID) {
                            \Filament\Notifications\Notification::make()
                                ->title('لا يمكن حذف الطلب')
                                ->body('لا يمكن حذف طلب مدفوع.')
                                ->danger()
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_pending')
                        ->label('تحديد كقيد الانتظار')
                        ->icon('heroicon-o-clock')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => Order::STATUS_PENDING]);
                            }
                        }),
                    BulkAction::make('mark_confirmed')
                        ->label('تأكيد الطلبات')
                        ->icon('heroicon-o-check')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => Order::STATUS_CONFIRMED]);
                            }
                        }),
                    BulkAction::make('mark_processing')
                        ->label('تحديد كقيد المعالجة')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => Order::STATUS_PROCESSING]);
                            }
                        }),
                    BulkAction::make('mark_shipped')
                        ->label('تحديد كتم الشحن')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => Order::STATUS_SHIPPED]);
                            }
                        }),
                    BulkAction::make('mark_in_progress')
                        ->label('تحديد كقيد التوصيل')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => Order::STATUS_IN_PROGRESS]);
                            }
                        }),
                    BulkAction::make('mark_delivered')
                        ->label('تحديد كتم التسليم')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => Order::STATUS_DELIVERED]);
                            }
                        }),
                    BulkAction::make('mark_completed')
                        ->label('تحديد كمكتمل')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->payment_status !== Order::PAYMENT_STATUS_PAID) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('تعذّر تحديد طلب كمكتمل')
                                        ->body("الطلب #{$record->order_number} لم يتم دفعه بعد.")
                                        ->warning()
                                        ->send();
                                    continue;
                                }
                                $record->update(['status' => Order::STATUS_COMPLETED]);
                            }
                        }),
                    BulkAction::make('mark_cancelled')
                        ->label('إلغاء الطلبات')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()->can('orders.update'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['status' => Order::STATUS_CANCELLED]);
                            }
                        }),
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('orders.delete'))
                        ->before(function ($records, $action) {
                            $paidOrders = $records->filter(fn ($record) => $record->payment_status === Order::PAYMENT_STATUS_PAID);
                            if ($paidOrders->isNotEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('لا يمكن الحذف')
                                    ->body('لا يمكن حذف الطلبات المدفوعة. يرجى إلغاؤها أولاً إذا لزم الأمر.')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }
}
