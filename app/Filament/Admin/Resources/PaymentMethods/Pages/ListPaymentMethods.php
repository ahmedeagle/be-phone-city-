<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Pages;

use App\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('payment_methods.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => auth()->user()->can('payment_methods.create')),
        ];
    }
}
