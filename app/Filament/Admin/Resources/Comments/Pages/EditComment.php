<?php

namespace App\Filament\Admin\Resources\Comments\Pages;

use App\Filament\Admin\Resources\Comments\CommentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditComment extends EditRecord
{
    protected static string $resource = CommentResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('comments.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => auth()->user()->can('comments.show')),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->can('comments.delete'))
                ->requiresConfirmation(),
        ];
    }
}

