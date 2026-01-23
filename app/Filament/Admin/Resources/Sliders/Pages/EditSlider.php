<?php

namespace App\Filament\Admin\Resources\Sliders\Pages;

use App\Filament\Admin\Resources\Sliders\SliderResource;
use Filament\Resources\Pages\EditRecord;

class EditSlider extends EditRecord
{
    protected static string $resource = SliderResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('sliders.update'), 403);
    }
}
