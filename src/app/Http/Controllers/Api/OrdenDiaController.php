<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrdenDia\MarcarEjecutadoRequest;
use App\Http\Requests\OrdenDia\ReorderOrdenDiaRequest;
use App\Http\Requests\OrdenDia\StoreOrdenDiaItemRequest;
use App\Http\Requests\OrdenDia\UpdateOrdenDiaItemRequest;
use App\Http\Resources\OrdenDiaItemResource;
use App\Models\OrdenDiaItem;
use App\Models\Reunion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrdenDiaController extends Controller
{
    public function index(Reunion $reunion): AnonymousResourceCollection
    {
        Gate::authorize('view', $reunion);

        return OrdenDiaItemResource::collection(
            $reunion->ordenDiaItems()->orderBy('orden')->get()
        );
    }

    public function store(StoreOrdenDiaItemRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        $maxOrden = (int) $reunion->ordenDiaItems()->max('orden');
        $orden = $request->integer('orden') ?: ($maxOrden + 1);

        $item = OrdenDiaItem::query()->create([
            'reunion_id' => $reunion->id,
            'titulo' => $request->string('titulo')->value(),
            'descripcion' => $request->input('descripcion'),
            'orden' => $orden,
            'ejecutado' => false,
        ]);

        return response()->json([
            'message' => 'Punto del orden del dia creado correctamente.',
            'data' => new OrdenDiaItemResource($item),
        ], 201);
    }

    public function update(UpdateOrdenDiaItemRequest $request, OrdenDiaItem $item): JsonResponse
    {
        Gate::authorize('update', $item);

        $item->update($request->validated());

        return response()->json([
            'message' => 'Punto del orden del dia actualizado correctamente.',
            'data' => new OrdenDiaItemResource($item->fresh()),
        ], 200);
    }

    public function reordenar(ReorderOrdenDiaRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        DB::transaction(function () use ($request, $reunion): void {
            foreach ($request->validated('items') as $itemData) {
                OrdenDiaItem::query()
                    ->where('reunion_id', $reunion->id)
                    ->where('id', $itemData['id'])
                    ->update(['orden' => $itemData['orden']]);
            }
        });

        return response()->json([
            'message' => 'Orden del dia reordenado correctamente.',
            'data' => OrdenDiaItemResource::collection(
                $reunion->ordenDiaItems()->orderBy('orden')->get()
            ),
        ], 200);
    }

    public function marcarEjecutado(MarcarEjecutadoRequest $request, OrdenDiaItem $item): JsonResponse
    {
        Gate::authorize('update', $item);

        $item->update([
            'ejecutado' => $request->boolean('ejecutado', true),
        ]);

        return response()->json([
            'message' => 'Estado de ejecucion del punto actualizado correctamente.',
            'data' => new OrdenDiaItemResource($item->fresh()),
        ], 200);
    }
}
