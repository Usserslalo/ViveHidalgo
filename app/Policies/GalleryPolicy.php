<?php

namespace App\Policies;

use App\Models\Destino;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GalleryPolicy
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
     * Determine whether the user can view any galleries.
     */
    public function viewAny(User $user): bool
    {
        return true; // Cualquier usuario puede ver galerías de destinos públicos
    }

    /**
     * Determine whether the user can view the gallery of a specific destino.
     */
    public function view(User $user, Destino $destino): bool
    {
        // Usuario puede ver si el destino está publicado o si es el propietario
        return $destino->status === 'published' || $user->id === $destino->user_id;
    }

    /**
     * Determine whether the user can manage the gallery of a destino.
     * This includes uploading, deleting, reordering, and setting main images.
     */
    public function manage(User $user, Destino $destino): bool
    {
        // Solo el propietario del destino puede gestionar su galería
        return $user->id === $destino->user_id;
    }

    /**
     * Determine whether the user can upload images to the gallery.
     */
    public function upload(User $user, Destino $destino): bool
    {
        return $this->manage($user, $destino);
    }

    /**
     * Determine whether the user can delete images from the gallery.
     */
    public function deleteImage(User $user, Destino $destino): bool
    {
        return $this->manage($user, $destino);
    }

    /**
     * Determine whether the user can reorder images in the gallery.
     */
    public function reorder(User $user, Destino $destino): bool
    {
        return $this->manage($user, $destino);
    }

    /**
     * Determine whether the user can set the main image of the gallery.
     */
    public function setMain(User $user, Destino $destino): bool
    {
        return $this->manage($user, $destino);
    }
} 