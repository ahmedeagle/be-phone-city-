<?php

namespace App\Filament\Admin\Resources\PaymentTransactions\Pages;

use App\Filament\Admin\Resources\PaymentTransactions\PaymentTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentTransaction extends ViewRecord
{
    protected static string $resource = PaymentTransactionResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('payment_transactions.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}
