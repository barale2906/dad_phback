<?php

namespace App\Jobs;

use App\Models\Asistente;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Reunion;
use App\Services\VotoService;
use App\Services\WhatsAppMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $messageId,
        public string $phone,
        public array $payload
    ) {
        $this->onQueue('whatsapp');
    }

    public function handle(
        WhatsAppMessageService $whatsappService,
        VotoService $votoService
    ): void {
        if ($whatsappService->isReplay($this->messageId)) {
            return;
        }

        if (! $whatsappService->checkRateLimit($this->phone)) {
            Log::info('WhatsApp: rate limit excedido', ['phone' => $this->phone]);
            return;
        }

        $text = trim((string) ($this->payload['text'] ?? ''));
        $comando = $whatsappService->interpretCommand($text);

        if ($comando === null) {
            return;
        }

        $phoneDigits = preg_replace('/\D/', '', $this->phone);
        if ($phoneDigits === '') {
            return;
        }

        // Primero localizar la reunión en curso — los asistentes son por reunión
        $reunion = Reunion::query()
            ->where('estado', 'en_curso')
            ->first();

        if (! $reunion) {
            Log::info('WhatsApp: no hay reunión en curso');
            return;
        }

        // Buscar el asistente SOLO dentro de la reunión activa (modelo efímero por reunión)
        $asistente = Asistente::query()
            ->where('reunion_id', $reunion->id)
            ->whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->get()
            ->first(function (Asistente $a) use ($phoneDigits): bool {
                $stored = preg_replace('/\D/', '', (string) $a->telefono);

                return $stored === $phoneDigits
                    || str_ends_with($stored, $phoneDigits)
                    || str_ends_with($phoneDigits, $stored);
            });

        if (! $asistente) {
            Log::info('WhatsApp: asistente no encontrado en la reunión en curso para teléfono', [
                'phone' => $this->phone,
                'reunion_id' => $reunion->id,
            ]);
            return;
        }

        if ($comando === 'presente') {
            $this->registrarPresencia($reunion, $asistente, $votoService);
            return;
        }

        if (in_array($comando, ['si', 'no'], true)) {
            $this->registrarVoto($reunion, $asistente, $comando, $votoService);
        }
    }

    private function registrarPresencia(Reunion $reunion, Asistente $asistente, VotoService $votoService): void
    {
        $pregunta = $reunion->preguntas()
            ->where('estado', 'abierta')
            ->whereHas('opciones', fn ($q) => $q->whereRaw("UPPER(texto) LIKE '%PRESENTE%'"))
            ->first();

        if (! $pregunta) {
            Log::info('WhatsApp: no hay pregunta de quórum abierta');
            return;
        }

        $opcion = $pregunta->opciones()->whereRaw("UPPER(texto) LIKE '%PRESENTE%'")->first();
        if (! $opcion) {
            return;
        }

        try {
            $votoService->registrarPorAsistente($pregunta, $opcion, $asistente, $this->phone);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp: error al registrar presencia', [
                'error' => $e->getMessage(),
                'asistente_id' => $asistente->id,
            ]);
        }
    }

    private function registrarVoto(Reunion $reunion, Asistente $asistente, string $comando, VotoService $votoService): void
    {
        $pregunta = $reunion->preguntas()->where('estado', 'abierta')->first();

        if (! $pregunta) {
            Log::info('WhatsApp: no hay pregunta abierta para votar');
            return;
        }

        $opcion = $pregunta->opciones()
            ->get()
            ->first(fn (Opcion $o) => $this->opcionCoincideConComando($o->texto, $comando));

        if (! $opcion) {
            Log::info('WhatsApp: ninguna opción coincide con comando', ['comando' => $comando]);
            return;
        }

        try {
            $votoService->registrarPorAsistente($pregunta, $opcion, $asistente, $this->phone);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp: error al registrar voto', [
                'error' => $e->getMessage(),
                'asistente_id' => $asistente->id,
            ]);
        }
    }

    private function opcionCoincideConComando(string $textoOpcion, string $comando): bool
    {
        $t = strtoupper(trim($textoOpcion));

        return $comando === 'si' && (str_contains($t, 'SI') || str_contains($t, 'SÍ') || $t === 'S')
            || $comando === 'no' && (str_contains($t, 'NO') || $t === 'N');
    }
}
