<?php

namespace App\Filament\Admin\Resources\About\Pages;

use App\Filament\Admin\Resources\About\AboutResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAbout extends ViewRecord
{
    protected static string $resource = AboutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            // No DeleteAction - deletion is not allowed
        ];
    }
}

