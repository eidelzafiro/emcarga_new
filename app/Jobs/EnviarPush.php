<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Services\Push\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envía una notificación push a todos los dispositivos de un usuario.
 *
 * Corre en la cola `database` (no hay Redis en la app). Es best-effort: los
 * fallos del proveedor no revierten la notificación in-app ya persistida.
 */
class EnviarPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string,mixed>  $data
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $titulo,
        public readonly string $cuerpo,
        public readonly array $data = [],
    ) {}

    public function handle(PushSender $sender): void
    {
        DeviceToken::query()
            ->where('user_id', $this->userId)
            ->get()
            ->each(function (DeviceToken $dispositivo) use ($sender) {
                $enviado = $sender->send($dispositivo->token, $this->titulo, $this->cuerpo, $this->data);

                if ($enviado) {
                    $dispositivo->forceFill(['last_used_at' => now()])->save();
                }
            });
    }
}
