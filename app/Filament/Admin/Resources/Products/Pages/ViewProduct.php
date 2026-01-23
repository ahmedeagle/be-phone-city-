<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('products.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => auth()->user()->can('products.update')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load relationships to avoid N+1 queries
        $this->record->load([
            'reviews.user',
            'orderItems.order' => function ($query) {
                $query->where('status', \App\Models\Order::STATUS_COMPLETED);
            }
        ]);

        return $data;
    }
}
