<?php

namespace App\Jobs;

use App\Models\Inmueble;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Reunion;
use App\Services\AsistenteService;
use App\Services\VotoService;
use App\Services\WhatsAppConversationService;
use App\Services\WhatsAppMessageService;
use App\Services\WhatsAppSenderService;
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
        WhatsAppConversationService $conversationService,
        WhatsAppSenderService $senderService,
        AsistenteService $asistenteService,
        VotoService $votoService
    ): void {
        if ($whatsappService->isReplay($this->messageId)) {
            return;
        }

        if (! $whatsappService->checkRateLimit($this->phone)) {
            Log::info('[WA] Rate limit excedido.', ['phone' => $this->phone]);

            return;
        }

        $text    = trim((string) ($this->payload['text'] ?? ''));
        $phone   = $this->phone;
        $session = $conversationService->getSession($phone);

        // ── 1. Sesión activa: continuar flujo de registro de asistencia ───────
        if ($session !== null) {
            match ($session['step']) {
                'waiting_inmueble' => $this->handleWaitingInmueble(
                    $phone, $text, $session, $conversationService, $senderService
                ),
                'waiting_name' => $this->handleWaitingName(
                    $phone, $text, $session, $conversationService, $senderService, $asistenteService
                ),
                default => null,
            };

            return;
        }

        $reunion = Reunion::query()->where('estado', 'en_curso')->first();

        if (! $reunion) {
            return; // Silencio cuando no hay asamblea activa
        }

        // ── 2. Voto numérico (1, 2, 3…) si hay una votación activa ───────────
        $activeVote = $conversationService->getActiveVote($reunion->id);
        $seleccion  = ctype_digit($text) ? (int) $text : 0;

        if ($activeVote !== null && $seleccion > 0) {
            $this->handleVotoNumerico($phone, $seleccion, $activeVote, $reunion, $senderService, $votoService);

            return;
        }

        // ── 3. Comando SI / NO (compatibilidad hacia atrás) ───────────────────
        $comando = $whatsappService->interpretCommand($text);

        if ($comando !== null && in_array($comando, ['si', 'no'], true)) {
            $this->handleVoto($phone, $comando, $votoService, $senderService);

            return;
        }

        // ── 4. Cualquier otro mensaje: iniciar flujo de registro ──────────────
        $conversationService->startSession($phone, $reunion->id);
        $senderService->sendText(
            $phone,
            "¡Hola! Para registrar tu asistencia a la asamblea envía el código de tu inmueble.\n\nEjemplo: *1101*"
        );
    }

    // ── Paso 1: validar nomenclatura del inmueble ──────────────────────────────

    private function handleWaitingInmueble(
        string $phone,
        string $text,
        array $session,
        WhatsAppConversationService $conversationService,
        WhatsAppSenderService $senderService
    ): void {
        $nomenclatura = strtoupper(trim($text));

        $inmueble = Inmueble::query()
            ->whereRaw('UPPER(nomenclatura) = ?', [$nomenclatura])
            ->where('activo', true)
            ->first();

        if (! $inmueble) {
            $senderService->sendText(
                $phone,
                "No encontré ningún inmueble con el código *{$text}*.\nVerifica el código e intenta de nuevo."
            );

            return; // Mantiene el paso waiting_inmueble
        }

        // Nombres candidatos: el correcto + hasta 2 al azar de otros inmuebles
        $nombreCorrecto    = $inmueble->propietario_nombre;
        $nombresAleatorios = Inmueble::query()
            ->whereNotNull('propietario_nombre')
            ->where('propietario_nombre', '!=', '')
            ->where('id', '!=', $inmueble->id)
            ->inRandomOrder()
            ->limit(2)
            ->pluck('propietario_nombre')
            ->toArray();

        $nombres = array_merge([$nombreCorrecto], $nombresAleatorios);
        shuffle($nombres);

        /** @var int $opcionCorrecta posición 1-indexed del nombre correcto tras mezclar */
        $opcionCorrecta = (int) array_search($nombreCorrecto, $nombres, true) + 1;

        $conversationService->advanceToWaitingName($phone, $inmueble->id, $nombres, $opcionCorrecta);

        $lista = '';
        foreach ($nombres as $i => $nombre) {
            $lista .= ($i + 1).". {$nombre}\n";
        }

        $totalOpciones  = count($nombres);
        $opcionesValidas = implode(', ', range(1, $totalOpciones));

        $senderService->sendText(
            $phone,
            "Inmueble *{$inmueble->nomenclatura}* encontrado.\nPara confirmar tu identidad, ¿cuál es tu nombre?\n\n{$lista}\nResponde con *{$opcionesValidas}*."
        );
    }

    // ── Paso 2: validar selección del nombre ──────────────────────────────────

    private function handleWaitingName(
        string $phone,
        string $text,
        array $session,
        WhatsAppConversationService $conversationService,
        WhatsAppSenderService $senderService,
        AsistenteService $asistenteService
    ): void {
        $seleccion     = (int) trim($text);
        $totalOpciones = count($session['nombres']);

        if ($seleccion < 1 || $seleccion > $totalOpciones) {
            $senderService->sendText(
                $phone,
                "Responde con un número entre *1* y *{$totalOpciones}*."
            );

            return;
        }

        if ($seleccion !== $session['opcion_correcta']) {
            $conversationService->resetToWaitingInmueble($phone);
            $senderService->sendText(
                $phone,
                "Nombre incorrecto. Por seguridad iniciemos de nuevo.\n\n¿Cuál es el código de tu inmueble?\n\nEjemplo: *1101*"
            );

            return;
        }

        // Nombre correcto → crear asistente y registrar presencia
        $reunion = Reunion::query()
            ->where('id', $session['reunion_id'])
            ->where('estado', 'en_curso')
            ->first();

        if (! $reunion) {
            $conversationService->clearSession($phone);
            $senderService->sendText($phone, 'La reunión ya no está en curso. Tu asistencia no pudo registrarse.');

            return;
        }

        $inmueble = Inmueble::query()->find($session['inmueble_id']);

        if (! $inmueble) {
            $conversationService->clearSession($phone);

            return;
        }

        try {
            $asistente = $asistenteService->create($reunion, [
                'telefono' => $phone,
                'inmuebles' => [[
                    'inmueble_id' => $inmueble->id,
                    'coeficiente' => (float) $inmueble->coeficiente,
                ]],
            ]);

            $result = $asistenteService->checkIn($asistente, $reunion->id);

            $conversationService->clearSession($phone);

            $msg = $result['ya_registrado']
                ? 'Tu asistencia ya estaba registrada anteriormente. ¡Bienvenido(a)!'
                : "✅ ¡Listo! Tu asistencia ha sido registrada. ¡Bienvenido(a) a la asamblea!";

            $senderService->sendText($phone, $msg);

        } catch (\RuntimeException $e) {
            $conversationService->clearSession($phone);

            Log::warning('[WA] Error al registrar asistencia.', [
                'error'      => $e->getMessage(),
                'phone'      => $phone,
                'inmueble_id' => $inmueble->id,
            ]);

            $mensaje = match (true) {
                str_contains($e->getMessage(), 'quórum ya fue cerrada')
                    => 'El registro de asistencia ya fue cerrado para esta reunión.',
                str_contains($e->getMessage(), 'ya están registrados')
                    => 'Este inmueble ya fue registrado por otra persona. Comunícate con logística.',
                str_contains($e->getMessage(), 'pregunta de quórum abierta')
                    => 'El registro de asistencia aún no está habilitado. Espera a que abra la verificación de quórum.',
                str_contains($e->getMessage(), 'votacion abierta')
                    => 'No se puede registrar mientras haya una votación en curso. Espera a que cierre.',
                default => 'No fue posible registrar tu asistencia. Comunícate con logística.',
            };

            $senderService->sendText($phone, $mensaje);
        }
    }

    // ── Voto numérico (1, 2, 3…) con pregunta activa ─────────────────────────

    private function handleVotoNumerico(
        string $phone,
        int $seleccion,
        array $activeVote,
        Reunion $reunion,
        WhatsAppSenderService $senderService,
        VotoService $votoService
    ): void {
        $totalOpciones = count($activeVote['opciones']);

        if ($seleccion < 1 || $seleccion > $totalOpciones) {
            $opcionesValidas = implode(', ', range(1, $totalOpciones));
            $senderService->sendText(
                $phone,
                "Opción no válida. Responde con *{$opcionesValidas}*."
            );

            return;
        }

        // Localizar el asistente por teléfono en la reunión activa
        $phoneDigits = preg_replace('/\D/', '', $phone);

        $asistente = $reunion->asistentes()
            ->whereNotNull('telefono')
            ->get()
            ->first(function ($a) use ($phoneDigits): bool {
                $stored = preg_replace('/\D/', '', (string) $a->telefono);

                return $stored === $phoneDigits
                    || str_ends_with($stored, $phoneDigits)
                    || str_ends_with($phoneDigits, $stored);
            });

        if (! $asistente) {
            $senderService->sendText(
                $phone,
                "No encontré tu registro en esta asamblea. Si aún no te has registrado, envía *hola* para iniciar."
            );

            return;
        }

        // Obtener la opción elegida y la pregunta
        $opcionData = $activeVote['opciones'][$seleccion - 1];
        $pregunta   = Pregunta::query()->find($activeVote['pregunta_id']);
        $opcion     = $pregunta?->opciones()->find($opcionData['id']);

        if (! $pregunta || ! $opcion || $pregunta->estado !== 'abierta') {
            $senderService->sendText($phone, 'La votación ya no está activa.');

            return;
        }

        try {
            $registrado = $votoService->registrarPorAsistente($pregunta, $opcion, $asistente, $phone);

            if ($registrado) {
                $senderService->sendText($phone, "✅ Voto registrado: *{$opcion->texto}*. ¡Gracias!");
            } else {
                $senderService->sendText($phone, '⚠️ Ya habías registrado tu voto en esta pregunta.');
            }
        } catch (\Throwable $e) {
            $yaVoto = str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'unique');

            $mensaje = $yaVoto
                ? '⚠️ Ya habías registrado tu voto en esta pregunta.'
                : 'No fue posible registrar tu voto. Intenta de nuevo.';

            $senderService->sendText($phone, $mensaje);

            Log::warning('[WA] Error al registrar voto numérico.', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);
        }
    }

    // ── Voto SI / NO (asistente ya registrado en la reunión) ─────────────────

    private function handleVoto(string $phone, string $comando, VotoService $votoService, WhatsAppSenderService $senderService): void
    {
        $phoneDigits = preg_replace('/\D/', '', $phone);

        $reunion = Reunion::query()->where('estado', 'en_curso')->first();

        if (! $reunion) {
            return;
        }

        $asistente = $reunion->asistentes()
            ->whereNotNull('telefono')
            ->get()
            ->first(function ($a) use ($phoneDigits): bool {
                $stored = preg_replace('/\D/', '', (string) $a->telefono);

                return $stored === $phoneDigits
                    || str_ends_with($stored, $phoneDigits)
                    || str_ends_with($phoneDigits, $stored);
            });

        if (! $asistente) {
            return;
        }

        $pregunta = $reunion->preguntas()->where('estado', 'abierta')->first();

        if (! $pregunta) {
            return;
        }

        $opcion = $pregunta->opciones()
            ->get()
            ->first(fn (Opcion $o) => $this->opcionCoincideConComando($o->texto, $comando));

        if (! $opcion) {
            return;
        }

        try {
            $registrado = $votoService->registrarPorAsistente($pregunta, $opcion, $asistente, $phone);

            if ($registrado) {
                $senderService->sendText($phone, "✅ Voto registrado: *{$opcion->texto}*. ¡Gracias!");
            } else {
                $senderService->sendText($phone, '⚠️ Ya habías registrado tu voto en esta pregunta.');
            }
        } catch (\Throwable $e) {
            Log::warning('[WA] Error al registrar voto.', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);
        }
    }

    private function opcionCoincideConComando(string $textoOpcion, string $comando): bool
    {
        $t = strtoupper(trim($textoOpcion));

        return ($comando === 'si' && (str_contains($t, 'SI') || str_contains($t, 'SÍ') || $t === 'S'))
            || ($comando === 'no' && (str_contains($t, 'NO') || $t === 'N'));
    }
}
