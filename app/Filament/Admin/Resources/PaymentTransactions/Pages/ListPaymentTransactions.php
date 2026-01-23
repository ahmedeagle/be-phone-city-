<?php

namespace App\Filament\Admin\Resources\PaymentTransactions\Pages;

use App\Filament\Admin\Resources\PaymentTransactions\PaymentTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTransactions extends ListRecords
{
    protected static string $resource = PaymentTransactionResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('payment_transactions.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            // No create action - transactions are created automatically
        ];
    }
}
