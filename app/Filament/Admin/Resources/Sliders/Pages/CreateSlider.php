<?php

namespace App\Filament\Admin\Resources\Sliders\Pages;

use App\Filament\Admin\Resources\Sliders\SliderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSlider extends CreateRecord
{
    protected static string $resource = SliderResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('sliders.create'), 403);
    }
}
