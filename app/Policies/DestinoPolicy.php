<?php

namespace App\Policies;

use App\Models\Destino;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DestinoPolicy
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
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Destino $destino): bool
    {
        // Un usuario puede ver un destino si está publicado,
        // o si es el dueño del destino (incluso si está en borrador o pendiente).
        return $destino->status === 'published' || $user->id === $destino->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo los proveedores y administradores pueden crear destinos.
        return $user->hasRole('provider') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Destino $destino): bool
    {
        // Solo el proveedor que es dueño del destino puede actualizarlo.
        // El admin ya tiene acceso por el método before().
        return $user->id === $destino->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Destino $destino): bool
    {
        // Solo el proveedor que es dueño del destino puede eliminarlo.
        return $user->id === $destino->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Destino $destino): bool
    {
        // Solo el dueño puede restaurar.
        return $user->id === $destino->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Destino $destino): bool
    {
        // Solo el dueño puede forzar la eliminación.
        return $user->id === $destino->user_id;
    }
}
