<?php

namespace App\Http\Middleware;

use App\Services\SupabaseAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Session-based equivalent of SupabaseAuth for Blade routes: reads the user
// established at login time from the session instead of verifying a bearer
// token on every request. Attaches `supabase_user` the same way, so
// Controller::supabaseUser() (and effectiveRole/isAdmin/etc built on it) work
// unchanged regardless of which middleware populated it.
class EnsureSupabaseSession
{
    public function __construct(private SupabaseAuthService $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->session()->get('supabase_user');
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $expiresAt = $request->session()->get('supabase_token_expires_at');
        if ($expiresAt && now()->timestamp >= $expiresAt) {
            $refreshed = $this->refresh($request);
            if (! $refreshed) {
                $request->session()->forget(['supabase_user', 'supabase_access_token', 'supabase_refresh_token', 'supabase_token_expires_at']);

                return redirect()->guest(route('login'));
            }
            $user = $request->session()->get('supabase_user');
        }

        $request->attributes->set('supabase_user', $user);

        // Google/Facebook sign-ups arrive without a phone number — make them
        // add one before using customer pages (port of the SPA ProtectedRoute
        // guard). Admin pages are exempt, mirroring the old AdminRoute, which
        // never checked. Sessions from before contact_number was tracked lack
        // the key entirely and are left alone until their next login/refresh.
        if (array_key_exists('contact_number', $user)
            && ($user['contact_number'] === null || $user['contact_number'] === '')
            && ! $request->routeIs('complete-profile', 'complete-profile.store')
            && ! $request->routeIs('admin.*')) {
            return redirect()->route('complete-profile');
        }

        return $next($request);
    }

    private function refresh(Request $request): bool
    {
        $refreshToken = $request->session()->get('supabase_refresh_token');
        if (! $refreshToken) {
            return false;
        }

        $result = $this->auth->refresh($refreshToken);
        if (! $result) {
            return false;
        }

        $request->session()->put([
            'supabase_user' => $result['user'],
            'supabase_access_token' => $result['access_token'],
            'supabase_refresh_token' => $result['refresh_token'],
            'supabase_token_expires_at' => now()->addSeconds($result['expires_in'])->timestamp,
        ]);

        return true;
    }
}
