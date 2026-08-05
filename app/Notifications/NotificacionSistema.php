<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NotificacionSistema extends Notification
{
    use Queueable;

    public function __construct(
        public string $titulo,
        public string $cuerpo,
        public string $tipo = 'info',
        public ?string $url = null,
        public ?string $icono = null,
    ) {}

    public function via(object $notifiable): array
    {
        // Se persisten en BD (tabla notifications). La UI las consume vía
        // NotificationsController; no se usa broadcast (requiere Redis, que no
        // está disponible en el contenedor app).
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => $this->titulo,
            'cuerpo' => $this->cuerpo,
            'tipo' => $this->tipo,
            'url' => $this->url,
            'icono' => $this->icono ?? $this->iconoPorTipo(),
        ];
    }

    private function iconoPorTipo(): string
    {
        return match ($this->tipo) {
            'success' => 'pi pi-check-circle',
            'error' => 'pi pi-times-circle',
            'warning' => 'pi pi-exclamation-triangle',
            default => 'pi pi-info-circle',
        };
    }
}
