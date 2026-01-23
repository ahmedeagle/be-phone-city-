<?php

namespace App\Filament\Admin\Resources\Admins\Pages;

use App\Filament\Admin\Resources\Admins\AdminResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove roles from data as it's handled separately via relationship
        unset($data['roles']);
        
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $roles = $this->form->getState()['roles'] ?? [];
        if (!empty($roles)) {
            $this->record->syncRoles($roles);
        }
    }
}

