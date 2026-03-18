<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        if (! $user || ! (bool) $user->is_admin) {
            return response()->json(['message' => 'Acceso solo para administradores'], 403);
        }

        return $next($request);
    }
}
