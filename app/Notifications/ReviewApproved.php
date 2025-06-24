<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $review;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review)
    {
        $this->review = $review;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $destino = $this->review->destino;
        
        return (new MailMessage)
            ->subject('¡Tu reseña ha sido aprobada!')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Nos complace informarte que tu reseña ha sido aprobada y ya está visible en nuestra plataforma.')
            ->line('Destino: ' . $destino->nombre)
            ->line('Tu calificación: ' . $this->review->rating . '/5 estrellas')
            ->line('Comentario: "' . $this->review->comment . '"')
            ->action('Ver el destino', url('/destinos/' . $destino->slug))
            ->line('Gracias por compartir tu experiencia con otros viajeros.')
            ->salutation('Saludos, El equipo de Vive Hidalgo');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_approved',
            'review_id' => $this->review->id,
            'destino_id' => $this->review->destino_id,
            'destino_nombre' => $this->review->destino->nombre,
            'rating' => $this->review->rating,
            'comment' => $this->review->comment,
            'message' => 'Tu reseña para "' . $this->review->destino->nombre . '" ha sido aprobada.',
        ];
    }
} 