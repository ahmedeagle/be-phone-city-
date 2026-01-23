<?php

namespace App\Filament\Admin\Resources\About\Pages;

use App\Filament\Admin\Resources\About\AboutResource;
use App\Models\About;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAbout extends CreateRecord
{
    protected static string $resource = AboutResource::class;

    protected function beforeCreate(): void
    {
        // Check if about record already exists
        $existing = About::first();
        if ($existing) {
            Notification::make()
                ->title('خطأ')
                ->body('يوجد سجل واحد فقط. يمكنك تعديل السجل الموجود.')
                ->danger()
                ->send();
            
            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}

