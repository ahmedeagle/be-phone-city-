<?php

namespace App\Filament\Admin\Resources\Offers\Pages;

use App\Filament\Admin\Resources\Offers\OfferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOffers extends ManageRecords
{
    protected static string $resource = OfferResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('offers.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth()->user()->can('offers.create')),
        ];
    }
}
