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

        if ($pregunta->estado !== 'cerrada') {
            return response()->json([
                'message' => 'Solo se pueden consultar resultados de preguntas cerradas.',
            ], 422);
        }

        $resultRows = $pregunta->opciones()->orderBy('orden')->get();
        $votesByOption = [];

        if (Schema::hasTable('votos')) {
            $votesByOption = DB::table('votos')
                ->selectRaw('opcion_id, COUNT(*) as total')
                ->where('pregunta_id', $pregunta->id)
                ->groupBy('opcion_id')
                ->pluck('total', 'opcion_id')
                ->map(fn ($value) => (int) $value)
                ->all();
        }

        $results = $resultRows->map(fn ($opcion) => [
            'opcion_id' => $opcion->id,
            'texto' => $opcion->texto,
            'votos' => $votesByOption[$opcion->id] ?? 0,
        ]);

        return response()->json([
            'data' => [
                'pregunta_id' => $pregunta->id,
                'estado' => $pregunta->estado,
                'resultados' => $results,
            ],
        ], 200);
    }
}
