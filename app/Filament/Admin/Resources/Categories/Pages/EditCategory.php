<?php

namespace App\Filament\Admin\Resources\Categories\Pages;

use App\Filament\Admin\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('categories.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => auth()->user()->can('categories.show')),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->can('categories.delete'))
                ->requiresConfirmation(),
        ];
    }
}
