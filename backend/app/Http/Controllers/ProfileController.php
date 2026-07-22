<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;

/**
 * Account settings page (Blade port of the SPA's Profile.jsx). Behind
 * `supabase.session`, reached from the menu header's account dropdown.
 * Name/contact live in Supabase auth user_metadata; the contact number is
 * additionally "claimed" in the Supabase `profiles` table, whose UNIQUE
 * constraint enforces one-account-per-number (same as the old AuthContext).
 */
class ProfileController extends Controller
{
    public function __construct(private SupabaseAuthService $auth)
    {
    }

    public function show(Request $request)
    {
        $sessionUser = $this->supabaseUser($request);
        $token = (string) $request->session()->get('supabase_access_token');
        $raw = $token !== '' ? $this->auth->fetchUser($token) : null;
        $meta = (array) ($raw['user_metadata'] ?? []);

        // Identities carry one entry per linked sign-in method (google,
        // facebook, email); GoTrue lists the password identity as "email".
        // Used only for the read-only "how you sign in" badge below.
        $providers = collect((array) ($raw['identities'] ?? []))
            ->pluck('provider')->filter()->unique()->values()->all();

        return view('profile', ['profile' => [
            'email' => $raw['email'] ?? ($sessionUser['email'] ?? ''),
            'name' => $meta['full_name'] ?? $meta['name'] ?? ($sessionUser['name'] ?? ''),
            'contact' => (string) ($meta['contact_number'] ?? ''),
            'address' => (string) ($meta['address'] ?? ''),
            'avatar' => trim((string) ($meta['avatar_url'] ?? '')),
            'memberSince' => $raw['created_at'] ?? null,
            'providers' => $providers,
        ]]);
    }

    /** Name + contact number (the "Account details" form). */
    public function updateInfo(Request $request)
    {
        $user = $this->supabaseUser($request);
        $token = (string) $request->session()->get('supabase_access_token');

        $data = $request->validateWithBag('info', [
            'name' => 'required|string|min:2|max:120',
            'contact_number' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
        ], [], ['contact_number' => 'contact number']);

        // Same rules as the old lib/phone.js: strip anything that isn't a
        // digit or common phone symbol, then require at least 7 digits.
        $contact = trim((string) preg_replace('/[^\d+\-\s()]/', '', $data['contact_number']));
        $digits = preg_replace('/\D/', '', $contact);
        if (strlen($digits) < 7) {
            return back()->withErrors(['contact_number' => 'Please enter a valid contact number.'], 'info')->withInput();
        }

        // Claim the normalized number first — it's the update that can fail on
        // uniqueness, so we don't half-save the name if the number is taken.
        $normalized = str_starts_with($contact, '+') ? "+{$digits}" : $digits;
        if ($error = $this->auth->claimContactNumber((string) ($user['id'] ?? ''), $normalized)) {
            return back()->withErrors(['contact_number' => $error], 'info')->withInput();
        }

        [, $error] = $this->auth->updateUser($token, ['data' => [
            'full_name' => trim($data['name']),
            'contact_number' => $contact, // user-entered form, for display
            'address' => trim((string) ($data['address'] ?? '')),
        ]]);
        if ($error) {
            return back()->withErrors(['name' => $error], 'info')->withInput();
        }

        // Keep the session copy (menu header greeting, phone-number gate) in sync.
        $request->session()->put('supabase_user', array_merge(
            (array) $request->session()->get('supabase_user'),
            ['name' => trim($data['name']), 'contact_number' => $contact],
        ));

        return back()->with('info_success', 'Your information has been updated.');
    }

    /** New password (the "Change password" form). */
    public function updatePassword(Request $request)
    {
        $token = (string) $request->session()->get('supabase_access_token');

        $data = $request->validateWithBag('password', [
            'password' => 'required|string|min:6|confirmed',
        ]);

        [, $error] = $this->auth->updateUser($token, ['password' => $data['password']]);
        if ($error) {
            return back()->withErrors(['password' => $error], 'password');
        }

        return back()->with('pw_success', 'Your password has been updated.');
    }
}
