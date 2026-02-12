<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements FilamentUser
{
    use Notifiable, HasRoles;

    /**
     * Force Arabic locale for all notifications/emails.
     */
    public function preferredLocale(): string
    {
        return 'ar';
    }

    protected $table = 'admins';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $guard_name = 'admin';

    public function canAccessPanel(Panel $panel): bool
    {
        return true; // أو أضف شرط مثل $this->is_superadmin
    }
}
