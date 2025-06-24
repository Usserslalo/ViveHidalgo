<?php

namespace App\Notifications;

use App\Models\Promocion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotionExpired extends Notification implements ShouldQueue
{
    use Queueable;

    public $promocion;

    /**
     * Create a new notification instance.
     */
    public function __construct(Promocion $promocion)
    {
        $this->promocion = $promocion;
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
        $destino = $this->promocion->destino;
        
        return (new MailMessage)
            ->subject('Promoción expirada: ' . $this->promocion->titulo)
            ->greeting('Hola ' . $notifiable->name)
            ->line('Te informamos que la siguiente promoción ha expirado y ha sido desactivada automáticamente:')
            ->line('Promoción: ' . $this->promocion->titulo)
            ->line('Destino: ' . $destino->nombre)
            ->line('Fecha de expiración: ' . $this->promocion->end_date->format('d/m/Y H:i'))
            ->line('Descuento: ' . $this->promocion->descuento . '%')
            ->action('Ver el destino', url('/destinos/' . $destino->slug))
            ->line('Puedes crear una nueva promoción cuando lo consideres necesario.')
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
            'type' => 'promotion_expired',
            'promocion_id' => $this->promocion->id,
            'destino_id' => $this->promocion->destino_id,
            'destino_nombre' => $this->promocion->destino->nombre,
            'promocion_titulo' => $this->promocion->titulo,
            'fecha_expiracion' => $this->promocion->end_date,
            'message' => 'La promoción "' . $this->promocion->titulo . '" ha expirado.',
        ];
    }
} 