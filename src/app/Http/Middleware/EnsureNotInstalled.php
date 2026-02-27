<?php

namespace App\Http\Middleware;

use App\Models\Ph;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = Ph::query()->whereNotNull('installed_at')->exists();

        if ($installed) {
            return response()->json([
                'message' => 'El sistema ya fue instalado.',
                'status' => 'already_installed',
            ], 409);
        }

        return $next($request);
    }
}
