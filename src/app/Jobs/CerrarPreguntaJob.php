<?php

namespace App\Jobs;

use App\Models\Pregunta;
use App\Services\PreguntaService;
use App\Services\WhatsAppConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CerrarPreguntaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $preguntaId)
    {
    }

    public function handle(
        PreguntaService $preguntaService,
        WhatsAppConversationService $conversationService
    ): void {
        $pregunta = Pregunta::query()->find($this->preguntaId);
        if (! $pregunta) {
            return;
        }

        $preguntaService->cerrar($pregunta);

        // Limpiar la votación activa en Redis para que el teléfono no siga aceptando votos
        if ($pregunta->tipo === 'VOTACION') {
            $conversationService->clearActiveVote($pregunta->reunion_id);
        }
    }
}
