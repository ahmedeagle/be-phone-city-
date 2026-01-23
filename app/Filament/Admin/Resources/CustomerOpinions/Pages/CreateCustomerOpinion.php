<?php

namespace App\Filament\Admin\Resources\CustomerOpinions\Pages;

use App\Filament\Admin\Resources\CustomerOpinions\CustomerOpinionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerOpinion extends CreateRecord
{
    protected static string $resource = CustomerOpinionResource::class;
}

