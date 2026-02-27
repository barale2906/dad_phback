<?php

namespace App\Http\Middleware;

use App\Models\Ph;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = Ph::query()->whereNotNull('installed_at')->exists();

        if (! $installed) {
            return response()->json([
                'message' => 'El sistema aun no ha sido instalado.',
                'status' => 'installation_required',
            ], 409);
        }

        return $next($request);
    }
}
