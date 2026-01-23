<?php

namespace App\Filament\Admin\Resources\Blogs\Pages;

use App\Filament\Admin\Resources\Blogs\BlogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlog extends CreateRecord
{
    protected static string $resource = BlogResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('blogs.create'), 403);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set admin_id to current admin if not set
        if (!isset($data['admin_id'])) {
            $data['admin_id'] = auth()->id();
        }

        // Set published_at if is_published is true and published_at is not set
        if (($data['is_published'] ?? false) && !isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}

