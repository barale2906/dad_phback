<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserInmueble\StoreUserInmuebleRequest;
use App\Http\Resources\InmuebleResource;
use App\Models\Inmueble;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserInmuebleController extends Controller
{
    public function index(User $user): AnonymousResourceCollection
    {
        Gate::authorize('view', $user);

        $inmuebles = $user->inmuebles()->orderBy('nomenclatura')->get();

        return InmuebleResource::collection($inmuebles);
    }

    public function store(StoreUserInmuebleRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $payload = $request->validated();
        $inmuebleId = (int) $payload['inmueble_id'];
        $inmueble = Inmueble::query()->findOrFail($inmuebleId);

        $alreadyExists = $user->inmuebles()
            ->where('inmueble_id', $inmuebleId)
            ->wherePivot('relacion', $payload['relacion'])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'message' => 'La relacion usuario-inmueble ya existe para ese tipo de relacion.',
            ], 409);
        }

        $user->inmuebles()->attach($inmuebleId, [
            'relacion' => $payload['relacion'],
            'es_principal' => $payload['es_principal'] ?? false,
            'fecha_inicio' => $payload['fecha_inicio'] ?? null,
            'fecha_fin' => $payload['fecha_fin'] ?? null,
        ]);

        return response()->json([
            'message' => 'Relacion usuario-inmueble creada correctamente.',
            'data' => [
                'user_id' => $user->id,
                'inmueble' => new InmuebleResource($inmueble),
                'relacion' => $payload['relacion'],
            ],
        ], 201);
    }

    public function destroy(User $user, Inmueble $inmueble): JsonResponse
    {
        Gate::authorize('update', $user);

        $user->inmuebles()->detach($inmueble->id);

        return response()->json([
            'message' => 'Relaciones usuario-inmueble eliminadas para el inmueble indicado.',
        ], 200);
    }
}
