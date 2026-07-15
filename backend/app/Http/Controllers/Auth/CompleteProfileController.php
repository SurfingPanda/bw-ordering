<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;

/**
 * "One last step" phone-number intake (Blade port of CompleteProfile.jsx).
 * Google/Facebook sign-ups arrive without a contact number, so
 * EnsureSupabaseSession bounces them here until one is saved. Behind
 * `supabase.session`.
 */
class CompleteProfileController extends Controller
{
    public function __construct(private SupabaseAuthService $auth) {}

    public function show(Request $request)
    {
        $user = $this->supabaseUser($request);

        // If a number already exists, nothing to do (mirrors the SPA).
        if (! empty($user['contact_number'])) {
            return redirect()->route('menu');
        }

        $content = SiteContent::find(1)?->data ?? [];

        return view('auth.complete-profile', [
            'user' => $user,
            'authPanel' => $content['authPanel'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->supabaseUser($request);
        $token = (string) $request->session()->get('supabase_access_token');

        $data = $request->validate([
            'contact_number' => 'required|string|max:20',
        ], [], ['contact_number' => 'contact number']);

        // Same rules as the old lib/phone.js: strip anything that isn't a
        // digit or common phone symbol, then require at least 7 digits.
        $contact = trim((string) preg_replace('/[^\d+\-\s()]/', '', $data['contact_number']));
        $digits = preg_replace('/\D/', '', $contact);
        if (strlen($digits) < 7) {
            return back()->withErrors(['contact_number' => 'Please enter a valid contact number.'])->withInput();
        }

        // Claim the normalized number first — the UNIQUE constraint on
        // profiles.contact_number is the gatekeeper, exactly like the SPA.
        $normalized = str_starts_with($contact, '+') ? "+{$digits}" : $digits;
        if ($error = $this->auth->claimContactNumber((string) ($user['id'] ?? ''), $normalized)) {
            return back()->withErrors(['contact_number' => $error])->withInput();
        }

        [, $error] = $this->auth->updateUser($token, ['data' => [
            'contact_number' => $contact, // user-entered form, for display
        ]]);
        if ($error) {
            return back()->withErrors(['contact_number' => $error])->withInput();
        }

        // Unblock the middleware gate for the rest of this session.
        $request->session()->put('supabase_user', array_merge(
            (array) $request->session()->get('supabase_user'),
            ['contact_number' => $contact],
        ));

        return redirect()->route('menu');
    }
}
