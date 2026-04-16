<?php

namespace App\Filament\Admin\Resources\PointsTiers\Pages;

use App\Filament\Admin\Resources\PointsTiers\PointsTierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePointsTiers extends ManageRecords
{
    protected static string $resource = PointsTierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}