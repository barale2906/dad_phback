<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use App\Http\Requests\Inmueble\CargaMasivaInmuebleRequest;
use App\Http\Requests\Inmueble\StoreInmuebleRequest;
use App\Http\Requests\Inmueble\UpdateInmuebleRequest;
use App\Http\Resources\InmuebleResource;
use App\Models\Inmueble;
use App\Services\InmuebleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

#[Group('Inmuebles', weight: 3)]
class InmuebleController extends Controller
{
    public function __construct(private readonly InmuebleService $inmuebleService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Inmueble::class);

        $query = Inmueble::query();

        if (request()->filled('activo')) {
            $query->where('activo', filter_var(request()->query('activo'), FILTER_VALIDATE_BOOL));
        }

        if (request()->filled('tipo')) {
            $query->where('tipo', (string) request()->query('tipo'));
        }

        if (request()->filled('nomenclatura')) {
            $query->where('nomenclatura', 'like', '%'.request()->query('nomenclatura').'%');
        }

        return InmuebleResource::collection($query->orderBy('nomenclatura')->paginate(20));
    }

    public function store(StoreInmuebleRequest $request): JsonResponse
    {
        Gate::authorize('create', Inmueble::class);

        $inmueble = Inmueble::query()->create($request->validated());

        return response()->json([
            'message' => 'Inmueble creado correctamente.',
            'data' => new InmuebleResource($inmueble),
        ], 201);
    }

    public function show(Inmueble $inmueble): InmuebleResource
    {
        Gate::authorize('view', $inmueble);

        return new InmuebleResource($inmueble);
    }

    public function update(UpdateInmuebleRequest $request, Inmueble $inmueble): JsonResponse
    {
        Gate::authorize('update', $inmueble);

        $inmueble->update($request->validated());

        return response()->json([
            'message' => 'Inmueble actualizado correctamente.',
            'data' => new InmuebleResource($inmueble->fresh()),
        ], 200);
    }

    public function destroy(Inmueble $inmueble): JsonResponse
    {
        Gate::authorize('delete', $inmueble);

        $this->inmuebleService->softDelete($inmueble);

        return response()->json([
            'message' => 'Inmueble eliminado correctamente.',
        ], 200);
    }

    public function cargaMasiva(CargaMasivaInmuebleRequest $request): JsonResponse
    {
        Gate::authorize('create', Inmueble::class);

        $result = $this->inmuebleService->cargaMasiva($request->file('archivo'));

        $hasErrors = ! empty($result['errores']);

        return response()->json([
            'message' => $hasErrors ? 'Carga masiva finalizada con errores.' : 'Carga masiva finalizada correctamente.',
            'data' => $result,
        ], $hasErrors ? 422 : 200);
    }

    public function validarCoeficientes(): JsonResponse
    {
        Gate::authorize('viewAny', Inmueble::class);

        return response()->json([
            'data' => $this->inmuebleService->validarCoeficientes(),
        ], 200);
    }
}
