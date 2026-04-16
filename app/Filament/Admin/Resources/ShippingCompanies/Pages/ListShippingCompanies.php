<?php

namespace App\Filament\Admin\Resources\ShippingCompanies\Pages;

use App\Filament\Admin\Resources\ShippingCompanies\ShippingCompanyResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListShippingCompanies extends ListRecords
{
    protected static string $resource = ShippingCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
