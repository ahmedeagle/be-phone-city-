<?php

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Filament\Admin\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('tickets.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

