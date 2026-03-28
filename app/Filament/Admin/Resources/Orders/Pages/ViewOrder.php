<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
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

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve_payment')
                ->label('قبول الدفع')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('تأكيد قبول الدفع')
                ->modalDescription('هل تريد قبول إيصال التحويل البنكي وتعيين الطلب كمدفوع؟ سيتم تنفيذ جميع الإجراءات التلقائية (خصم المخزون، إرسال إشعار، إضافة النقاط).')
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
                            ->body('تم تأكيد الدفع وتنفيذ جميع الإجراءات التلقائية (خصم مخزون، إشعار، نقاط)')
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
                ->modalDescription('هذا الطلب مؤكد ومدفوع. هل تريد نقله إلى مرحلة المعالجة؟ سيظهر الطلب في صفحة "جاهزة للشحن" لإنشاء الشحنة.')
                ->modalSubmitActionLabel('نعم، ابدأ المعالجة')
                ->action(function () {
                    try {
                        $this->record->update(['status' => Order::STATUS_PROCESSING]);

                        Notification::make()
                            ->title('تم نقل الطلب للمعالجة')
                            ->success()
                            ->body('الطلب الآن في صفحة "جاهزة للشحن" — يمكنك إنشاء الشحنة من هناك')
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
