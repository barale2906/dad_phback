<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opcion\StoreOpcionRequest;
use App\Http\Requests\Opcion\UpdateOpcionRequest;
use App\Http\Resources\OpcionResource;
use App\Models\Opcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OpcionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Opcion::class);

        $query = Opcion::query();

        if (request()->filled('pregunta_id')) {
            $query->where('pregunta_id', (int) request()->query('pregunta_id'));
        }

        return OpcionResource::collection($query->orderBy('orden')->paginate(20));
    }

    public function store(StoreOpcionRequest $request): JsonResponse
    {
        Gate::authorize('create', Opcion::class);

        $opcion = Opcion::query()->create([
            'pregunta_id' => $request->integer('pregunta_id'),
            'texto' => $request->string('texto')->value(),
            'orden' => $request->integer('orden', 1),
        ]);

        return response()->json([
            'message' => 'Opcion creada correctamente.',
            'data' => new OpcionResource($opcion),
        ], 201);
    }

    public function show(Opcion $opcion): OpcionResource
    {
        Gate::authorize('view', $opcion);

        return new OpcionResource($opcion);
    }

    public function update(UpdateOpcionRequest $request, Opcion $opcion): JsonResponse
    {
        Gate::authorize('update', $opcion);

        $opcion->update($request->validated());

        return response()->json([
            'message' => 'Opcion actualizada correctamente.',
            'data' => new OpcionResource($opcion->fresh()),
        ], 200);
    }

    public function destroy(Opcion $opcion): JsonResponse
    {
        Gate::authorize('delete', $opcion);

        $opcion->delete();

        return response()->json([
            'message' => 'Opcion eliminada correctamente.',
        ], 200);
    }
}
