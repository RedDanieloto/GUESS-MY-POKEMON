<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectBannedUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();

        if ($user && (bool) $user->is_banned) {
            return response()->json([
                'message' => 'Tu cuenta está suspendida',
                'reason' => $user->banned_reason,
            ], 403);
        }

        return $next($request);
    }
}
