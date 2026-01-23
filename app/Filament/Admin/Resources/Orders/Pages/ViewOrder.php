<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Shipping\OtoShippingService;
use App\Services\Shipping\Oto\Exceptions\OtoValidationException;
use App\Services\Shipping\Oto\Exceptions\OtoApiException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('orders.show'), 403);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relationships for infolist
        $this->record->load(['user', 'location', 'paymentMethod', 'discountCode', 'items.product', 'items.productOption', 'invoice']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_oto_order')
                ->label('إنشاء طلب في OTO')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Textarea::make('shipping_notes')
                        ->label('ملاحظات الشحن (اختياري)')
                        ->helperText('أضف أي ملاحظات خاصة للطلب')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->requiresConfirmation()
                ->modalHeading('تأكيد إنشاء طلب OTO')
                ->modalDescription('سيتم إنشاء الطلب في نظام OTO (بدون إنشاء شحنة بعد). هل تريد المتابعة؟')
                ->modalSubmitActionLabel('نعم، إنشاء الطلب')
                ->action(function (array $data) {
                    try {
                        $shippingService = app(OtoShippingService::class);
                        $notes = $data['shipping_notes'] ?? null;

                        $otoOrderId = $shippingService->createOtoOrder($this->record, $notes);

                        Notification::make()
                            ->title('تم إنشاء الطلب في OTO بنجاح!')
                            ->success()
                            ->body("رقم طلب OTO: {$otoOrderId}")
                            ->duration(10000)
                            ->send();

                        $this->record->refresh();
                    } catch (OtoValidationException $e) {
                        Notification::make()
                            ->title('لا يمكن إنشاء طلب')
                            ->danger()
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    } catch (OtoApiException $e) {
                        Notification::make()
                            ->title('فشل الاتصال بـ OTO')
                            ->danger()
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    } catch (\Exception $e) {
                        Log::error('OTO order creation failed', [
                            'order_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('حدث خطأ')
                            ->danger()
                            ->body('فشل إنشاء الطلب: ' . $e->getMessage())
                            ->persistent()
                            ->send();
                    }
                })
                ->visible(fn () =>
                    $this->record->isEligibleForShipment()
                    && empty($this->record->oto_order_id)
                    && auth()->user()->can('orders.update')
                ),

            Action::make('create_oto_shipment')
                ->label('إنشاء شحنة (OTO)')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->form(function () {
                    try {
                        $shippingService = app(OtoShippingService::class);
                        $options = $shippingService->checkDeliveryOptions($this->record);

                        $selectOptions = [];
                        foreach ($options as $option) {
                            $label = ($option['name'] ?? 'Carrier') . " - " . ($option['price'] ?? '') . " SAR" . " (" . ($option['eta'] ?? '') . ")";
                            $selectOptions[$option['deliveryOptionId']] = $label;
                        }

                        if (empty($selectOptions)) {
                            return [
                                \Filament\Forms\Components\Placeholder::make('no_options')
                                    ->content('لا توجد خيارات شحن متاحة حالياً لهذا الطلب. سيتم استخدام الإعدادات الافتراضية في OTO.')
                            ];
                        }

                        return [
                            \Filament\Forms\Components\Select::make('delivery_option_id')
                                ->label('اختر شركة الشحن')
                                ->options($selectOptions)
                                ->searchable(),
                        ];
                    } catch (\Exception $e) {
                        return [
                            \Filament\Forms\Components\Placeholder::make('error')
                                ->content('حدث خطأ أثناء جلب خيارات الشحن: ' . $e->getMessage())
                        ];
                    }
                })
                ->action(function (array $data) {
                    try {
                        $shippingService = app(OtoShippingService::class);
                        $optionId = $data['delivery_option_id'] ?? null;

                        $shipmentDto = $shippingService->createShipment($this->record, $optionId ? (int) $optionId : null);

                        $title = $shipmentDto->hasTrackingInfo()
                            ? 'تم إنشاء الشحنة بنجاح!'
                            : 'تم إرسال طلب الشحن إلى OTO!';

                        $body = $shipmentDto->hasTrackingInfo()
                            ? "رقم التتبع: {$shipmentDto->trackingNumber}"
                            : 'طلب الشحن قيد المعالجة في OTO. سيتم تحديث رقم التتبع قريباً.';

                        Notification::make()
                            ->title($title)
                            ->success()
                            ->body($body)
                            ->actions([
                                Action::make('view_tracking')
                                    ->label('عرض التتبع')
                                    ->url($shipmentDto->trackingUrl, shouldOpenInNewTab: true)
                                    ->visible(fn () => !empty($shipmentDto->trackingUrl)),
                            ])
                            ->duration(10000)
                            ->send();

                        $this->record->refresh();
                    } catch (\Exception $e) {
                        Log::error('OTO shipment creation failed', [
                            'order_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('حدث خطأ')
                            ->danger()
                            ->body('فشل إنشاء الشحنة: ' . $e->getMessage())
                            ->persistent()
                            ->send();
                    }
                })
                ->visible(fn () =>
                    !empty($this->record->oto_order_id)
                    && empty($this->record->tracking_number)
                    && auth()->user()->can('orders.update')
                ),

            Action::make('sync_oto_status')
                ->label('تحديث من OTO')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    try {
                        $shippingService = app(OtoShippingService::class);
                        $shippingService->syncShipmentStatus($this->record);

                        Notification::make()
                            ->title('تم تحديث حالة الشحن')
                            ->success()
                            ->send();

                        $this->record->refresh();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('فشل التحديث')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn () => !empty($this->record->tracking_number) || !empty($this->record->oto_order_id)),

            \Filament\Actions\ActionGroup::make([
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_PENDING),
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_CONFIRMED),
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_PROCESSING),
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_SHIPPED),
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_IN_PROGRESS),
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_DELIVERED),
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_COMPLETED),
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
                    ->visible(fn () => $this->record->status !== Order::STATUS_CANCELLED),
            ])
            ->label('تحديث الحالة')
            ->icon('heroicon-o-ellipsis-vertical')
            ->color('gray')
            ->button()
            ->visible(fn () => auth()->user()->can('orders.update')),

            Action::make('print')
                ->label('طباعة الطلب')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('admin.orders.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => auth()->user()->can('orders.show')),
            EditAction::make()
                ->visible(fn () => auth()->user()->can('orders.update')),
        ];
    }
}
