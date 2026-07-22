<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NotificacionSistema extends Notification implements ShouldBroadcast
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
        return ['database', 'broadcast'];
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

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function broadcastType(): string
    {
        return 'notificacion.sistema';
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
