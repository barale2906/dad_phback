<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Install\InstallRunRequest;
use App\Http\Resources\PhResource;
use App\Services\InstallationService;
use Illuminate\Http\JsonResponse;

class InstallController extends Controller
{
    public function __construct(private readonly InstallationService $installationService)
    {
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'installed' => $this->installationService->isInstalled(),
        ]);
    }

    public function check(): JsonResponse
    {
        return response()->json($this->installationService->checkRequirements());
    }

    public function run(InstallRunRequest $request): JsonResponse
    {
        $result = $this->installationService->run($request->validated());

        return response()->json([
            'message' => 'Instalacion completada correctamente.',
            'admin' => [
                'id' => $result['admin']->id,
                'name' => $result['admin']->name,
                'email' => $result['admin']->email,
                'rol' => $result['admin']->rol,
            ],
            'ph' => new PhResource($result['ph']),
        ], 201);
    }
}
