<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    /**
     * Determine whether the user can view any settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'super admin';
    }

    /**
     * Determine whether the user can view the setting.
     */
    public function view(User $user, Setting $setting): bool
    {
        return $user->role === 'super admin';
    }

    /**
     * Determine whether the user can create settings.
     */
    public function create(User $user): bool
    {
        return $user->role === 'super admin';
    }

    /**
     * Determine whether the user can update the setting.
     */
    public function update(User $user, Setting $setting): bool
    {
        return $user->role === 'super admin';
    }

    /**
     * Determine whether the user can delete the setting.
     */
    public function delete(User $user, Setting $setting): bool
    {
        return $user->role === 'super admin';
    }

    /**
     * Determine whether the user can clear cache.
     */
    public function clearCache(User $user): bool
    {
        return $user->role === 'super admin';
    }
}
