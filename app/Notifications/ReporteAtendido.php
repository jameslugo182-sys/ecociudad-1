<?php

namespace App\Notifications;

use App\Models\Reporte;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReporteAtendido extends Notification
{
    use Queueable;

    public function __construct(private readonly Reporte $reporte) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("EcoCiudad: reporte #{$this->reporte->id} atendido")
            ->greeting("Hola, {$notifiable->name}")
            ->line('El área de limpieza finalizó la atención de tu reporte.')
            ->line($this->reporte->descripcion)
            ->action('Consultar mi reporte', route('reportes.index'))
            ->line('Gracias por contribuir con una ciudad más limpia.');
    }
}
