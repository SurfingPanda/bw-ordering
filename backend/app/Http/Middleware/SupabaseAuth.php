<?php

namespace App\Http\Middleware;

use App\Services\SupabaseAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Verifies a Supabase access token (Bearer header) and attaches a normalized
// user to the request as `supabase_user`. Used by the legacy per-request API
// (routes/api.php) — the Blade session bridge uses EnsureSupabaseSession
// instead, but both share the same verification logic via SupabaseAuthService.
class SupabaseAuth
{
    public function __construct(private SupabaseAuthService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = $this->auth->verifyAccessToken($token);
        if (! $user) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $request->attributes->set('supabase_user', $user);

        return $next($request);
    }
}
