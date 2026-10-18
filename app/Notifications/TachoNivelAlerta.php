<?php

namespace App\Notifications;

use App\Models\Tacho;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TachoNivelAlerta extends Notification
{
    use Queueable;

    public function __construct(private readonly Tacho $tacho) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $nivel = $this->tacho->nivel_porcentaje ?? 0;

        return (new MailMessage)
            ->subject("EcoCiudad: tacho {$this->tacho->codigo} al {$nivel}%")
            ->greeting("Hola, {$notifiable->name}")
            ->line("El contenedor IoT {$this->tacho->codigo} ({$this->tacho->nombre}) superó el umbral de {$this->tacho->umbral_alerta}%.")
            ->line("Nivel actual: {$nivel}%. Capacidad restante: {$this->tacho->capacidadRestante()}%.")
            ->action('Ver tachos IoT', route('administracion.tachos.index'))
            ->line('Puedes incorporarlo a una ruta de recolección ya planificada.');
    }
}
