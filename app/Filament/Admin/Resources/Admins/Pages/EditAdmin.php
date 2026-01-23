<?php

namespace App\Filament\Admin\Resources\Admins\Pages;

use App\Filament\Admin\Resources\Admins\AdminResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(function () {
                    // Check if user has delete permission
                    if (!auth('admin')->check() || !auth('admin')->user()->can('admins.delete')) {
                        return false;
                    }
                    // Only hide delete if this is the last admin with owner role
                    if ($this->record->hasRole('owner')) {
                        $ownersCount = \App\Models\Admin::role('owner')->count();
                        return $ownersCount > 1;
                    }
                    return true;
                })
                ->requiresConfirmation()
                ->action(function () {
                    // Check permission before allowing delete
                    if (!auth('admin')->check() || !auth('admin')->user()->can('admins.delete')) {
                        Notification::make()
                            ->danger()
                            ->title('خطأ')
                            ->body('ليس لديك صلاحية لحذف المدراء')
                            ->send();
                        return;
                    }

                    if ($this->record->hasRole('owner')) {
                        $ownersCount = \App\Models\Admin::role('owner')->count();
                        if ($ownersCount === 1) {
                            Notification::make()
                                ->danger()
                                ->title('خطأ')
                                ->body('لا يمكن حذف المدير الوحيد الذي لديه دور المالك')
                                ->send();
                            return;
                        }
                    }
                    $this->record->delete();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['roles'] = $this->record->roles->pluck('id')->toArray();
        // Don't fill password field
        unset($data['password']);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove roles from data as it's handled separately via relationship
        unset($data['roles']);
        
        // Only hash password if it's provided and not empty
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        } elseif (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        $roles = $this->form->getState()['roles'] ?? [];
        $this->record->syncRoles($roles);
    }

    protected function beforeSave(): void
    {
        // Only prevent removing owner role if this is the LAST admin with owner role
        if ($this->record->hasRole('owner')) {
            $ownersCount = \App\Models\Admin::role('owner')->count();
            
            // If this is the only admin with owner role, prevent removing it
            if ($ownersCount === 1) {
                $roles = $this->form->getState()['roles'] ?? [];
                $ownerRole = Role::where('name', 'owner')->where('guard_name', 'admin')->first();
                
                if ($ownerRole && !in_array($ownerRole->id, $roles)) {
                    Notification::make()
                        ->warning()
                        ->title('تحذير')
                        ->body('لا يمكن إزالة دور المالك - هذا المدير هو الوحيد الذي لديه دور المالك')
                        ->send();
                    $this->halt();
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

