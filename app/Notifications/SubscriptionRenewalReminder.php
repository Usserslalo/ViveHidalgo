<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subscription;
    protected $daysUntilRenewal;

    public function __construct(Subscription $subscription, int $daysUntilRenewal = 7)
    {
        $this->subscription = $subscription;
        $this->daysUntilRenewal = $daysUntilRenewal;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = number_format($this->subscription->amount, 2);
        $currency = strtoupper($this->subscription->currency);
        $renewalDate = $this->subscription->current_period_end->format('d/m/Y');

        return (new MailMessage)
            ->subject('Recordatorio de Renovación - ViveHidalgo')
            ->greeting('Hola ' . $notifiable->name)
            ->line("Tu suscripción se renovará automáticamente en {$this->daysUntilRenewal} días.")
            ->line("**Plan:** " . ucfirst($this->subscription->plan_type))
            ->line("**Monto:** {$currency} {$amount}")
            ->line("**Fecha de renovación:** {$renewalDate}")
            ->action('Gestionar Suscripción', url('/dashboard/subscription'))
            ->line('Si deseas cancelar o modificar tu suscripción, hazlo antes de la fecha de renovación.')
            ->line('Gracias por ser parte de ViveHidalgo.')
            ->salutation('Saludos, El equipo de ViveHidalgo');
    }

    public function toArray($notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_type' => $this->subscription->plan_type,
            'amount' => $this->subscription->amount,
            'currency' => $this->subscription->currency,
            'renewal_date' => $this->subscription->current_period_end,
            'days_until_renewal' => $this->daysUntilRenewal,
        ];
    }
} 