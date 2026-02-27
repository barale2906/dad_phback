<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timer\StoreTimerRequest;
use App\Http\Requests\Timer\UpdateTimerRequest;
use App\Http\Resources\TimerResource;
use App\Models\Timer;
use App\Services\TimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class TimerController extends Controller
{
    public function __construct(private readonly TimerService $timerService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Timer::class);

        $query = Timer::query();

        if (request()->filled('reunion_id')) {
            $query->where('reunion_id', (int) request()->query('reunion_id'));
        }
        if (request()->filled('tipo')) {
            $query->where('tipo', request()->query('tipo'));
        }
        if (request()->filled('estado')) {
            $query->where('estado', request()->query('estado'));
        }

        return TimerResource::collection($query->orderByDesc('id')->paginate(20));
    }

    public function store(StoreTimerRequest $request): JsonResponse
    {
        Gate::authorize('create', Timer::class);

        $timer = Timer::query()->create([
            'reunion_id' => $request->integer('reunion_id'),
            'tipo' => $request->string('tipo')->value(),
            'duracion_segundos' => $request->integer('duracion_segundos'),
            'estado' => $request->input('estado', 'inactivo'),
            'interviniente_nombre' => $request->input('interviniente_nombre'),
            'interviniente_asistente_id' => $request->input('interviniente_asistente_id'),
        ]);

        return response()->json([
            'message' => 'Timer creado correctamente.',
            'data' => new TimerResource($timer),
        ], 201);
    }

    public function show(Timer $timer): TimerResource
    {
        Gate::authorize('view', $timer);

        return new TimerResource($timer);
    }

    public function update(UpdateTimerRequest $request, Timer $timer): JsonResponse
    {
        Gate::authorize('update', $timer);

        $timer->update($request->validated());

        return response()->json([
            'message' => 'Timer actualizado correctamente.',
            'data' => new TimerResource($timer->fresh()),
        ], 200);
    }

    public function destroy(Timer $timer): JsonResponse
    {
        Gate::authorize('delete', $timer);

        if ($timer->estado === 'activo') {
            return response()->json([
                'message' => 'No se puede eliminar un timer activo.',
            ], 409);
        }

        $timer->delete();

        return response()->json([
            'message' => 'Timer eliminado correctamente.',
        ], 200);
    }

    public function iniciar(Timer $timer): JsonResponse
    {
        Gate::authorize('update', $timer);

        try {
            $this->timerService->iniciar($timer);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Timer iniciado correctamente.',
            'data' => new TimerResource($timer->fresh()),
        ], 200);
    }

    public function pausar(Timer $timer): JsonResponse
    {
        Gate::authorize('update', $timer);

        try {
            $this->timerService->pausar($timer);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Timer pausado correctamente.',
            'data' => new TimerResource($timer->fresh()),
        ], 200);
    }
}
