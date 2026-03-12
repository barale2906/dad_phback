<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pregunta\StorePreguntaRequest;
use App\Http\Requests\Pregunta\UpdatePreguntaRequest;
use App\Http\Resources\PreguntaResource;
use App\Jobs\AbrirPreguntaJob;
use App\Jobs\CerrarPreguntaJob;
use App\Models\Pregunta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

class PreguntaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Pregunta::class);

        $query = Pregunta::query()->with('opciones');

        if (request()->filled('reunion_id')) {
            $query->where('reunion_id', (int) request()->query('reunion_id'));
        }
        if (request()->filled('estado')) {
            $query->where('estado', request()->query('estado'));
        }

        return PreguntaResource::collection($query->orderBy('orden')->paginate(20));
    }

    public function store(StorePreguntaRequest $request): JsonResponse
    {
        Gate::authorize('create', Pregunta::class);

        $pregunta = Pregunta::query()->create([
            'reunion_id' => $request->integer('reunion_id'),
            'pregunta' => $request->string('pregunta')->value(),
            'tipo' => $request->input('tipo', 'VOTACION'),
            'estado' => $request->input('estado', 'inactiva'),
            'orden' => $request->integer('orden', 1),
        ]);

        return response()->json([
            'message' => 'Pregunta creada correctamente.',
            'data' => new PreguntaResource($pregunta),
        ], 201);
    }

    public function show(Pregunta $pregunta): PreguntaResource
    {
        Gate::authorize('view', $pregunta);

        return new PreguntaResource($pregunta->load('opciones'));
    }

    public function update(UpdatePreguntaRequest $request, Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('update', $pregunta);

        $pregunta->update($request->validated());

        return response()->json([
            'message' => 'Pregunta actualizada correctamente.',
            'data' => new PreguntaResource($pregunta->fresh('opciones')),
        ], 200);
    }

    public function destroy(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('delete', $pregunta);

        $pregunta->delete();

        return response()->json([
            'message' => 'Pregunta eliminada correctamente.',
        ], 200);
    }

    public function abrir(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('update', $pregunta);

        Queue::push(new AbrirPreguntaJob($pregunta->id));

        return response()->json([
            'message' => 'Apertura de pregunta en proceso.',
            'status' => 'queued',
        ], 202);
    }

    public function cerrar(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('update', $pregunta);

        Queue::push(new CerrarPreguntaJob($pregunta->id));

        return response()->json([
            'message' => 'Cierre de pregunta en proceso.',
            'status' => 'queued',
        ], 202);
    }

    public function resultados(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('view', $pregunta);

        // Solo bloqueamos para preguntas inactivas o canceladas.
        // Permitimos resultados en tiempo real (abierta) y resultados finales (cerrada).
        if (in_array($pregunta->estado, ['inactiva', 'cancelada'], true)) {
            return response()->json([
                'message' => 'No hay resultados disponibles para esta pregunta.',
            ], 422);
        }

        $resultRows = $pregunta->opciones()->orderBy('orden')->get();
        $votesByOption = [];

        if (Schema::hasTable('votos')) {
            $votesByOption = DB::table('votos')
                ->selectRaw('opcion_id, COUNT(*) as total_votos, COALESCE(SUM(coeficiente), 0) as total_coeficiente')
                ->where('pregunta_id', $pregunta->id)
                ->groupBy('opcion_id')
                ->get()
                ->keyBy('opcion_id')
                ->toArray();
        }

        $results = $resultRows->map(fn ($opcion) => [
            'opcion_id' => $opcion->id,
            'texto' => $opcion->texto,
            'votos' => (int) ($votesByOption[$opcion->id]->total_votos ?? 0),
            'coeficiente' => (float) ($votesByOption[$opcion->id]->total_coeficiente ?? 0),
        ]);

        return response()->json([
            'data' => [
                'pregunta_id' => $pregunta->id,
                'tipo' => $pregunta->tipo,
                'estado' => $pregunta->estado,
                'resultados' => $results,
            ],
        ], 200);
    }

    /**
     * Detalle de votos por inmueble para una pregunta.
     *
     * Muestra qué inmuebles han votado y cuáles no, con su opción elegida.
     * Funciona en tiempo real (pregunta abierta) y para resultados finales (cerrada).
     * Aplica tanto para preguntas de tipo VOTACION como QUORUM_CHECK.
     *
     * @authenticated
     *
     * @urlParam pregunta integer required ID de la pregunta. Example: 1
     * @queryParam ocultar_respuesta boolean Si true, oculta la opción elegida (solo muestra si votó o no). Default: false. Example: false
     *
     * @response 200 {
     *   "data": {
     *     "pregunta_id": 1,
     *     "tipo": "VOTACION",
     *     "estado": "abierta",
     *     "total_inmuebles": 50,
     *     "inmuebles_votaron": 20,
     *     "inmuebles_pendientes": 30,
     *     "coeficiente_total": 100.0,
     *     "coeficiente_votante": 45.23,
     *     "inmuebles": [
     *       { "inmueble_id": 3, "nomenclatura": "101", "coeficiente": 1.23456, "votado": true, "opcion_id": 2, "opcion_texto": "SI", "votado_at": "2026-03-11T10:35:00+00:00" },
     *       { "inmueble_id": 7, "nomenclatura": "201", "coeficiente": 0.98765, "votado": false, "opcion_id": null, "opcion_texto": null, "votado_at": null }
     *     ]
     *   }
     * }
     * @response 422 { "message": "No hay información disponible para esta pregunta." }
     */
    public function inmuebleVotos(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('view', $pregunta);

        if (in_array($pregunta->estado, ['inactiva', 'cancelada'], true)) {
            return response()->json([
                'message' => 'No hay información disponible para esta pregunta.',
            ], 422);
        }

        $ocultarRespuesta = filter_var(request()->query('ocultar_respuesta', false), FILTER_VALIDATE_BOOL);

        // Votos registrados para esta pregunta indexados por inmueble_id
        $votosPorInmueble = DB::table('votos')
            ->join('opciones', 'votos.opcion_id', '=', 'opciones.id')
            ->where('votos.pregunta_id', $pregunta->id)
            ->select('votos.inmueble_id', 'votos.opcion_id', 'opciones.texto as opcion_texto', 'votos.votado_at')
            ->get()
            ->keyBy('inmueble_id');

        // Todos los inmuebles activos de la PH
        $inmuebles = DB::table('inmuebles')
            ->where('activo', true)
            ->select('id', 'nomenclatura', 'coeficiente')
            ->orderBy('nomenclatura')
            ->get();

        $totalCoeficiente = 0.0;
        $coeficienteVotante = 0.0;
        $votaron = 0;

        $detalle = $inmuebles->map(function ($inmueble) use ($votosPorInmueble, $ocultarRespuesta, &$totalCoeficiente, &$coeficienteVotante, &$votaron) {
            $totalCoeficiente += (float) $inmueble->coeficiente;
            $voto = $votosPorInmueble[$inmueble->id] ?? null;

            if ($voto) {
                $votaron++;
                $coeficienteVotante += (float) $inmueble->coeficiente;
            }

            return [
                'inmueble_id' => $inmueble->id,
                'nomenclatura' => $inmueble->nomenclatura,
                'coeficiente' => (float) $inmueble->coeficiente,
                'votado' => $voto !== null,
                'opcion_id' => ($voto && ! $ocultarRespuesta) ? $voto->opcion_id : null,
                'opcion_texto' => ($voto && ! $ocultarRespuesta) ? $voto->opcion_texto : null,
                'votado_at' => $voto ? $voto->votado_at : null,
            ];
        });

        $total = $inmuebles->count();

        return response()->json([
            'data' => [
                'pregunta_id' => $pregunta->id,
                'tipo' => $pregunta->tipo,
                'estado' => $pregunta->estado,
                'total_inmuebles' => $total,
                'inmuebles_votaron' => $votaron,
                'inmuebles_pendientes' => $total - $votaron,
                'coeficiente_total' => round($totalCoeficiente, 6),
                'coeficiente_votante' => round($coeficienteVotante, 6),
                'inmuebles' => $detalle,
            ],
        ], 200);
    }
}
