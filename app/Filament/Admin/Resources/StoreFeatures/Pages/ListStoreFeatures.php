<?php

namespace App\Filament\Admin\Resources\StoreFeatures\Pages;

use App\Filament\Admin\Resources\StoreFeatures\StoreFeatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreFeatures extends ListRecords
{
    protected static string $resource = StoreFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

