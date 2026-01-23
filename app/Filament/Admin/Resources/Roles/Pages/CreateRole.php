<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = 'admin';
        // Remove permissions from data as it's handled separately via relationship
        unset($data['permissions']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $permissions = $this->form->getState()['permissions'] ?? [];
        if (!empty($permissions)) {
            $this->record->syncPermissions($permissions);
        }
    }
}

