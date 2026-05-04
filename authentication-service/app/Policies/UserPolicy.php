<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function view(?User $user): bool
    {
        return $user && $user->hasPermission('users.list');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        return $user && $user->hasPermission('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function edit(?User $user): bool
    {
        return $user && $user->hasPermission('users.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user): bool
    {
        return $user && $user->hasPermission('users.delete');
    }
    
    /**
     * Determine whether the user can view own auth logs.
     */
    public function viewAuthLogs(?User $user): bool
    {
        // Users can view their own auth logs
        return $user !== null;
    }
    
    /**
     * Determine whether the user can view any user's auth logs.
     */
    public function viewAnyAuthLogs(?User $user): bool
    {
        return $user && ($user->hasRole('admin') || $user->hasPermission('users.view_logs'));
    }
    
    /**
     * Determine whether the user can view roles.
     */
    public function viewRoles(?User $user): bool
    {
        return $user && $user->hasPermission('roles.view');
    }
    
    /**
     * Determine whether the user can manage roles.
     */
    public function manageRoles(?User $user): bool
    {
        return $user && $user->hasPermission('roles.manage');
    }
}