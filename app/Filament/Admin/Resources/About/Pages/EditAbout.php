<?php

namespace App\Filament\Admin\Resources\About\Pages;

use App\Filament\Admin\Resources\About\AboutResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAbout extends EditRecord
{
    protected static string $resource = AboutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            // No DeleteAction - deletion is not allowed
        ];
    }
}
