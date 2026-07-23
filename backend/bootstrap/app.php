<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'supabase' => \App\Http\Middleware\SupabaseAuth::class,
            'supabase.session' => \App\Http\Middleware\EnsureSupabaseSession::class,
        ]);
        // Global, not opt-in per route: a maintenance toggle should hold for
        // every page by default, including ones added later, rather than
        // relying on each new route remembering to add it.
        $middleware->web(append: [\App\Http\Middleware\CheckMaintenanceMode::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Throttled auth form posts (429 from throttle:5,1) return to the form
        // with a friendly message in its existing error slot instead of the
        // bare "429 Too Many Requests" error page. API clients still get the
        // plain JSON 429.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $seconds = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            return back()
                ->withErrors(['email' => "Too many attempts. Please wait {$seconds} seconds and try again."])
                ->withInput($request->except(['password', 'password_confirmation', '_token']));
        });
    })->create();
