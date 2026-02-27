<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Convocatoria\StoreConvocatoriaRequest;
use App\Http\Requests\Convocatoria\UpdateConvocatoriaRequest;
use App\Http\Resources\ConvocatoriaResource;
use App\Models\Convocatoria;
use App\Models\Reunion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ConvocatoriaController extends Controller
{
    public function show(Reunion $reunion): JsonResponse
    {
        Gate::authorize('view', $reunion);

        $convocatoria = $reunion->convocatoria;

        return response()->json([
            'data' => $convocatoria ? new ConvocatoriaResource($convocatoria) : null,
        ], 200);
    }

    public function store(StoreConvocatoriaRequest $request, Reunion $reunion): JsonResponse
    {
        Gate::authorize('update', $reunion);

        if ($reunion->convocatoria()->exists()) {
            return response()->json([
                'message' => 'La reunion ya tiene convocatoria registrada.',
            ], 409);
        }

        $convocatoria = Convocatoria::query()->create(array_merge(
            $request->validated(),
            ['reunion_id' => $reunion->id]
        ));

        return response()->json([
            'message' => 'Convocatoria creada correctamente.',
            'data' => new ConvocatoriaResource($convocatoria),
        ], 201);
    }

    public function update(UpdateConvocatoriaRequest $request, Convocatoria $convocatoria): JsonResponse
    {
        Gate::authorize('update', $convocatoria);

        $convocatoria->update($request->validated());

        return response()->json([
            'message' => 'Convocatoria actualizada correctamente.',
            'data' => new ConvocatoriaResource($convocatoria->fresh()),
        ], 200);
    }

    public function enviar(Convocatoria $convocatoria): JsonResponse
    {
        Gate::authorize('update', $convocatoria);

        $convocatoria->update(['estado' => 'enviada']);

        return response()->json([
            'message' => 'Convocatoria marcada como enviada.',
            'data' => new ConvocatoriaResource($convocatoria->fresh()),
        ], 200);
    }

    public function publicar(Convocatoria $convocatoria): JsonResponse
    {
        Gate::authorize('update', $convocatoria);

        $convocatoria->update(['estado' => 'publicada']);

        return response()->json([
            'message' => 'Convocatoria marcada como publicada.',
            'data' => new ConvocatoriaResource($convocatoria->fresh()),
        ], 200);
    }
}
