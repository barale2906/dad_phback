<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voto\StoreVotoRequest;
use App\Http\Resources\VotoResource;
use App\Jobs\RegistrarVotoJob;
use App\Models\Voto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VotoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Voto::class);

        $query = Voto::query()
            ->with(['pregunta', 'opcion', 'inmueble', 'asistente'])
            ->latest('votado_at');

        if ($request->filled('pregunta_id')) {
            $query->where('pregunta_id', $request->integer('pregunta_id'));
        }

        $votos = $query->paginate(50);

        return VotoResource::collection($votos)->response();
    }

    public function store(StoreVotoRequest $request): JsonResponse
    {
        $data = $request->validated();

        Gate::authorize('create', Voto::class);

        RegistrarVotoJob::dispatch(
            $data['pregunta_id'],
            $data['opcion_id'],
            $data['inmueble_id'] ?? null,
            $data['asistente_id'] ?? null,
            $data['telefono'] ?? null
        );

        return response()->json([
            'message' => 'Voto recibido y en cola para procesamiento.',
            'status' => 'queued',
        ], 202);
    }

    public function show(Voto $voto): JsonResponse
    {
        Gate::authorize('view', $voto);

        return (new VotoResource($voto->load(['pregunta', 'opcion', 'inmueble', 'asistente'])))
            ->response();
    }
}

