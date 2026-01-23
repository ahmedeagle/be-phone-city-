<?php

namespace App\Filament\Admin\Resources\CustomerOpinions\Pages;

use App\Filament\Admin\Resources\CustomerOpinions\CustomerOpinionResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomerOpinion extends EditRecord
{
    protected static string $resource = CustomerOpinionResource::class;
}

