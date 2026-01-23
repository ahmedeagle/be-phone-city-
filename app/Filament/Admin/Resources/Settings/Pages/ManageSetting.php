<?php

namespace App\Filament\Admin\Resources\Settings\Pages;

use App\Filament\Admin\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ManageSetting extends ViewRecord
{
    protected static string $resource = SettingResource::class;

    public function mount(int | string | null $record = null): void
    {
        $setting = Setting::getSettings();

        parent::mount($setting->id);
    }

    protected function authorizeAccess(): void
    {
        abort_unless(auth()->user()->can('settings.show'), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل')
                ->visible(fn () => auth()->user()->can('settings.update')),
        ];
    }
}
