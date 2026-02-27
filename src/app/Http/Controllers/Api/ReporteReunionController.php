<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reunion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReporteReunionController extends Controller
{
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

    public function estadisticas(Reunion $reunion): JsonResponse
    {
        Gate::authorize('view', $reunion);

        $reunion->load([
            'preguntas.opciones',
        ]);

        $estadisticas = [];

        foreach ($reunion->preguntas as $pregunta) {
            $resultados = app('db')->table('votos')
                ->selectRaw('opcion_id, COUNT(*) as total_votos, COALESCE(SUM(coeficiente), 0) as suma_coeficiente')
                ->where('pregunta_id', $pregunta->id)
                ->groupBy('opcion_id')
                ->pluck('total_votos', 'opcion_id');

            $estadisticas[] = [
                'pregunta_id' => $pregunta->id,
                'pregunta' => $pregunta->pregunta,
                'resultados' => $pregunta->opciones->map(function ($opcion) use ($resultados) {
                    $total = (int) ($resultados[$opcion->id] ?? 0);

                    return [
                        'opcion_id' => $opcion->id,
                        'texto' => $opcion->texto,
                        'total_votos' => $total,
                    ];
                })->all(),
            ];
        }

        return response()->json([
            'data' => $estadisticas,
        ]);
    }
}

