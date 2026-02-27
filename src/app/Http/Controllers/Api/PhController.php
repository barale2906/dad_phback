<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use App\Http\Requests\Ph\UpdatePhRequest;
use App\Http\Resources\PhResource;
use App\Models\Ph;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

#[Group('Propiedad Horizontal (PH)', weight: 2)]
class PhController extends Controller
{
    public function show(): PhResource
    {
        $ph = Ph::query()->firstOrFail();

        return new PhResource($ph);
    }

    public function update(UpdatePhRequest $request): JsonResponse
    {
        $ph = Ph::query()->firstOrFail();

        Gate::authorize('update', $ph);

        $ph->update($request->validated());

        return response()->json([
            'message' => 'PH actualizada correctamente.',
            'data' => new PhResource($ph->fresh()),
        ], 200);
    }
}
