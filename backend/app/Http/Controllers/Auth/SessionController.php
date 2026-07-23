<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function __construct(private SupabaseAuthService $auth) {}

    public function create(Request $request)
    {
        // Already signed in → straight to their landing page instead of the
        // form (mirrors the SPA, which never showed /login to a session).
        // Exception: the Site Editor's preview iframe (?preview=1) — the
        // signed-in editor is deliberately previewing this page's design.
        if (! $request->query('preview') && $request->session()->has('supabase_user')) {
            return redirect($this->landingRoute($request->session()->get('supabase_user')['email'] ?? null));
        }

        // Site Editor live preview (?preview=1, editor session) shows the
        // unsaved draft's auth panel; everyone else sees the saved blob.
        $content = $this->previewDraft($request) ?? (SiteContent::find(1)?->data ?? []);

        return view('auth.login', [
            'authPanel' => $content['authPanel'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        [$result, $reason] = $this->auth->passwordGrant($credentials['email'], $credentials['password']);
        if (! $result) {
            // A registered-but-unverified account gets a specific prompt (with
            // a "resend" option), not the misleading "invalid password" — the
            // login view keys off the flashed `unconfirmed_email`.
            if ($reason === 'email_not_confirmed') {
                return back()
                    ->withInput($request->only('email'))
                    ->with('unconfirmed_email', $credentials['email']);
            }

            throw ValidationException::withMessages([
                'email' => $reason === 'unavailable'
                    ? "We couldn't reach the sign-in service. Please try again in a moment."
                    : 'Invalid email or password.',
            ]);
        }

        $this->establishSession($request, $result);

        $redirectTo = $request->session()->pull('url.intended', $this->landingRoute($result['user']['email']));
        // Customers without a contact number go straight to the intake step
        // (staff accounts are exempt, mirroring the old AdminRoute) —
        // checked by role, not by matching the computed redirect against
        // /menu, so this still fires even when `url.intended` points
        // somewhere else (e.g. they were bounced to /login from /checkout).
        if (empty($result['user']['contact_number']) && ! $this->isAdmin($result['user']['email']) && ! $this->isEditor($result['user']['email'])) {
            $redirectTo = route('complete-profile');
        }

        return redirect($redirectTo);
    }

    /** Kick off Google/Facebook sign-in via Supabase's OAuth authorize endpoint. */
    public function oauthRedirect(Request $request, string $provider)
    {
        $base = rtrim((string) config('supabase.url'), '/');
        abort_unless($base !== '', 404);

        return redirect()->away($base.'/auth/v1/authorize?'.http_build_query([
            'provider' => $provider,
            'redirect_to' => route('oauth.callback'),
        ]));
    }

    /**
     * Where Supabase sends the browser back after the provider consent screen.
     * The tokens live in the URL #fragment, which the server never receives —
     * this page's JS forwards them via POST (oauthStore) to establish the
     * Laravel session.
     */
    public function oauthCallback()
    {
        return view('auth.callback');
    }

    /** Verify the forwarded OAuth tokens and establish the session. */
    public function oauthStore(Request $request)
    {
        $data = $request->validate([
            'access_token' => ['required', 'string'],
            'refresh_token' => ['required', 'string'],
            'expires_in' => ['nullable', 'integer'],
        ]);

        // The token is verified (HS256 signature / GoTrue lookup) before being
        // trusted — the client can only hand us a JWT Supabase actually issued.
        $user = $this->auth->verifyAccessToken($data['access_token']);
        if (! $user || empty($user['id'])) {
            return redirect()->route('login', ['oauth' => 'failed']);
        }

        $this->establishSession($request, [
            'user' => $user,
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
        ]);

        $redirectTo = $request->session()->pull('url.intended', $this->landingRoute($user['email'] ?? null));
        // First-time Google/Facebook sign-ins have no phone number yet — send
        // them to the intake step (staff accounts are exempt) — checked by
        // role, not by matching the computed redirect against /menu, so this
        // still fires even when `url.intended` points somewhere else.
        if (empty($user['contact_number']) && ! $this->isAdmin($user['email'] ?? null) && ! $this->isEditor($user['email'] ?? null)) {
            $redirectTo = route('complete-profile');
        }

        return redirect($redirectTo);
    }

    public function destroy(Request $request)
    {
        $accessToken = $request->session()->get('supabase_access_token');
        if ($accessToken) {
            $this->auth->logout($accessToken);
        }

        $request->session()->forget(['supabase_user', 'supabase_access_token', 'supabase_refresh_token', 'supabase_token_expires_at']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function establishSession(Request $request, array $result): void
    {
        $request->session()->regenerate();
        $request->session()->put([
            'supabase_user' => $result['user'],
            'supabase_access_token' => $result['access_token'],
            'supabase_refresh_token' => $result['refresh_token'],
            'supabase_token_expires_at' => now()->addSeconds($result['expires_in'])->timestamp,
        ]);
    }

    /**
     * Mirrors frontend/src/pages/Login.jsx's landingRoute(): admin → /admin,
     * editor → the Site Editor, customers → the menu (the SPA's /dashboard
     * rendered the Menu page).
     */
    private function landingRoute(?string $email): string
    {
        if ($this->isAdmin($email)) {
            return route('admin.dashboard');
        }
        if ($this->isEditor($email)) {
            return route('admin.content');
        }

        return route('menu');
    }
}
