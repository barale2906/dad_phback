<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteRegistered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $reunionId,
        public int $preguntaId,
        public int $inmuebleId,
        public float $coeficiente
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('reunion.'.$this->reunionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'vote.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'reunion_id' => $this->reunionId,
            'pregunta_id' => $this->preguntaId,
            'inmueble_id' => $this->inmuebleId,
            'coeficiente' => $this->coeficiente,
        ];
    }
}
