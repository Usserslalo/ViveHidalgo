<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use App\Models\Destino;

class ReviewPolicy
{
    /**
     * Determine whether the user can create a review.
     */
    public function create(User $user, ?Destino $destino = null): bool
    {
        // Si no se proporciona un destino específico, permitir acceso general
        if (!$destino) {
            return true;
        }

        // Verificar que el usuario no haya reseñado este destino antes
        if ($destino->reviews()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Verificar que el usuario tenga el destino en favoritos
        return $user->favoritos()->where('destino_id', $destino->id)->exists();
    }

    /**
     * Determine whether the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        // Solo el autor de la reseña puede editarla
        return $user->id === $review->user_id;
    }

    /**
     * Determine whether the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        // Solo el autor de la reseña puede eliminarla
        return $user->id === $review->user_id;
    }

    /**
     * Determine whether the user can view the review.
     */
    public function view(User $user, Review $review): bool
    {
        // Cualquier usuario autenticado puede ver reseñas aprobadas
        return $review->is_approved;
    }
} 