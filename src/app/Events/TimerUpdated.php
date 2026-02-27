<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimerUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $reunionId,
        public int $timerId,
        public string $estado,
        public ?string $inicioAt = null,
        public ?string $finAt = null
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
        return 'timer.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'reunion_id' => $this->reunionId,
            'timer_id' => $this->timerId,
            'estado' => $this->estado,
            'inicio_at' => $this->inicioAt,
            'fin_at' => $this->finAt,
        ];
    }
}
