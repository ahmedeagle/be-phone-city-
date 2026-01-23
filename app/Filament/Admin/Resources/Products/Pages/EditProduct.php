<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('products.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض')
                ->visible(fn () => auth()->user()->can('products.show')),
            Actions\DeleteAction::make()
                ->label('حذف')
                ->visible(fn () => auth()->user()->can('products.delete'))
                ->requiresConfirmation(),
            ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
