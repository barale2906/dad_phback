<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reunion\StoreReunionRequest;
use App\Http\Requests\Reunion\UpdateReunionRequest;
use App\Http\Resources\ReunionResource;
use App\Models\Reunion;
use App\Services\ReunionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ReunionController extends Controller
{
    public function __construct(private readonly ReunionService $reunionService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Reunion::class);

        $query = Reunion::query()->with(['zonasComunes', 'convocatoria']);

        if (request()->filled('estado')) {
            $query->where('estado', request()->query('estado'));
        }
        if (request()->filled('tipo')) {
            $query->where('tipo', request()->query('tipo'));
        }

        return ReunionResource::collection($query->orderByDesc('fecha')->paginate(20));
    }

    public function store(StoreReunionRequest $request): JsonResponse
    {
        Gate::authorize('create', Reunion::class);

        $reunion = $this->reunionService->create($request->validated());

        return response()->json([
            'message' => 'Reunion creada correctamente.',
            'data' => new ReunionResource($reunion),
        ], 201);
    }

    public function show(Reunion $reunion): ReunionResource
    {
        Gate::authorize('view', $reunion);

        return new ReunionResource($reunion->load(['zonasComunes', 'convocatoria', 'ordenDiaItems']));
    }

    public function update(UpdateReunionRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        $updated = $this->reunionService->update($reunion, $request->validated());

        return response()->json([
            'message' => 'Reunion actualizada correctamente.',
            'data' => new ReunionResource($updated),
        ], 200);
    }

    public function destroy(Reunion $reunion): JsonResponse
    {
        Gate::authorize('delete', $reunion);

        if ($reunion->ordenDiaItems()->exists() || $reunion->convocatoria()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la reunion porque tiene orden del dia o convocatoria asociada.',
            ], 409);
        }

        $reunion->delete();

        return response()->json([
            'message' => 'Reunion eliminada correctamente.',
        ], 200);
    }

    public function iniciar(Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        try {
            $updated = $this->reunionService->iniciar($reunion);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Reunion iniciada correctamente.',
            'data' => new ReunionResource($updated),
        ], 200);
    }

    public function cerrar(Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        try {
            $updated = $this->reunionService->cerrar($reunion);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Reunion cerrada correctamente.',
            'data' => new ReunionResource($updated),
        ], 200);
    }
}
