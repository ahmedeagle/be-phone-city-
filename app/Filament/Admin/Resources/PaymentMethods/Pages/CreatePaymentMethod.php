<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Pages;

use App\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('payment_methods.create'), 403);
    }
}
