<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reunion;
use App\Services\QuorumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuorumController extends Controller
{
    public function __construct(private readonly QuorumService $service)
    {
    }

    public function actual(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Reunion::class);

        $reunion = null;

        if ($request->filled('reunion_id')) {
            $reunion = Reunion::query()->find($request->integer('reunion_id'));
        }

        $data = $this->service->calcularQuorum($reunion);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function crearPregunta(Request $request): JsonResponse
    {
        $request->validate([
            'reunion_id' => ['required', 'integer', 'exists:reuniones,id'],
        ]);

        $reunion = Reunion::query()->findOrFail($request->integer('reunion_id'));

        Gate::authorize('update', $reunion);

        $pregunta = $this->service->crearPreguntaQuorum($reunion);

        return response()->json([
            'data' => [
                'pregunta_id' => $pregunta->id,
            ],
        ], 201);
    }
}

