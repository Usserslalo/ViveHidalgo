<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;
    protected $reason;

    public function __construct(Invoice $invoice, string $reason = 'Error en el procesamiento del pago')
    {
        $this->invoice = $invoice;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = number_format($this->invoice->amount, 2);
        $currency = strtoupper($this->invoice->currency);

        return (new MailMessage)
            ->subject('Pago Fallido - ViveHidalgo')
            ->greeting('Hola ' . $notifiable->name)
            ->error()
            ->line('Tu pago no pudo ser procesado.')
            ->line("**Monto:** {$currency} {$amount}")
            ->line("**Razón:** {$this->reason}")
            ->line("**Número de factura:** #" . $this->invoice->id)
            ->action('Actualizar Método de Pago', url('/dashboard/payment-methods'))
            ->line('Por favor, verifica tu método de pago e intenta nuevamente.')
            ->line('Si el problema persiste, contacta a nuestro soporte.')
            ->salutation('Saludos, El equipo de ViveHidalgo');
    }

    public function toArray($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->amount,
            'currency' => $this->invoice->currency,
            'reason' => $this->reason,
        ];
    }
} 