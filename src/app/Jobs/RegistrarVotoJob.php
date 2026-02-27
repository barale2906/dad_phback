<?php

namespace App\Jobs;

use App\Models\Asistente;
use App\Models\Inmueble;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Services\VotoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RegistrarVotoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $preguntaId,
        public int $opcionId,
        public ?int $inmuebleId,
        public ?int $asistenteId,
        public ?string $telefono
    ) {
        $this->onQueue('votaciones');
    }

    public function handle(VotoService $service): void
    {
        $pregunta = Pregunta::query()->find($this->preguntaId);
        $opcion = Opcion::query()->find($this->opcionId);

        if (! $pregunta || ! $opcion) {
            return;
        }

        if ($opcion->pregunta_id !== $pregunta->id) {
            Log::warning('Opcion no pertenece a la pregunta', [
                'pregunta_id' => $this->preguntaId,
                'opcion_id' => $this->opcionId,
            ]);

            return;
        }

        try {
            if ($this->inmuebleId) {
                $inmueble = Inmueble::query()->find($this->inmuebleId);

                if (! $inmueble) {
                    return;
                }

                $service->registrarPorInmueble($pregunta, $opcion, $inmueble, null, $this->telefono);

                return;
            }

            if ($this->asistenteId) {
                $asistente = Asistente::query()->find($this->asistenteId);

                if (! $asistente) {
                    return;
                }

                $service->registrarPorAsistente($pregunta, $opcion, $asistente, $this->telefono);
            }
        } catch (RuntimeException $e) {
            Log::warning('Error de negocio al registrar voto', [
                'pregunta_id' => $this->preguntaId,
                'opcion_id' => $this->opcionId,
                'inmueble_id' => $this->inmuebleId,
                'asistente_id' => $this->asistenteId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

