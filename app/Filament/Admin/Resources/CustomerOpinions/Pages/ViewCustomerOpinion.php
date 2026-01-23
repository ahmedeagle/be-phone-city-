<?php

namespace App\Filament\Admin\Resources\CustomerOpinions\Pages;

use App\Filament\Admin\Resources\CustomerOpinions\CustomerOpinionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerOpinion extends ViewRecord
{
    protected static string $resource = CustomerOpinionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

