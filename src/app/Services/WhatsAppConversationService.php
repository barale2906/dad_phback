<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Gestiona el estado de conversación por teléfono en Redis.
 *
 * Pasos del flujo de registro de asistencia:
 *   waiting_inmueble  → esperando que el residente envíe la nomenclatura del inmueble.
 *   waiting_name      → esperando que el residente seleccione su nombre (1 / 2 / 3).
 */
class WhatsAppConversationService
{
    private const TTL     = 900; // 15 minutos
    private const PREFIX  = 'whatsapp:conv:';

    public function getSession(string $phone): ?array
    {
        $data = Cache::get($this->key($phone));

        return is_array($data) ? $data : null;
    }

    /**
     * Inicia el flujo de registro: guarda reunión activa y va al paso 1.
     */
    public function startSession(string $phone, int $reunionId): void
    {
        Cache::put($this->key($phone), [
            'step'      => 'waiting_inmueble',
            'reunion_id' => $reunionId,
        ], self::TTL);
    }

    /**
     * Avanza al paso 2 tras validar el inmueble.
     * Guarda los nombres candidatos y cuál es el correcto (posición 1-indexed).
     */
    public function advanceToWaitingName(
        string $phone,
        int $inmuebleId,
        array $nombres,
        int $opcionCorrecta
    ): void {
        $session = $this->getSession($phone) ?? [];

        Cache::put($this->key($phone), array_merge($session, [
            'step'            => 'waiting_name',
            'inmueble_id'     => $inmuebleId,
            'nombres'         => $nombres,
            'opcion_correcta' => $opcionCorrecta,
        ]), self::TTL);
    }

    /**
     * Reinicia al paso 1 manteniendo el reunion_id (nombre incorrecto → reintento).
     */
    public function resetToWaitingInmueble(string $phone): void
    {
        $reunionId = $this->getSession($phone)['reunion_id'] ?? null;

        Cache::put($this->key($phone), [
            'step'       => 'waiting_inmueble',
            'reunion_id' => $reunionId,
        ], self::TTL);
    }

    /**
     * Elimina la sesión al completar o cancelar el flujo.
     */
    public function clearSession(string $phone): void
    {
        Cache::forget($this->key($phone));
    }

    // ── Votación activa por reunión ────────────────────────────────────────────

    /**
     * Registra la pregunta activa para votación en WhatsApp.
     * Se llama cuando el admin abre una pregunta de tipo VOTACION.
     *
     * @param  array<int, array{numero: int, id: int, texto: string}>  $opciones
     */
    public function setActiveVote(int $reunionId, int $preguntaId, string $titulo, array $opciones): void
    {
        Cache::put($this->voteKey($reunionId), [
            'pregunta_id' => $preguntaId,
            'titulo'      => $titulo,
            'opciones'    => $opciones,
        ], 7200); // TTL: 2 horas, se limpia explícitamente al cerrar
    }

    /**
     * Devuelve la votación activa para la reunión, o null si no hay ninguna.
     *
     * @return array{pregunta_id: int, titulo: string, opciones: array}|null
     */
    public function getActiveVote(int $reunionId): ?array
    {
        $data = Cache::get($this->voteKey($reunionId));

        return is_array($data) ? $data : null;
    }

    /**
     * Elimina la votación activa al cerrar la pregunta.
     */
    public function clearActiveVote(int $reunionId): void
    {
        Cache::forget($this->voteKey($reunionId));
    }

    private function key(string $phone): string
    {
        return self::PREFIX.$phone;
    }

    private function voteKey(int $reunionId): string
    {
        return 'whatsapp:activevote:'.$reunionId;
    }
}
