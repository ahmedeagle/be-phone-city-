<?php

namespace App\Filament\Admin\Resources\ShippingCompanies\Pages;

use App\Filament\Admin\Resources\ShippingCompanies\ShippingCompanyResource;
use Filament\Resources\Pages\EditRecord;

class EditShippingCompany extends EditRecord
{
    protected static string $resource = ShippingCompanyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
