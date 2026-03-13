<?php

namespace App\Jobs;

use App\Models\Pregunta;
use App\Services\WhatsAppConversationService;
use App\Jobs\EnviarPreguntaWhatsAppJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Cuando se abre una pregunta de tipo VOTACION, este job:
 *   1. Construye el mensaje con la pregunta y sus opciones numeradas.
 *   2. Registra la votación activa en Redis para que los teléfonos puedan votar.
 *   3. Hace fan-out: despacha un EnviarPreguntaWhatsAppJob por cada asistente
 *      con teléfono registrado en la reunión.
 *
 * Los envíos van a la cola "default" (prioridad baja) para que los votos
 * entrantes —cola "whatsapp"— siempre se procesen primero.
 */
class BroadcastVotacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $preguntaId)
    {
        $this->onQueue('default');
    }

    public function handle(WhatsAppConversationService $conversationService): void
    {
        $pregunta = Pregunta::query()
            ->with('opciones')
            ->find($this->preguntaId);

        if (! $pregunta || $pregunta->tipo !== 'VOTACION' || $pregunta->estado !== 'abierta') {
            return;
        }

        $opciones = $pregunta->opciones()->orderBy('orden')->get();

        if ($opciones->isEmpty()) {
            Log::warning('[WA-Broadcast] Pregunta sin opciones, no se puede difundir.', [
                'pregunta_id' => $this->preguntaId,
            ]);

            return;
        }

        // Estructura de opciones numeradas para Redis y para el mensaje
        $opcionesData = $opciones->values()->map(fn ($o, $i) => [
            'numero' => $i + 1,
            'id'     => $o->id,
            'texto'  => $o->texto,
        ])->all();

        // Guardar votación activa en Redis para que ProcessWhatsAppMessageJob la consulte
        $conversationService->setActiveVote(
            $pregunta->reunion_id,
            $pregunta->id,
            $pregunta->pregunta,
            $opcionesData
        );

        // Fan-out: un job por asistente con teléfono registrado
        $asistentes = $pregunta->reunion
            ->asistentes()
            ->whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->get();

        // Limpiar sesiones de registro activas: si un residente quedó a mitad del
        // flujo de asistencia cuando se abre la votación, su sesión se descarta
        // para que pueda votar directamente al responder el mensaje de broadcast.
        foreach ($asistentes as $asistente) {
            $conversationService->clearSession((string) $asistente->telefono);
        }

        // Construir el mensaje que recibirán los asistentes
        $lista = '';
        foreach ($opcionesData as $op) {
            $lista .= "{$op['numero']}. {$op['texto']}\n";
        }

        $totalOpciones  = count($opcionesData);
        $opcionesValidas = implode(', ', range(1, $totalOpciones));

        $mensaje = "📋 *Nueva votación*\n\n{$pregunta->pregunta}\n\n{$lista}\nResponde con *{$opcionesValidas}*.";

        foreach ($asistentes as $asistente) {
            EnviarPreguntaWhatsAppJob::dispatch($asistente->telefono, $mensaje);
        }

        Log::info('[WA-Broadcast] Difusión de votación encolada.', [
            'pregunta_id'    => $this->preguntaId,
            'destinatarios'  => $asistentes->count(),
        ]);
    }
}
