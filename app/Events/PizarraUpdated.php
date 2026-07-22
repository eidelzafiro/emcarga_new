<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PizarraUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $registros,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('pizarra'),
        ];
    }

    public function broadcastWith(): array
    {
        return ['registros' => $this->registros];
    }
}
