<?php

namespace App\Services;

use App\Models\Pregunta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio para obtener resultados de votación y detalle por inmueble.
 *
 * Extrae la lógica de PreguntaController para ser reutilizada en reportes
 * y estadísticas de reunión. Filtra por inmuebles activos y respeta
 * la estructura de los endpoints GET /preguntas/:id/resultados e
 * GET /preguntas/:id/inmuebles-votos.
 */
class PreguntaResultadosService
{
    public function __construct(private readonly QuorumService $quorumService)
    {
    }

    /**
     * Obtiene los resultados agregados de una pregunta (votos por opción).
     *
     * @return array{pregunta_id: int, tipo: string, estado: string, asistencia_unidades: int, asistencia_coeficiente: float, votaron_unidades: int, votaron_coeficiente: float, no_votaron_unidades: int, no_votaron_coeficiente: float, resultados: array}
     *
     * @throws \RuntimeException Si la pregunta está inactiva o cancelada
     */
    public function getResultados(Pregunta $pregunta): array
    {
        if (in_array($pregunta->estado, ['inactiva', 'cancelada'], true)) {
            throw new \RuntimeException('No hay resultados disponibles para esta pregunta.');
        }

        $resultRows = $pregunta->opciones()->orderBy('orden')->get();
        $votesByOption = [];
        $votaronUnidades = 0;
        $votaronCoeficiente = 0.0;

        if (Schema::hasTable('votos')) {
            $votesByOption = DB::table('votos')
                ->selectRaw('opcion_id, COUNT(*) as total_votos, COALESCE(SUM(coeficiente), 0) as total_coeficiente')
                ->where('pregunta_id', $pregunta->id)
                ->groupBy('opcion_id')
                ->get()
                ->keyBy('opcion_id')
                ->toArray();

            $totalesVotantes = DB::table('votos')
                ->join('inmuebles', 'votos.inmueble_id', '=', 'inmuebles.id')
                ->where('votos.pregunta_id', $pregunta->id)
                ->where('inmuebles.activo', true)
                ->selectRaw('COUNT(DISTINCT votos.inmueble_id) as unidades, COALESCE(SUM(votos.coeficiente), 0) as coeficiente')
                ->first();
            $votaronUnidades = (int) ($totalesVotantes->unidades ?? 0);
            $votaronCoeficiente = (float) ($totalesVotantes->coeficiente ?? 0);
        }

        $asistencia = $this->quorumService->getAsistenciaRegistrada($pregunta->reunion);
        $noVotaronUnidades = max(0, $asistencia['unidades'] - $votaronUnidades);
        $noVotaronCoeficiente = max(0.0, $asistencia['coeficiente'] - $votaronCoeficiente);

        $results = $resultRows->map(function ($opcion) use ($votesByOption) {
            $row = $votesByOption[$opcion->id] ?? null;

            return [
                'opcion_id' => $opcion->id,
                'texto' => $opcion->texto,
                'votos' => (int) ($row->total_votos ?? 0),
                'coeficiente' => (float) ($row->total_coeficiente ?? 0.0),
            ];
        });

        return [
            'pregunta_id' => $pregunta->id,
            'pregunta' => $pregunta->pregunta,
            'tipo' => $pregunta->tipo,
            'estado' => $pregunta->estado,
            'asistencia_unidades' => $asistencia['unidades'],
            'asistencia_coeficiente' => round($asistencia['coeficiente'], 6),
            'votaron_unidades' => $votaronUnidades,
            'votaron_coeficiente' => round($votaronCoeficiente, 6),
            'no_votaron_unidades' => $noVotaronUnidades,
            'no_votaron_coeficiente' => round($noVotaronCoeficiente, 6),
            'resultados' => $results->all(),
        ];
    }

    /**
     * Obtiene el detalle de votos por inmueble para una pregunta.
     *
     * Incluye todos los inmuebles activos con su estado de voto y opción elegida.
     * Los asistentes son los inmuebles registrados en la reunión.
     *
     * @param  bool  $ocultarRespuesta  Si true, no incluye opcion_id ni opcion_texto en la respuesta
     * @return array{pregunta_id: int, tipo: string, estado: string, total_inmuebles: int, inmuebles_votaron: int, inmuebles_pendientes: int, coeficiente_total: float, coeficiente_votante: float, asistencia_unidades: int, asistencia_coeficiente: float, no_votaron_unidades: int, no_votaron_coeficiente: float, inmuebles: array, inmuebles_asistentes: array}
     *
     * @throws \RuntimeException Si la pregunta está inactiva o cancelada
     */
    public function getInmuebleVotos(Pregunta $pregunta, bool $ocultarRespuesta = false): array
    {
        if (in_array($pregunta->estado, ['inactiva', 'cancelada'], true)) {
            throw new \RuntimeException('No hay información disponible para esta pregunta.');
        }

        $votosPorInmueble = DB::table('votos')
            ->join('opciones', 'votos.opcion_id', '=', 'opciones.id')
            ->where('votos.pregunta_id', $pregunta->id)
            ->select('votos.inmueble_id', 'votos.opcion_id', 'opciones.texto as opcion_texto', 'votos.votado_at')
            ->get()
            ->keyBy('inmueble_id');

        $inmuebleIdsAsistentes = collect();
        $identificacionPorInmueble = collect();
        if (Schema::hasTable('asistente_inmueble')) {
            $asistentesInmuebles = DB::table('asistente_inmueble')
                ->join('asistentes', 'asistente_inmueble.asistente_id', '=', 'asistentes.id')
                ->join('inmuebles', 'asistente_inmueble.inmueble_id', '=', 'inmuebles.id')
                ->where('asistentes.reunion_id', $pregunta->reunion_id)
                ->where('inmuebles.activo', true)
                ->select(
                    'asistente_inmueble.inmueble_id',
                    'asistentes.codigo_barras',
                    'asistentes.telefono'
                )
                ->get();

            $inmuebleIdsAsistentes = $asistentesInmuebles->pluck('inmueble_id');
            $identificacionPorInmueble = $asistentesInmuebles->keyBy('inmueble_id');
        }

        $inmuebles = DB::table('inmuebles')
            ->where('activo', true)
            ->select('id', 'nomenclatura', 'coeficiente')
            ->orderBy('nomenclatura')
            ->get();

        $totalCoeficiente = 0.0;
        $coeficienteVotante = 0.0;
        $votaron = 0;

        $detalle = $inmuebles->map(function ($inmueble) use ($votosPorInmueble, $ocultarRespuesta, $inmuebleIdsAsistentes, $identificacionPorInmueble, &$totalCoeficiente, &$coeficienteVotante, &$votaron) {
            $totalCoeficiente += (float) $inmueble->coeficiente;
            $voto = $votosPorInmueble[$inmueble->id] ?? null;
            $esAsistente = $inmuebleIdsAsistentes->contains($inmueble->id);

            if ($voto) {
                $votaron++;
                $coeficienteVotante += (float) $inmueble->coeficiente;
            }

            $asist = $identificacionPorInmueble[$inmueble->id] ?? null;
            $codigoBarras = $esAsistente && $asist ? $asist->codigo_barras : null;
            $telefono = $esAsistente && $asist ? $asist->telefono : null;
            $identificacion = $codigoBarras ? (string) $codigoBarras : ($telefono ?? null);

            return [
                'inmueble_id' => $inmueble->id,
                'nomenclatura' => $inmueble->nomenclatura,
                'coeficiente' => (float) $inmueble->coeficiente,
                'votado' => $voto !== null,
                'es_asistente' => $esAsistente,
                'codigo_barras' => $codigoBarras,
                'telefono' => $telefono,
                'identificacion' => $identificacion,
                'opcion_id' => ($voto && ! $ocultarRespuesta) ? $voto->opcion_id : null,
                'opcion_texto' => ($voto && ! $ocultarRespuesta) ? $voto->opcion_texto : null,
                'votado_at' => $voto ? $voto->votado_at : null,
            ];
        });

        $total = $inmuebles->count();
        $asistencia = $this->quorumService->getAsistenciaRegistrada($pregunta->reunion);
        $noVotaronUnidades = max(0, $asistencia['unidades'] - $votaron);
        $noVotaronCoeficiente = max(0.0, $asistencia['coeficiente'] - $coeficienteVotante);

        $inmueblesAsistentes = $detalle
            ->filter(fn ($item) => $item['es_asistente'])
            ->values()
            ->sortByDesc('votado')
            ->values()
            ->all();

        return [
            'pregunta_id' => $pregunta->id,
            'pregunta' => $pregunta->pregunta,
            'tipo' => $pregunta->tipo,
            'estado' => $pregunta->estado,
            'total_inmuebles' => $total,
            'inmuebles_votaron' => $votaron,
            'inmuebles_pendientes' => $total - $votaron,
            'coeficiente_total' => round($totalCoeficiente, 6),
            'coeficiente_votante' => round($coeficienteVotante, 6),
            'asistencia_unidades' => $asistencia['unidades'],
            'asistencia_coeficiente' => round($asistencia['coeficiente'], 6),
            'no_votaron_unidades' => $noVotaronUnidades,
            'no_votaron_coeficiente' => round($noVotaronCoeficiente, 6),
            'inmuebles' => $detalle->all(),
            'inmuebles_asistentes' => $inmueblesAsistentes,
        ];
    }
}
