<?php

namespace App\Services;

use App\Events\VoteRegistered;
use App\Jobs\RecalcularQuorumJob;
use App\Models\Asistente;
use App\Models\Inmueble;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Voto;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VotoService
{
    public function registrarPorInmueble(Pregunta $pregunta, Opcion $opcion, Inmueble $inmueble, ?Asistente $asistente, ?string $telefono): ?Voto
    {
        if ($pregunta->estado !== 'abierta') {
            throw new RuntimeException('Solo se pueden registrar votos para preguntas abiertas.');
        }

        if (! $inmueble->activo) {
            throw new RuntimeException('No se pueden registrar votos para inmuebles inactivos.');
        }

        try {
            return DB::transaction(function () use ($pregunta, $opcion, $inmueble, $asistente, $telefono): ?Voto {
                $existing = Voto::query()
                    ->where('pregunta_id', $pregunta->id)
                    ->where('inmueble_id', $inmueble->id)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return null;
                }

                $voto = Voto::create([
                    'pregunta_id' => $pregunta->id,
                    'inmueble_id' => $inmueble->id,
                    'opcion_id' => $opcion->id,
                    'asistente_id' => $asistente?->id,
                    'coeficiente' => $inmueble->coeficiente,
                    'telefono' => $telefono,
                    'votado_at' => now(),
                ]);

                VoteRegistered::dispatch(
                    $pregunta->reunion_id,
                    $pregunta->id,
                    $inmueble->id,
                    (float) $inmueble->coeficiente
                );

                RecalcularQuorumJob::dispatch($pregunta->reunion_id);

                return $voto;
            });
        } catch (QueryException $e) {
            $code = (string) $e->getCode();
            $message = strtolower($e->getMessage());
            $isUniqueViolation = $code === '23505'
                || ($code === '23000' && (str_contains($message, 'unique') || str_contains($message, 'duplicate')));

            if ($isUniqueViolation) {
                return null;
            }

            throw $e;
        }
    }

    public function registrarPorAsistente(Pregunta $pregunta, Opcion $opcion, Asistente $asistente, ?string $telefono): void
    {
        $inmuebles = $asistente->inmuebles()
            ->where('inmuebles.activo', true)
            ->get();

        foreach ($inmuebles as $inmueble) {
            $this->registrarPorInmueble($pregunta, $opcion, $inmueble, $asistente, $telefono);
        }
    }
}

