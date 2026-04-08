<?php

namespace App\Filament\Admin\Resources\VipTiers\Pages;

use App\Filament\Admin\Resources\VipTiers\VipTierResource;
use App\Services\VipTierService;
use Filament\Resources\Pages\ManageRecords;

class ManageVipTiers extends ManageRecords
{
    protected static string $resource = VipTierResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function afterCreate(): void
    {
        VipTierService::clearCache();
    }

    protected function afterSave(): void
    {
        VipTierService::clearCache();
    }

    protected function afterDelete(): void
    {
        VipTierService::clearCache();
    }
}
