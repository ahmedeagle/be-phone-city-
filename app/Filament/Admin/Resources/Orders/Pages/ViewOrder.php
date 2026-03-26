<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Shipping\OtoShippingService;
use App\Services\Shipping\Oto\Exceptions\OtoValidationException;
use App\Services\Shipping\Oto\Exceptions\OtoApiException;
use App\Services\PaymentService;
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
        $this->record->load(['user', 'location', 'branch', 'paymentMethod', 'discountCode', 'items.product', 'items.productOption', 'invoice']);

        // Automatically sync status from OTO if order has tracking or OTO order ID
        if ((!empty($this->record->tracking_number) || !empty($this->record->oto_order_id))
            && $this->record->status !== Order::STATUS_CANCELLED) {
            try {
                $shippingService = app(OtoShippingService::class);
                if (!empty($this->record->tracking_number)) {
                    $shippingService->syncShipmentStatus($this->record);
                }
            } catch (\Exception $e) {
                // Silently fail - don't interrupt page load
                Log::debug('Auto-sync OTO status failed on page load', [
                    'order_id' => $this->record->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

            Action::make('view_tracking')
                ->label('عرض التتبع')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->url(fn () => $this->record->tracking_url)
                ->openUrlInNewTab()
                ->visible(fn () => !empty($this->record->tracking_url)),

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

            Action::make('cancel_order_oto')
                ->label('إلغاء الطلب في OTO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تأكيد إلغاء الطلب في OTO')
                ->modalDescription(fn () => 'سيتم إلغاء الطلب في نظام OTO. هل أنت متأكد؟')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('سبب الإلغاء (اختياري)')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $shippingService = app(OtoShippingService::class);
                        $reason = $data['reason'] ?? null;

                        // Cancel shipment if exists, otherwise cancel order
                        if (!empty($this->record->tracking_number)) {
                            $shippingService->cancelShipment($this->record, $reason);
                            $message = 'تم إلغاء الشحنة في OTO بنجاح';
                        } elseif (!empty($this->record->oto_order_id)) {
                            $shippingService->cancelOrder($this->record, $reason);
                            $message = 'تم إلغاء الطلب في OTO بنجاح';
                        } else {
                            throw new \Exception('لا يوجد طلب أو شحنة في OTO لإلغائها');
                        }

                        Notification::make()
                            ->title($message)
                            ->success()
                            ->send();

                        $this->record->refresh();
                    } catch (\App\Services\Shipping\Oto\Exceptions\OtoApiException $e) {
                        Notification::make()
                            ->title('فشل الإلغاء في OTO')
                            ->danger()
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    } catch (\Exception $e) {
                        Log::error('Failed to cancel OTO order/shipment', [
                            'order_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('حدث خطأ')
                            ->danger()
                            ->body('فشل الإلغاء: ' . $e->getMessage())
                            ->persistent()
                            ->send();
                    }
                })
                ->visible(fn () =>
                    (!empty($this->record->tracking_number) || !empty($this->record->oto_order_id))
                    && $this->record->status !== Order::STATUS_CANCELLED
                    && auth()->user()->can('orders.update')
                ),

            Action::make('approve_payment')
                ->label('قبول الدفع')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تأكيد قبول الدفع')
                ->modalDescription('هل تريد قبول إيصال التحويل البنكي وتعيين الطلب كمدفوع؟ سيتم تنفيذ جميع الإجراءات التلقائية (خصم المخزون، إرسال إشعار، إضافة النقاط، إنشاء شحنة).')
                ->modalSubmitActionLabel('نعم، قبول الدفع')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات (اختياري)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $paymentService = app(PaymentService::class);
                        $paymentService->reviewPaymentProof($this->record, true, $data['notes'] ?? null);

                        Notification::make()
                            ->title('تم قبول الدفع بنجاح')
                            ->success()
                            ->body('تم تأكيد الدفع وتنفيذ جميع الإجراءات التلقائية (خصم مخزون، إشعار، نقاط، شحنة)')
                            ->duration(8000)
                            ->send();

                        $this->record->refresh();
                    } catch (\Exception $e) {
                        Log::error('Admin approve payment failed', [
                            'order_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('فشل قبول الدفع')
                            ->danger()
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                })
                ->visible(fn () =>
                    $this->record->payment_status === Order::PAYMENT_STATUS_AWAITING_REVIEW
                    && auth()->user()->can('orders.update')
                ),

            Action::make('reject_payment')
                ->label('رفض الدفع')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('تأكيد رفض الدفع')
                ->modalDescription('هل تريد رفض إيصال التحويل البنكي؟ سيتمكن العميل من رفع إيصال جديد.')
                ->modalSubmitActionLabel('نعم، رفض الدفع')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_notes')
                        ->label('سبب الرفض')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('سيتم إرسال السبب للعميل'),
                ])
                ->action(function (array $data) {
                    try {
                        $paymentService = app(PaymentService::class);
                        $paymentService->reviewPaymentProof($this->record, false, $data['rejection_notes'] ?? null);

                        Notification::make()
                            ->title('تم رفض الدفع')
                            ->danger()
                            ->body('تم إبلاغ العميل ويمكنه رفع إيصال جديد')
                            ->send();

                        $this->record->refresh();
                    } catch (\Exception $e) {
                        Log::error('Admin reject payment failed', [
                            'order_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('فشل رفض الدفع')
                            ->danger()
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                })
                ->visible(fn () =>
                    $this->record->payment_status === Order::PAYMENT_STATUS_AWAITING_REVIEW
                    && auth()->user()->can('orders.update')
                ),

            Action::make('process_confirmed_order')
                ->label('بدء معالجة الطلب')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تأكيد بدء المعالجة')
                ->modalDescription('هذا الطلب مؤكد ومدفوع. هل تريد نقله إلى مرحلة المعالجة؟ سيتم إنشاء شحنة OTO تلقائياً إذا كان التوصيل منزلي.')
                ->modalSubmitActionLabel('نعم، ابدأ المعالجة')
                ->action(function () {
                    try {
                        $this->record->update(['status' => Order::STATUS_PROCESSING]);

                        Notification::make()
                            ->title('تم نقل الطلب للمعالجة')
                            ->success()
                            ->body('سيتم إنشاء شحنة OTO تلقائياً إذا كان التوصيل منزلي')
                            ->duration(8000)
                            ->send();

                        $this->record->refresh();
                    } catch (\Exception $e) {
                        Log::error('Admin process confirmed order failed', [
                            'order_id' => $this->record->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('فشل بدء المعالجة')
                            ->danger()
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();
                    }
                })
                ->visible(fn () =>
                    $this->record->status === Order::STATUS_CONFIRMED
                    && $this->record->payment_status === Order::PAYMENT_STATUS_PAID
                    && auth()->user()->can('orders.update')
                ),

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
