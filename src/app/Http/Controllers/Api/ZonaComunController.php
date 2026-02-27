<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ZonaComun\StoreZonaComunRequest;
use App\Http\Requests\ZonaComun\UpdateZonaComunRequest;
use App\Http\Resources\ZonaComunResource;
use App\Models\ZonaComun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ZonaComunController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ZonaComun::class);

        $query = ZonaComun::query();

        if (request()->filled('tipo')) {
            $query->where('tipo', request()->query('tipo'));
        }

        return ZonaComunResource::collection($query->orderBy('nombre')->paginate(20));
    }

    public function store(StoreZonaComunRequest $request): JsonResponse
    {
        Gate::authorize('create', ZonaComun::class);

        $zona = ZonaComun::query()->create($request->validated());

        return response()->json([
            'message' => 'Zona comun creada correctamente.',
            'data' => new ZonaComunResource($zona),
        ], 201);
    }

    public function show(ZonaComun $zona): ZonaComunResource
    {
        Gate::authorize('view', $zona);

        return new ZonaComunResource($zona);
    }

    public function update(UpdateZonaComunRequest $request, ZonaComun $zona): JsonResponse
    {
        Gate::authorize('update', $zona);

        $zona->update($request->validated());

        return response()->json([
            'message' => 'Zona comun actualizada correctamente.',
            'data' => new ZonaComunResource($zona->fresh()),
        ], 200);
    }

    public function destroy(ZonaComun $zona): JsonResponse
    {
        Gate::authorize('delete', $zona);

        $zona->delete();

        return response()->json([
            'message' => 'Zona comun eliminada correctamente.',
        ], 200);
    }
}
