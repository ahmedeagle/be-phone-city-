<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Pages;

use App\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethod extends EditRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('payment_methods.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => auth()->user()->can('payment_methods.show')),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->can('payment_methods.delete'))
                ->requiresConfirmation(),
        ];
    }
}
