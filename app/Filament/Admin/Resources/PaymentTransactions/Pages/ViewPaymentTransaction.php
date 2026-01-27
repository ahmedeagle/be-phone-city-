<?php

namespace App\Filament\Admin\Resources\PaymentTransactions\Pages;

use App\Filament\Admin\Resources\PaymentTransactions\PaymentTransactionResource;
use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewPaymentTransaction extends ViewRecord
{
    protected static string $resource = PaymentTransactionResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('payment_transactions.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),

            Actions\Action::make('review_payment')
                ->label('مراجعة')
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->visible(fn () => $this->record->requiresReview() && auth()->user()->can('payment_transactions.review'))
                ->form(function () {
                    $components = [];
                    
                    if ($this->record->hasPaymentProof()) {
                        $proofPath = $this->record->payment_proof_path;
                        $extension = pathinfo($proofPath, PATHINFO_EXTENSION);
                        $isPdf = strtolower($extension) === 'pdf';
                        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                        $proofUrl = route('admin.payment-transactions.proof', ['transaction' => $this->record->id]);
                        
                        $html = view('filament.forms.payment-proof-viewer', [
                            'record' => $this->record,
                            'proofPath' => $proofPath,
                            'isPdf' => $isPdf,
                            'isImage' => $isImage,
                            'proofUrl' => $proofUrl,
                        ])->render();
                        
                        $components[] = Placeholder::make('payment_proof')
                            ->label('إثبات الدفع')
                            ->content(new HtmlString($html));
                    }
                    
                    $components[] = Radio::make('decision')
                        ->label('القرار')
                        ->options([
                            'approve' => 'قبول',
                            'reject' => 'رفض',
                        ])
                        ->required()
                        ->inline();
                    
                    $components[] = Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->placeholder('سبب الرفض أو ملاحظات إضافية');
                    
                    return $components;
                })
                ->action(function (array $data) {
                    try {
                        $approve = $data['decision'] === 'approve';
                        $paymentService = app(PaymentService::class);

                        $paymentService->reviewPaymentProof(
                            $this->record->order,
                            $approve,
                            $data['notes'] ?? null
                        );

                        Notification::make()
                            ->success()
                            ->title($approve ? 'تم قبول الدفع' : 'تم رفض الدفع')
                            ->body($approve ? 'تم تحديث حالة الطلب بنجاح' : 'تم إخطار العميل بالرفض')
                            ->send();

                        // Refresh the page to show updated data
                        $this->refresh();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('فشل في مراجعة الدفع')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
