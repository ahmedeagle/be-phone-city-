<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('orders.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => auth()->user()->can('orders.show')),
            Action::make('mark_pending')
                ->label('قيد الانتظار')
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_PENDING]);
                    Notification::make()
                        ->title('تم تحديث حالة الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_PENDING && auth()->user()->can('orders.update')),
            Action::make('mark_confirmed')
                ->label('تأكيد')
                ->icon('heroicon-o-check')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_CONFIRMED]);
                    Notification::make()
                        ->title('تم تحديث حالة الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_CONFIRMED && auth()->user()->can('orders.update')),
            Action::make('mark_processing')
                ->label('قيد المعالجة')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_PROCESSING]);
                    Notification::make()
                        ->title('تم تحديث حالة الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_PROCESSING && auth()->user()->can('orders.update')),
            Action::make('mark_shipped')
                ->label('تم الشحن')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_SHIPPED]);
                    Notification::make()
                        ->title('تم تحديث حالة الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_SHIPPED && auth()->user()->can('orders.update')),
            Action::make('mark_in_progress')
                ->label('قيد التوصيل')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_IN_PROGRESS]);
                    Notification::make()
                        ->title('تم تحديث حالة الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_IN_PROGRESS && auth()->user()->can('orders.update')),
            Action::make('mark_delivered')
                ->label('تم التسليم')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_DELIVERED]);
                    Notification::make()
                        ->title('تم تحديث حالة الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_DELIVERED && auth()->user()->can('orders.update')),
            Action::make('mark_completed')
                ->label('مكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_COMPLETED]);
                    Notification::make()
                        ->title('تم تحديث حالة الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_COMPLETED && auth()->user()->can('orders.update')),
            Action::make('mark_cancelled')
                ->label('إلغاء')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => Order::STATUS_CANCELLED]);
                    Notification::make()
                        ->title('تم إلغاء الطلب')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status !== Order::STATUS_CANCELLED && auth()->user()->can('orders.update')),
            Action::make('print')
                ->label('طباعة الطلب')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('admin.orders.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => auth()->user()->can('orders.show')),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->can('orders.delete'))
                ->requiresConfirmation(),
        ];
    }
}
