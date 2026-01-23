<?php

namespace App\Filament\Admin\Resources\Blogs\Pages;

use App\Filament\Admin\Resources\Blogs\BlogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBlog extends EditRecord
{
    protected static string $resource = BlogResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('blogs.update'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->visible(fn () => auth()->user()->can('blogs.show')),
            DeleteAction::make()
                ->visible(fn () => auth()->user()->can('blogs.delete'))
                ->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set published_at if is_published is true and published_at is not set
        if (($data['is_published'] ?? false) && !isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
