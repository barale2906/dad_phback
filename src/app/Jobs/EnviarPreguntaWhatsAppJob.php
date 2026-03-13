<?php

namespace App\Jobs;

use App\Services\WhatsAppSenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envía un mensaje de votación a un único número de teléfono.
 * Producido por BroadcastVotacionJob vía fan-out.
 * Usa la cola "default" (prioridad baja) para ceder paso a los votos entrantes.
 */
class EnviarPreguntaWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $telefono,
        private readonly string $mensaje
    ) {
        $this->onQueue('default');
    }

    public function handle(WhatsAppSenderService $senderService): void
    {
        $senderService->sendText($this->telefono, $this->mensaje);
    }
}
