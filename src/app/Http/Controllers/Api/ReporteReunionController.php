<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReunionEstadisticasResource;
use App\Models\Reunion;
use App\Services\ReunionEstadisticasCsvExporter;
use App\Services\ReunionEstadisticasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de reportes de reunión.
 *
 * Genera actas en PDF, estadísticas completas (JSON) y exportación CSV.
 */
class ReporteReunionController extends Controller
{
    public function __construct(
        private readonly ReunionEstadisticasService $estadisticasService,
        private readonly ReunionEstadisticasCsvExporter $csvExporter
    ) {
    }

    /**
     * Descarga el acta de la reunión en formato PDF.
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión. Example: 1
     *
     * @response 200 binary/octet-stream
     */
    public function actaPdf(Reunion $reunion): Response
    {
        Gate::authorize('view', $reunion);

        $reunion->load([
            'convocatoria',
            'zonasComunes',
            'preguntas.opciones',
        ]);

        $pdf = Pdf::loadView('pdf.acta_reunion', [
            'reunion' => $reunion,
        ])->setPaper('a4');

        $fileName = 'acta-reunion-' . $reunion->id . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Estadísticas completas de la reunión.
     *
     * Incluye:
     * - Orden del día con nivel de cumplimiento (items ejecutados / total)
     * - Asistencia: registrados (con código de barras o teléfono) y no registrados
     * - Todas las votaciones: pregunta, opciones, resultados y detalle por inmueble
     *   (quienes votaron y quienes no entre los asistentes)
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * @queryParam ocultar_respuesta boolean Si true, oculta la opción elegida en el detalle de votos por inmueble. Default: false. Example: false
     *
     * @response 200 {
     *   "data": {
     *     "reunion_id": 1,
     *     "orden_dia": {
     *       "items": [{"id": 1, "orden": 1, "titulo": "...", "descripcion": null, "ejecutado": true}],
     *       "total": 5,
     *       "ejecutados": 3,
     *       "nivel_cumplimiento": 60.0
     *     },
     *     "asistencia": {
     *       "registrados": [{"asistente_id": 1, "codigo_barras": 42, "telefono": "573001234567", "identificacion": "42", "inmuebles": [{"inmueble_id": 1, "nomenclatura": "101", "coeficiente": 1.23}]}],
     *       "no_registrados": [{"inmueble_id": 2, "nomenclatura": "102", "coeficiente": 0.98, "telefono": null}],
     *       "total_unidades": 50,
     *       "unidades_registradas": 20,
     *       "unidades_no_registradas": 30
     *     },
     *     "votaciones": [{
     *       "pregunta_id": 1,
     *       "pregunta": "¿Aprueba el presupuesto?",
     *       "tipo": "VOTACION",
     *       "estado": "cerrada",
     *       "disponible": true,
     *       "resultados": {...},
     *       "inmuebles_asistentes": [...]
     *     }]
     *   }
     * }
     */
    public function estadisticas(Reunion $reunion): JsonResponse
    {
        Gate::authorize('view', $reunion);

        $ocultarRespuesta = filter_var(
            request()->query('ocultar_respuesta', false),
            FILTER_VALIDATE_BOOL
        );

        $estadisticas = $this->estadisticasService->generar($reunion, $ocultarRespuesta);

        $data = array_merge(
            ['reunion_id' => $reunion->id],
            $estadisticas
        );

        return response()->json([
            'data' => (new ReunionEstadisticasResource($data))->toArray(request()),
        ]);
    }

    /**
     * Descarga las estadísticas de la reunión en formato CSV.
     *
     * Incluye orden del día, asistencia y votaciones en un archivo
     * compatible con Excel (UTF-8 con BOM).
     *
     * @authenticated
     *
     * @urlParam reunion integer required ID de la reunión. Example: 1
     * @queryParam ocultar_respuesta boolean Si true, oculta la opción elegida en el detalle de votos. Default: false. Example: false
     *
     * @response 200 binary/octet-stream
     */
    public function estadisticasCsv(Reunion $reunion): StreamedResponse
    {
        Gate::authorize('view', $reunion);

        $ocultarRespuesta = filter_var(
            request()->query('ocultar_respuesta', false),
            FILTER_VALIDATE_BOOL
        );

        $estadisticas = $this->estadisticasService->generar($reunion, $ocultarRespuesta);
        $data = array_merge(['reunion_id' => $reunion->id], $estadisticas);

        $csv = $this->csvExporter->exportar($data);

        $fileName = 'estadisticas-reunion-' . $reunion->id . '.csv';

        return response()->streamDownload(
            fn () => print($csv),
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }
}
