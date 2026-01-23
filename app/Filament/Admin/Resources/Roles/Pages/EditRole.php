<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => $this->record->name !== 'owner')
                ->requiresConfirmation()
                ->action(function () {
                    if ($this->record->name === 'owner') {
                        Notification::make()
                            ->danger()
                            ->title('خطأ')
                            ->body('لا يمكن حذف دور المالك')
                            ->send();
                        return;
                    }
                    $this->record->delete();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = $this->record->permissions->pluck('id')->toArray();
        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->name === 'owner') {
            // Owner role should have all permissions
            $allPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'admin')->pluck('id');
            $this->record->syncPermissions($allPermissions);
        } else {
            $permissions = $this->form->getState()['permissions'] ?? [];
            $this->record->syncPermissions($permissions);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove permissions from data as it's handled separately via relationship
        unset($data['permissions']);
        return $data;
    }

    protected function beforeSave(): void
    {
        if ($this->record->name === 'owner') {
            Notification::make()
                ->warning()
                ->title('تحذير')
                ->body('لا يمكن تعديل دور المالك')
                ->send();
            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

