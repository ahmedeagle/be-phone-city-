<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('users.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => auth()->user()->can('users.update')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load relationships to avoid N+1 queries
        // Limit to recent 20 orders and reviews for performance
        $this->record->load([
            'orders' => function ($query) {
                $query->latest()
                    ->limit(20)
                    ->with('paymentMethod', 'location');
            },
            'reviews' => function ($query) {
                $query->latest()
                    ->limit(20)
                    ->with('product');
            },
        ]);

        return $data;
    }
}

