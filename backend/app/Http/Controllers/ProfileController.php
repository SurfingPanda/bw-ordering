<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuthService;
use App\Services\DeliveryAddressService;
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
    public function __construct(private SupabaseAuthService $auth, private DeliveryAddressService $addresses)
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
                'addresses' => $this->addresses->read((string) $sessionUser['id'], $raw ? $meta : null),
                'avatar' => trim((string) ($meta['avatar_url'] ?? '')),
                'memberSince' => $raw['created_at'] ?? null,
                'providers' => $providers,
            ],
            'nav' => $this->navConfig((array) app(SiteContentController::class)->cachedData()),
        ]);
    }

    /** Name + contact number (the "Account details" form). */
    public function updateInfo(Request $request)
    {
        $user = $this->supabaseUser($request);
        $token = (string) $request->session()->get('supabase_access_token');

        $data = $request->validateWithBag('info', [
            'name' => 'required|string|min:2|max:120',
            'contact_number' => 'required|string|max:20',
            'addresses' => 'nullable|array|max:2',
            'addresses.*.label' => 'nullable|string|max:60',
            'addresses.*.address' => 'nullable|string|max:500',
            'addresses.*.latitude' => 'nullable|numeric|between:-90,90',
            'addresses.*.longitude' => 'nullable|numeric|between:-180,180',
        ], [], ['contact_number' => 'contact number']);

        // Repeater rows may include ones the visitor added then left blank —
        // drop anything with no address text (a label alone isn't a saved
        // address), then reindex.
        $addresses = collect($data['addresses'] ?? [])
            ->map(function ($a) {
                $address = ['label' => trim((string) ($a['label'] ?? '')), 'address' => trim((string) ($a['address'] ?? ''))];
                if (isset($a['latitude'], $a['longitude'])) {
                    $address['latitude'] = (float) $a['latitude'];
                    $address['longitude'] = (float) $a['longitude'];
                }
                return $address;
            })
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
        ]]);
        if ($error) {
            return back()->withErrors(['name' => $error], 'info')->withInput();
        }
        $this->addresses->replace((string) $user['id'], $addresses);

        // Keep the session copy (menu header greeting, phone-number gate) in sync.
        $request->session()->put('supabase_user', array_merge(
            (array) $request->session()->get('supabase_user'),
            ['name' => trim($data['name']), 'contact_number' => $contact],
        ));

        return back()->with('info_success', 'Your information has been updated.');
    }

    /** Add a delivery address to the authenticated account from checkout. */
    public function addAddress(Request $request)
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:60',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
        $address = trim($data['address']);
        if ($address === '') {
            return response()->json(['message' => 'Please enter a delivery address.'], 422);
        }
        return response()->json($this->addresses->add(
            (string) $this->supabaseUser($request)['id'], trim($data['label'] ?? ''), $address,
            isset($data['latitude'], $data['longitude']) ? (float) $data['latitude'] : null,
            isset($data['latitude'], $data['longitude']) ? (float) $data['longitude'] : null,
        ));
    }

    public function deleteAddress(Request $request)
    {
        $data = $request->validate([
            'index' => 'required|integer|min:0',
            'label' => 'nullable|string|max:60',
            'address' => 'required|string|max:500',
        ]);
        return response()->json(['addresses' => $this->addresses->delete(
            (string) $this->supabaseUser($request)['id'], $data['index'], $data['label'] ?? '', $data['address'],
        )]);
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
