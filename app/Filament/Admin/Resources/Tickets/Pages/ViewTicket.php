<?php

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Notifications\TicketNotification;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('tickets.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Edit action
            EditAction::make()
                ->label('تعديل')
                ->visible(fn () => auth()->user()->can('tickets.update')),

            // Change status action
            Action::make('changeStatus')
                ->label('تغيير الحالة')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => auth()->user()->can('tickets.update'))
                ->form([
                    Select::make('status')
                        ->label('الحالة الجديدة')
                        ->options([
                            Ticket::STATUS_PENDING => 'قيد الانتظار',
                            Ticket::STATUS_IN_PROGRESS => 'قيد المعالجة',
                            Ticket::STATUS_RESOLVED => 'تم الحل',
                            Ticket::STATUS_CLOSED => 'مغلق',
                        ])
                        ->default(fn () => $this->record->status)
                        ->required(),
                    Textarea::make('resolution_notes')
                        ->label('ملاحظات')
                        ->rows(3)
                        ->maxLength(2000)
                        ->placeholder('أضف ملاحظة اختيارية...')
                        ->visible(fn ($get) => in_array($get('status'), [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])),
                ])
                ->action(function (array $data): void {
                    $oldStatus = $this->record->status;
                    $updateData = ['status' => $data['status']];

                    if ($data['status'] === Ticket::STATUS_RESOLVED) {
                        $updateData['resolved_at'] = now();
                        if (!empty($data['resolution_notes'])) {
                            $updateData['resolution_notes'] = $data['resolution_notes'];
                        }
                    }

                    // Auto-assign admin if not assigned
                    if (!$this->record->admin_id) {
                        $updateData['admin_id'] = auth()->id();
                    }

                    $this->record->update($updateData);
                    $this->record->refresh();

                    // Notify customer about status change
                    if ($oldStatus !== $data['status']) {
                        app(NotificationService::class)->notifyTicketUpdated($this->record);
                    }

                    Notification::make()
                        ->title('تم تحديث حالة التذكرة')
                        ->success()
                        ->send();
                }),

            // Reply by email action
            Action::make('replyByEmail')
                ->label('رد بالبريد الإلكتروني')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->visible(fn () => auth()->user()->can('tickets.update') && $this->getCustomerEmail())
                ->form([
                    Textarea::make('message')
                        ->label('الرسالة')
                        ->rows(5)
                        ->required()
                        ->maxLength(5000)
                        ->placeholder('اكتب ردك هنا...'),
                ])
                ->action(function (array $data): void {
                    $email = $this->getCustomerEmail();
                    if (!$email) {
                        Notification::make()
                            ->title('لا يوجد بريد إلكتروني للعميل')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Auto-assign admin and set in_progress if still pending
                    $updateData = [];
                    if (!$this->record->admin_id) {
                        $updateData['admin_id'] = auth()->id();
                    }
                    if ($this->record->status === Ticket::STATUS_PENDING) {
                        $updateData['status'] = Ticket::STATUS_IN_PROGRESS;
                    }
                    if (!empty($updateData)) {
                        $this->record->update($updateData);
                        $this->record->refresh();
                    }

                    // Send reply notification with admin message
                    if ($this->record->user) {
                        $this->record->user->notify(
                            (new TicketNotification($this->record, 'replied', $data['message']))->locale('ar')
                        );
                    } else {
                        // Guest ticket — send directly via mail
                        \Illuminate\Support\Facades\Notification::route('mail', $email)
                            ->notify(
                                (new TicketNotification($this->record, 'replied', $data['message']))->locale('ar')
                            );
                    }

                    Notification::make()
                        ->title('تم إرسال الرد بنجاح')
                        ->body('تم إرسال الرد إلى: ' . $email)
                        ->success()
                        ->send();
                }),

            // Assign to me action
            Action::make('assignToMe')
                ->label('تعيين لي')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn () => auth()->user()->can('tickets.update') && !$this->record->admin_id)
                ->action(function (): void {
                    $updateData = ['admin_id' => auth()->id()];
                    if ($this->record->status === Ticket::STATUS_PENDING) {
                        $updateData['status'] = Ticket::STATUS_IN_PROGRESS;
                    }
                    $this->record->update($updateData);
                    $this->record->refresh();

                    Notification::make()
                        ->title('تم تعيين التذكرة لك')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Get customer email from user relation or guest fields.
     */
    private function getCustomerEmail(): ?string
    {
        if ($this->record->user_id && $this->record->user) {
            return $this->record->user->email;
        }
        return $this->record->email;
    }
}

