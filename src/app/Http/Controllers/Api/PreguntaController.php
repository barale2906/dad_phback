<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pregunta\StorePreguntaRequest;
use App\Http\Requests\Pregunta\UpdatePreguntaRequest;
use App\Http\Resources\PreguntaResource;
use App\Jobs\AbrirPreguntaJob;
use App\Jobs\CerrarPreguntaJob;
use App\Models\Pregunta;
use App\Services\PreguntaResultadosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class PreguntaController extends Controller
{
    public function __construct(private readonly PreguntaResultadosService $preguntaResultadosService)
    {
    }
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

        AbrirPreguntaJob::dispatch($pregunta->id);

        return response()->json([
            'message' => 'Apertura de pregunta en proceso.',
            'status' => 'queued',
        ], 202);
    }

    public function cerrar(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('update', $pregunta);

        CerrarPreguntaJob::dispatch($pregunta->id);

        return response()->json([
            'message' => 'Cierre de pregunta en proceso.',
            'status' => 'queued',
        ], 202);
    }

    public function resultados(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('view', $pregunta);

        try {
            $data = $this->preguntaResultadosService->getResultados($pregunta);
        } catch (\RuntimeException) {
            return response()->json([
                'message' => 'No hay resultados disponibles para esta pregunta.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'pregunta_id' => $data['pregunta_id'],
                'tipo' => $data['tipo'],
                'estado' => $data['estado'],
                'asistencia_unidades' => $data['asistencia_unidades'],
                'asistencia_coeficiente' => $data['asistencia_coeficiente'],
                'votaron_unidades' => $data['votaron_unidades'],
                'votaron_coeficiente' => $data['votaron_coeficiente'],
                'no_votaron_unidades' => $data['no_votaron_unidades'],
                'no_votaron_coeficiente' => $data['no_votaron_coeficiente'],
                'resultados' => $data['resultados'],
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
     *       { "inmueble_id": 3, "nomenclatura": "101", "coeficiente": 1.23456, "votado": true, "es_asistente": true, "opcion_id": 2, "opcion_texto": "SI", "votado_at": "2026-03-11T10:35:00+00:00" },
     *       { "inmueble_id": 7, "nomenclatura": "201", "coeficiente": 0.98765, "votado": false, "es_asistente": true, "opcion_id": null, "opcion_texto": null, "votado_at": null }
     *     ],
     *     "inmuebles_asistentes": [
     *       { "inmueble_id": 3, "nomenclatura": "101", "coeficiente": 1.23456, "votado": true, "es_asistente": true, "opcion_id": 2, "opcion_texto": "SI", "votado_at": "2026-03-11T10:35:00+00:00" },
     *       { "inmueble_id": 7, "nomenclatura": "201", "coeficiente": 0.98765, "votado": false, "es_asistente": true, "opcion_id": null, "opcion_texto": null, "votado_at": null }
     *     ]
     *   }
     * }
     * @response 422 { "message": "No hay información disponible para esta pregunta." }
     */
    public function inmuebleVotos(Pregunta $pregunta): JsonResponse
    {
        Gate::authorize('view', $pregunta);

        try {
            $ocultarRespuesta = filter_var(request()->query('ocultar_respuesta', false), FILTER_VALIDATE_BOOL);
            $data = $this->preguntaResultadosService->getInmuebleVotos($pregunta, $ocultarRespuesta);
        } catch (\RuntimeException) {
            return response()->json([
                'message' => 'No hay información disponible para esta pregunta.',
            ], 422);
        }

        return response()->json([
            'data' => $data,
        ], 200);
    }
}
