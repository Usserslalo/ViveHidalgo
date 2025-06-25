<?php

namespace App\Policies;

use App\Models\HomeConfig;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HomeConfigPolicy
{
    /**
     * Perform pre-authorization checks.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ability
     * @return bool|void
     */
    public function before(User $user, string $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Cualquier usuario puede ver la configuración de la página de inicio
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, HomeConfig $homeConfig): bool
    {
        return true; // Cualquier usuario puede ver la configuración
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HomeConfig $homeConfig): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HomeConfig $homeConfig): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, HomeConfig $homeConfig): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, HomeConfig $homeConfig): bool
    {
        return $user->hasRole('admin');
    }
} 