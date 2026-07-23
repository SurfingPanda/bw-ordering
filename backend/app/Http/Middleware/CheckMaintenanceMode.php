<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LandingController;
use App\Http\Controllers\SiteContentController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Site-wide maintenance gate. LandingController already renders its own
// maintenance screen for "/" (it needs the full CMS blob anyway), so this
// only needs to catch every OTHER public page — otherwise a visitor could
// skip the "We'll be right back" screen just by navigating straight to
// /menu, /stores, /checkout, etc. Exempts auth (login/register/OAuth/
// password-reset), complete-profile (so a user mid-signup when maintenance
// flips on isn't stranded — EnsureSupabaseSession would otherwise keep
// redirecting them to a page this middleware also blocks), and the whole
// /admin/* shell so staff can still sign in and switch maintenance back off.
class CheckMaintenanceMode
{
    private const EXEMPT_PATTERNS = [
        '/',
        'login', 'register', 'logout',
        'forgot-password', 'reset-password',
        'auth/*',
        'complete-profile',
        'admin', 'admin/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::EXEMPT_PATTERNS as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $content = (array) app(SiteContentController::class)->cachedData();
        $maintenance = array_merge(LandingController::DEFAULT_CONTENT['maintenance'], (array) ($content['maintenance'] ?? []));

        if (! ($maintenance['enabled'] ?? false)) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'm' => $maintenance,
            'social' => (array) ($content['social'] ?? []),
        ], 503);
    }
}
