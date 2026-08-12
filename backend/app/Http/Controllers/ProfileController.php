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

        return view('profile', [
            'profile' => [
                'email' => $raw['email'] ?? ($sessionUser['email'] ?? ''),
                'name' => $meta['full_name'] ?? $meta['name'] ?? ($sessionUser['name'] ?? ''),
                'contact' => (string) ($meta['contact_number'] ?? ''),
                'addresses' => $this->readAddresses($meta),
                'avatar' => trim((string) ($meta['avatar_url'] ?? '')),
                'memberSince' => $raw['created_at'] ?? null,
                'providers' => $providers,
            ],
            'nav' => $this->navConfig((array) app(SiteContentController::class)->cachedData()),
        ]);
    }

    /**
     * Labeled addresses (Home/Work/Partner/etc.) live under user_metadata's
     * `addresses` key as an array of {label, address}. Older accounts only
     * ever saved a single unlabeled `address` string — read that as one
     * label-less entry so nothing saved before this feature existed appears
     * to have vanished.
     */
    private function readAddresses(array $meta): array
    {
        $addresses = (array) ($meta['addresses'] ?? []);
        if (! $addresses && trim((string) ($meta['address'] ?? '')) !== '') {
            $addresses = [['label' => '', 'address' => (string) $meta['address']]];
        }

        return collect($addresses)
            ->map(fn ($a) => ['label' => (string) ($a['label'] ?? ''), 'address' => (string) ($a['address'] ?? '')])
            ->values()->all();
    }

    /** Name + contact number (the "Account details" form). */
    public function updateInfo(Request $request)
    {
        $user = $this->supabaseUser($request);
        $token = (string) $request->session()->get('supabase_access_token');

        $data = $request->validateWithBag('info', [
            'name' => 'required|string|min:2|max:120',
            'contact_number' => 'required|string|max:20',
            'addresses' => 'nullable|array',
            'addresses.*.label' => 'nullable|string|max:60',
            'addresses.*.address' => 'nullable|string|max:500',
        ], [], ['contact_number' => 'contact number']);

        // Repeater rows may include ones the visitor added then left blank —
        // drop anything with no address text (a label alone isn't a saved
        // address), then reindex.
        $addresses = collect($data['addresses'] ?? [])
            ->map(fn ($a) => ['label' => trim((string) ($a['label'] ?? '')), 'address' => trim((string) ($a['address'] ?? ''))])
            ->filter(fn ($a) => $a['address'] !== '')
            ->values()->all();

        // PH mobile numbers only: local 11-digit (09XXXXXXXXX) or
        // international (+639XXXXXXXXX) form.
        $phone = SupabaseAuthService::normalizePhContactNumber($data['contact_number']);
        if (! $phone) {
            return back()->withErrors(['contact_number' => 'Please enter a valid 11-digit PH mobile number, e.g. 09123456789.'], 'info')->withInput();
        }
        ['contact' => $contact, 'normalized' => $normalized] = $phone;

        // Claim the normalized number first — it's the update that can fail on
        // uniqueness, so we don't half-save the name if the number is taken.
        if ($error = $this->auth->claimContactNumber((string) ($user['id'] ?? ''), $normalized)) {
            return back()->withErrors(['contact_number' => $error], 'info')->withInput();
        }

        [, $error] = $this->auth->updateUser($token, ['data' => [
            'full_name' => trim($data['name']),
            'contact_number' => $contact, // user-entered form, for display
            'addresses' => $addresses,
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
