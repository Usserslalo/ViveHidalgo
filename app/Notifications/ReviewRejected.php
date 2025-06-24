<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public $review;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review, string $reason = null)
    {
        $this->review = $review;
        $this->reason = $reason;
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
        
        $message = (new MailMessage)
            ->subject('Tu reseña requiere revisión')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Lamentamos informarte que tu reseña no pudo ser publicada en este momento.')
            ->line('Destino: ' . $destino->nombre)
            ->line('Tu calificación: ' . $this->review->rating . '/5 estrellas');

        if ($this->reason) {
            $message->line('Motivo: ' . $this->reason);
        } else {
            $message->line('Motivo: No cumple con nuestras políticas de contenido.');
        }

        $message->line('Puedes enviar una nueva reseña que cumpla con nuestras directrices.')
            ->action('Ver el destino', url('/destinos/' . $destino->slug))
            ->line('Gracias por tu comprensión.')
            ->salutation('Saludos, El equipo de Vive Hidalgo');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_rejected',
            'review_id' => $this->review->id,
            'destino_id' => $this->review->destino_id,
            'destino_nombre' => $this->review->destino->nombre,
            'rating' => $this->review->rating,
            'reason' => $this->reason,
            'message' => 'Tu reseña para "' . $this->review->destino->nombre . '" no pudo ser publicada.',
        ];
    }
} 