<?php

namespace App\Services;

use App\Models\Reunion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio para generar estadísticas completas de una reunión.
 *
 * Agrupa en una sola respuesta:
 * - Orden del día y nivel de cumplimiento
 * - Asistencia (registrados y no registrados con identificación)
 * - Todas las votaciones con resultados y detalle por inmueble (quienes votaron y quienes no)
 */
class ReunionEstadisticasService
{
    public function __construct(
        private readonly QuorumService $quorumService,
        private readonly PreguntaResultadosService $preguntaResultadosService
    ) {
    }

    /**
     * Genera las estadísticas completas de la reunión.
     *
     * @param  bool  $ocultarRespuesta  Si true, oculta la opción elegida en el detalle de votos por inmueble
     * @return array{orden_dia: array, asistencia: array, votaciones: array}
     */
    public function generar(Reunion $reunion, bool $ocultarRespuesta = false): array
    {
        return [
            'orden_dia' => $this->getOrdenDiaConCumplimiento($reunion),
            'asistencia' => $this->getAsistencia($reunion),
            'votaciones' => $this->getVotaciones($reunion, $ocultarRespuesta),
        ];
    }

    /**
     * Orden del día con nivel de cumplimiento.
     *
     * @return array{items: array, total: int, ejecutados: int, nivel_cumplimiento: float}
     */
    private function getOrdenDiaConCumplimiento(Reunion $reunion): array
    {
        $items = $reunion->ordenDiaItems()
            ->orderBy('orden')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'orden' => $item->orden,
                'titulo' => $item->titulo,
                'descripcion' => $item->descripcion,
                'ejecutado' => (bool) $item->ejecutado,
            ])
            ->all();

        $total = count($items);
        $ejecutados = collect($items)->filter(fn ($i) => $i['ejecutado'])->count();
        $nivelCumplimiento = $total > 0 ? round(($ejecutados / $total) * 100, 2) : 0.0;

        return [
            'items' => $items,
            'total' => $total,
            'ejecutados' => $ejecutados,
            'nivel_cumplimiento' => $nivelCumplimiento,
        ];
    }

    /**
     * Asistencia: registrados (con codigo_barras o telefono) y no registrados.
     *
     * @return array{registrados: array, no_registrados: array, total_unidades: int, unidades_registradas: int, unidades_no_registradas: int}
     */
    private function getAsistencia(Reunion $reunion): array
    {
        $registrados = [];
        $inmuebleIdsRegistrados = collect();

        if (Schema::hasTable('asistentes') && Schema::hasTable('asistente_inmueble')) {
            $asistentes = $reunion->asistentes()
                ->with(['inmuebles' => fn ($q) => $q->where('activo', true)])
                ->orderBy('created_at')
                ->get();

            foreach ($asistentes as $asistente) {
                $identificacion = $asistente->codigo_barras
                    ? (string) $asistente->codigo_barras
                    : ($asistente->telefono ?? null);

                $inmuebles = $asistente->inmuebles->map(fn ($inmueble) => [
                    'inmueble_id' => $inmueble->id,
                    'nomenclatura' => $inmueble->nomenclatura,
                    'coeficiente' => (float) ($inmueble->pivot->coeficiente ?? $inmueble->coeficiente),
                ])->all();

                foreach ($asistente->inmuebles as $inmueble) {
                    $inmuebleIdsRegistrados->push($inmueble->id);
                }

                $registrados[] = [
                    'asistente_id' => $asistente->id,
                    'codigo_barras' => $asistente->codigo_barras,
                    'telefono' => $asistente->telefono,
                    'identificacion' => $identificacion,
                    'inmuebles' => $inmuebles,
                ];
            }
        }

        $noRegistrados = [];
        $inmueblesActivos = DB::table('inmuebles')
            ->where('activo', true)
            ->select('id', 'nomenclatura', 'coeficiente', 'telefono')
            ->orderBy('nomenclatura')
            ->get();

        foreach ($inmueblesActivos as $inmueble) {
            if ($inmuebleIdsRegistrados->contains($inmueble->id)) {
                continue;
            }
            $noRegistrados[] = [
                'inmueble_id' => $inmueble->id,
                'nomenclatura' => $inmueble->nomenclatura,
                'coeficiente' => (float) $inmueble->coeficiente,
                'telefono' => $inmueble->telefono,
            ];
        }

        $totalUnidades = $inmueblesActivos->count();
        $unidadesRegistradas = $inmuebleIdsRegistrados->unique()->count();
        $unidadesNoRegistradas = $totalUnidades - $unidadesRegistradas;

        return [
            'registrados' => $registrados,
            'no_registrados' => $noRegistrados,
            'total_unidades' => $totalUnidades,
            'unidades_registradas' => $unidadesRegistradas,
            'unidades_no_registradas' => $unidadesNoRegistradas,
        ];
    }

    /**
     * Todas las votaciones con resultados y detalle por inmueble.
     *
     * Para cada pregunta incluye: resultados agregados (opciones y votos) y
     * detalle de inmuebles asistentes (quienes votaron y quienes no).
     *
     * @return array<int, array>
     */
    private function getVotaciones(Reunion $reunion, bool $ocultarRespuesta): array
    {
        $preguntas = $reunion->preguntas()
            ->with('opciones')
            ->orderBy('orden')
            ->get();

        $votaciones = [];

        foreach ($preguntas as $pregunta) {
            try {
                $resultados = $this->preguntaResultadosService->getResultados($pregunta);
                $inmuebleVotos = $this->preguntaResultadosService->getInmuebleVotos($pregunta, $ocultarRespuesta);
            } catch (\RuntimeException) {
                $votaciones[] = [
                    'pregunta_id' => $pregunta->id,
                    'pregunta' => $pregunta->pregunta,
                    'tipo' => $pregunta->tipo,
                    'estado' => $pregunta->estado,
                    'disponible' => false,
                    'mensaje' => 'No hay resultados disponibles para esta pregunta.',
                ];
                continue;
            }

            $votaciones[] = [
                'pregunta_id' => $resultados['pregunta_id'],
                'pregunta' => $resultados['pregunta'],
                'tipo' => $resultados['tipo'],
                'estado' => $resultados['estado'],
                'disponible' => true,
                'resultados' => [
                    'asistencia_unidades' => $resultados['asistencia_unidades'],
                    'asistencia_coeficiente' => $resultados['asistencia_coeficiente'],
                    'votaron_unidades' => $resultados['votaron_unidades'],
                    'votaron_coeficiente' => $resultados['votaron_coeficiente'],
                    'no_votaron_unidades' => $resultados['no_votaron_unidades'],
                    'no_votaron_coeficiente' => $resultados['no_votaron_coeficiente'],
                    'opciones' => $resultados['resultados'],
                ],
                'inmuebles_asistentes' => $inmuebleVotos['inmuebles_asistentes'],
            ];
        }

        return $votaciones;
    }
}
