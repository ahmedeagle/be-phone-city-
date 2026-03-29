<?php

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Services\NotificationService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    private ?string $originalStatus = null;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('tickets.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => auth()->user()->can('tickets.show')),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->can('tickets.delete'))
                ->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Remember original status to detect changes
        $this->originalStatus = $data['status'] ?? null;
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set resolved_at when status changes to resolved
        if (($data['status'] ?? null) === Ticket::STATUS_RESOLVED && !$this->record->resolved_at) {
            $data['resolved_at'] = now();
        }

        // Auto-assign admin if not assigned and status is changing
        if (!$this->record->admin_id && isset($data['admin_id']) && $data['admin_id']) {
            // Admin is being assigned
        } elseif (!$this->record->admin_id && ($data['status'] ?? null) !== Ticket::STATUS_PENDING) {
            $data['admin_id'] = auth()->id();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Notify customer when status changes
        if ($this->originalStatus && $this->originalStatus !== $this->record->status) {
            app(NotificationService::class)->notifyTicketUpdated($this->record);
        }
    }
}

