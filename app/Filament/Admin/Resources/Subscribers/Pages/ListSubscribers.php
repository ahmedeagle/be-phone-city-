<?php

namespace App\Filament\Admin\Resources\Subscribers\Pages;

use App\Filament\Admin\Resources\Subscribers\SubscriberResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;
}
