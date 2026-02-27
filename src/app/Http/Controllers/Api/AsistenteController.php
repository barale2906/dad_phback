<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asistente\StoreAsistenteRequest;
use App\Http\Requests\Asistente\UpdateAsistenteRequest;
use App\Http\Resources\AsistenteResource;
use App\Models\Asistente;
use App\Services\AsistenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class AsistenteController extends Controller
{
    public function __construct(private readonly AsistenteService $asistenteService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Asistente::class);

        $query = Asistente::query()->with('inmuebles');

        if (request()->filled('nombre')) {
            $query->where('nombre', 'like', '%'.request()->query('nombre').'%');
        }

        if (request()->filled('documento')) {
            $query->where('documento', (string) request()->query('documento'));
        }

        return AsistenteResource::collection($query->orderBy('nombre')->paginate(20));
    }

    public function store(StoreAsistenteRequest $request): JsonResponse
    {
        Gate::authorize('create', Asistente::class);

        try {
            $asistente = $this->asistenteService->create($request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Asistente creado correctamente.',
            'data' => new AsistenteResource($asistente),
        ], 201);
    }

    public function show(Asistente $asistente): AsistenteResource
    {
        Gate::authorize('view', $asistente);

        return new AsistenteResource($asistente->load('inmuebles'));
    }

    public function update(UpdateAsistenteRequest $request, Asistente $asistente): JsonResponse
    {
        Gate::authorize('update', $asistente);

        try {
            $updated = $this->asistenteService->update($asistente, $request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Asistente actualizado correctamente.',
            'data' => new AsistenteResource($updated),
        ], 200);
    }

    public function destroy(Asistente $asistente): JsonResponse
    {
        Gate::authorize('delete', $asistente);

        $this->asistenteService->delete($asistente);

        return response()->json([
            'message' => 'Asistente eliminado correctamente.',
        ], 200);
    }
}
