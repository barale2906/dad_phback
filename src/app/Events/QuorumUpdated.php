<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuorumUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $reunionId,
        public int $totalUnidades,
        public int $unidadesPresentes,
        public float $totalCoeficiente,
        public float $coeficientePresente,
        public float $porcentajeUnidades,
        public float $porcentajeCoeficiente
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
        return 'quorum.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'reunion_id' => $this->reunionId,
            'total_unidades' => $this->totalUnidades,
            'unidades_presentes' => $this->unidadesPresentes,
            'total_coeficiente' => $this->totalCoeficiente,
            'coeficiente_presente' => $this->coeficientePresente,
            'porcentaje_unidades' => $this->porcentajeUnidades,
            'porcentaje_coeficiente' => $this->porcentajeCoeficiente,
        ];
    }
}
