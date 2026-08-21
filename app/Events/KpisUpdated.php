<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KpisUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $kpis,
        public string $perfil = 'SUPERADMIN',
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('perfil.'.$this->perfil),
        ];
    }

    public function broadcastWith(): array
    {
        return ['kpis' => $this->kpis];
    }
}
