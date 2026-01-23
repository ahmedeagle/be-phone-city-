<?php

namespace App\Filament\Admin\Resources\HomePages\Pages;

use App\Filament\Admin\Resources\HomePages\HomePageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHomePage extends ViewRecord
{
    protected static string $resource = HomePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
