<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithTokenOrProfileToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if ($request->user()) {
            return $next($request);
        }

        // Check if valid player_token is provided (for anonymous players)
        $playerToken = $request->input('player_token') ?? '';
        if (trim((string) $playerToken) !== '') {
            // Validate that player_token exists in database
            $profile = \App\Models\PlayerProfile::query()
                ->where('session_id', $playerToken)
                ->first();

            if ($profile) {
                // Store the profile in request for later use
                $request->merge(['_authenticated_profile' => $profile]);
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
