<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('orders.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => auth()->user()->can('orders.show')),
            Action::make('print')
                ->label('طباعة الطلب')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('admin.orders.print', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => auth()->user()->can('orders.show')),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->can('orders.delete'))
                ->requiresConfirmation(),
        ];
    }
}
