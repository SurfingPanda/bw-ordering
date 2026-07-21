<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;

/**
 * Forgot/reset password (Blade port of the SPA's ForgotPassword.jsx +
 * ResetPassword.jsx, recovered from git history). Supabase does the heavy
 * lifting: /auth/v1/recover emails a recovery link that lands back on
 * /reset-password with a short-lived access token in the URL hash; the reset
 * form's JS moves that token into a hidden field, and the POST sets the new
 * password via the same PUT /auth/v1/user the Profile page uses. No session
 * is established at any point — the user signs in fresh afterwards, exactly
 * like the old flow.
 */
class PasswordResetController extends Controller
{
    public function __construct(private SupabaseAuthService $auth) {}

    private function authPanel(): array
    {
        return (array) ((SiteContent::find(1)?->data ?? [])['authPanel'] ?? []);
    }

    /** GET /forgot-password — email form, or the "link sent" state after POST. */
    public function request(Request $request)
    {
        if ($request->session()->has('supabase_user')) {
            return redirect()->route('menu');
        }

        return view('auth.forgot-password', ['authPanel' => $this->authPanel()]);
    }

    /** POST /forgot-password — ask Supabase to email the recovery link. */
    public function email(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        // The link must come back to *this* origin's /reset-password (mirrors
        // the SPA's window.location.origin) — remember to allow-list it in
        // Supabase → Auth → URL Configuration → Redirect URLs.
        $error = $this->auth->sendPasswordReset(
            $data['email'],
            $request->getSchemeAndHttpHost().'/reset-password'
        );
        if ($error) {
            return back()->withErrors(['email' => $error])->withInput();
        }

        // Success regardless of whether the account exists (anti-enumeration,
        // same wording as the SPA's confirmation panel).
        return redirect()->route('password.request')->with('resetSent', $data['email']);
    }

    /** GET /reset-password — landed on from the email's recovery link. */
    public function reset()
    {
        return view('auth.reset-password', ['authPanel' => $this->authPanel()]);
    }

    /** POST /reset-password — set the new password on the recovery token. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'access_token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'access_token.required' => 'This reset link is invalid or has expired. Please request a new one.',
            'password.min' => 'Password should be at least 6 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ]);

        [, $error] = $this->auth->updateUser($data['access_token'], ['password' => $data['password']]);
        if ($error) {
            return back()->withErrors([
                'form' => 'Unable to reset your password — the link may have expired. Please request a new one.',
            ])->withInput($request->only('access_token'));
        }

        return redirect()->route('login')->with('status', 'Password updated! Please sign in with your new password.');
    }
}
