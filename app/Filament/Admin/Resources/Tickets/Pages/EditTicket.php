<?php

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set resolved_at when status changes to resolved
        if (($data['status'] ?? null) === Ticket::STATUS_RESOLVED && !isset($data['resolved_at'])) {
            $data['resolved_at'] = now();
        }

        // Auto-assign status to in_progress when admin is assigned
        if (isset($data['admin_id']) && !isset($data['status']) && !$this->record->admin_id) {
            $data['status'] = Ticket::STATUS_IN_PROGRESS;
        }

        return $data;
    }
}

