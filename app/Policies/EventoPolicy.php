<?php

namespace App\Policies;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventoPolicy
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
        return true; // Cualquier usuario puede ver la lista de eventos
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Evento $evento): bool
    {
        // Usuario puede ver si el evento está publicado o si es el propietario
        return $evento->status === 'published' || $user->id === $evento->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('provider') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Evento $evento): bool
    {
        // Solo el propietario del evento puede actualizarlo
        return $user->id === $evento->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Evento $evento): bool
    {
        // Solo el propietario del evento puede eliminarlo
        return $user->id === $evento->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Evento $evento): bool
    {
        // Solo el propietario puede restaurar
        return $user->id === $evento->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Evento $evento): bool
    {
        // Solo el propietario puede forzar la eliminación
        return $user->id === $evento->user_id;
    }
} 