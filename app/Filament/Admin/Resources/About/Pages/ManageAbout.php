<?php

namespace App\Filament\Admin\Resources\About\Pages;

use App\Filament\Admin\Resources\About\AboutResource;
use App\Models\About;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ManageAbout extends ViewRecord
{
    protected static string $resource = AboutResource::class;

    public function mount(int | string | null $record = null): void
    {
        $about = About::first();

        if (!$about) {
            // Redirect to create if no record exists
            $this->redirect(static::getResource()::getUrl('create'));
            return;
        }

        parent::mount($about->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
