<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessful extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
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
            ->subject('¡Pago Exitoso! - ViveHidalgo')
            ->greeting('¡Hola ' . $notifiable->name . '!')
            ->line('Tu pago ha sido procesado exitosamente.')
            ->line("**Monto:** {$currency} {$amount}")
            ->line("**Fecha:** " . $this->invoice->paid_at->format('d/m/Y H:i'))
            ->line("**Número de factura:** #" . $this->invoice->id)
            ->action('Ver Factura', url('/dashboard/invoices/' . $this->invoice->id))
            ->line('Gracias por confiar en ViveHidalgo.')
            ->salutation('Saludos, El equipo de ViveHidalgo');
    }

    public function toArray($notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'amount' => $this->invoice->amount,
            'currency' => $this->invoice->currency,
            'paid_at' => $this->invoice->paid_at,
        ];
    }
} 