<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        // Stale CSRF token (419) — happens when a form was loaded before the
        // session expired or was rotated by another tab (log in/out
        // elsewhere), then submitted after. Typed on the HttpException the
        // framework maps TokenMismatchException into (prepareException runs
        // before render callbacks, so the original type never reaches here).
        $exceptions->render(function (HttpException $e, Request $request) {
            if (! $e->getPrevious() instanceof TokenMismatchException || $request->expectsJson()) {
                return null;
            }

            // A stale logout click should never be an error page: the token
            // is only ever stale because the session it belonged to is
            // already gone — so just finish the job and land on the
            // homepage like any normal sign-out.
            if ($request->is('logout')) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/');
            }

            // Any other expired form: back to it with a friendly note (and
            // the typed input preserved) instead of the bare 419 page.
            return back()
                ->withErrors(['email' => 'Your session expired — please try submitting again.'])
                ->withInput($request->except(['password', 'password_confirmation', '_token']));
        });
    })->create();
