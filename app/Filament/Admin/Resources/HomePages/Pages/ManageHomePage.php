<?php

namespace App\Filament\Admin\Resources\HomePages\Pages;

use App\Filament\Admin\Resources\HomePages\HomePageResource;
use App\Models\HomePage;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ManageHomePage extends ViewRecord
{
    protected static string $resource = HomePageResource::class;

    public function mount(int | string | null $record = null): void
    {
        $homePage = HomePage::first();

        if (!$homePage) {
            // Redirect to create if no record exists
            $this->redirect(static::getResource()::getUrl('create'));
            return;
        }

        parent::mount($homePage->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
