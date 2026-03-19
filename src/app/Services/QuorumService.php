<?php

namespace App\Services;

use App\Models\Pregunta;
use App\Models\Reunion;
use App\Models\Voto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuorumService
{
    public function calcularQuorum(?Reunion $reunion = null): array
    {
        $totalQuery = DB::table('inmuebles')
            ->where('activo', true);

        $totales = $totalQuery
            ->selectRaw('COUNT(*) as total_unidades, COALESCE(SUM(coeficiente), 0) as total_coeficiente')
            ->first();

        $presentesQuery = Voto::query()
            ->join('inmuebles', 'votos.inmueble_id', '=', 'inmuebles.id')
            ->join('preguntas', 'votos.pregunta_id', '=', 'preguntas.id')
            ->where('inmuebles.activo', true);

        if ($reunion) {
            $presentesQuery->where('preguntas.reunion_id', $reunion->id);
        }

        $presentes = $presentesQuery
            ->selectRaw('COUNT(DISTINCT votos.inmueble_id) as unidades_presentes, COALESCE(SUM(votos.coeficiente), 0) as coeficiente_presente')
            ->first();

        $totalUnidades = (int) ($totales->total_unidades ?? 0);
        $totalCoef = (float) ($totales->total_coeficiente ?? 0);
        $unidadesPresentes = (int) ($presentes->unidades_presentes ?? 0);
        $coefPresente = (float) ($presentes->coeficiente_presente ?? 0);

        $porcentajeUnidades = $totalUnidades > 0 ? ($unidadesPresentes / $totalUnidades) * 100 : 0.0;
        $porcentajeCoeficiente = $totalCoef > 0 ? ($coefPresente / $totalCoef) * 100 : 0.0;

        return [
            'total_unidades' => $totalUnidades,
            'unidades_presentes' => $unidadesPresentes,
            'total_coeficiente' => $totalCoef,
            'coeficiente_presente' => $coefPresente,
            'porcentaje_unidades' => round($porcentajeUnidades, 2),
            'porcentaje_coeficiente' => round($porcentajeCoeficiente, 2),
        ];
    }

    /**
     * Obtiene la asistencia registrada en la reunión (todos los asistentes).
     * Incluye registro normal (check-in PRESENTE) y registro tardío.
     * Usado para calcular quiénes no votaron en preguntas de votación y para
     * mostrar el total real de asistencia en resultados de quórum.
     *
     * @return array{unidades: int, coeficiente: float}
     */
    public function getAsistenciaRegistrada(Reunion $reunion): array
    {
        if (! Schema::hasTable('asistente_inmueble')) {
            return ['unidades' => 0, 'coeficiente' => 0.0];
        }

        $asistencia = DB::table('asistente_inmueble')
            ->join('asistentes', 'asistente_inmueble.asistente_id', '=', 'asistentes.id')
            ->join('inmuebles', 'asistente_inmueble.inmueble_id', '=', 'inmuebles.id')
            ->where('asistentes.reunion_id', $reunion->id)
            ->where('inmuebles.activo', true)
            ->selectRaw('COUNT(DISTINCT asistente_inmueble.inmueble_id) as unidades, COALESCE(SUM(asistente_inmueble.coeficiente), 0) as coeficiente')
            ->first();

        return [
            'unidades' => (int) ($asistencia->unidades ?? 0),
            'coeficiente' => (float) ($asistencia->coeficiente ?? 0),
        ];
    }

    public function crearPreguntaQuorum(Reunion $reunion): Pregunta
    {
        return DB::transaction(function () use ($reunion): Pregunta {
            $pregunta = $reunion->preguntas()->create([
                'pregunta' => 'Verificacion de quorum',
                'tipo' => 'QUORUM_CHECK',
                'estado' => 'abierta',
                'orden' => 0,
            ]);

            $pregunta->opciones()->create([
                'texto' => 'PRESENTE',
                'orden' => 1,
            ]);

            return $pregunta;
        });
    }
}

