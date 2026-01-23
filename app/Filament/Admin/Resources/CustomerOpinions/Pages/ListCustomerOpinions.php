<?php

namespace App\Filament\Admin\Resources\CustomerOpinions\Pages;

use App\Filament\Admin\Resources\CustomerOpinions\CustomerOpinionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerOpinions extends ListRecords
{
    protected static string $resource = CustomerOpinionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

