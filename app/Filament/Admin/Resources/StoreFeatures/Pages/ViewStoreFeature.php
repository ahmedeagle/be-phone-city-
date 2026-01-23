<?php

namespace App\Filament\Admin\Resources\StoreFeatures\Pages;

use App\Filament\Admin\Resources\StoreFeatures\StoreFeatureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreFeature extends ViewRecord
{
    protected static string $resource = StoreFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

