<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;

/**
 * Account registration (Blade port of the SPA's Register.jsx). Creates the
 * user in Supabase Auth with name + contact number in user_metadata, claims
 * the number in the `profiles` table (UNIQUE constraint = one account per
 * number), and sends the user back to /login to sign in explicitly — no
 * session is established here, same as the old flow.
 */
class RegistrationController extends Controller
{
    public function __construct(private SupabaseAuthService $auth) {}

    public function create(Request $request)
    {
        // Signed-in users have no business registering (mirrors the SPA).
        if ($request->session()->has('supabase_user')) {
            return redirect()->route('menu');
        }

        $content = SiteContent::find(1)?->data ?? [];

        return view('auth.register', [
            'authPanel' => $content['authPanel'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email',
            'contact_number' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.min' => 'Please enter your full name.',
            'contact_number.required' => 'Please enter a valid contact number.',
            'password.min' => 'Password should be at least 6 characters.',
            'password.confirmed' => 'Passwords do not match.',
        ], ['contact_number' => 'contact number']);

        // PH mobile numbers only: local 11-digit (09XXXXXXXXX) or
        // international (+639XXXXXXXXX) form.
        $phone = SupabaseAuthService::normalizePhContactNumber($data['contact_number']);
        if (! $phone) {
            return back()->withErrors(['contact_number' => 'Please enter a valid 11-digit PH mobile number, e.g. 09123456789.'])->withInput();
        }
        ['contact' => $contact, 'normalized' => $normalized] = $phone;

        // Reject duplicate numbers before creating the account (the
        // contact_number_taken RPC, fail-closed like the old register form).
        $taken = $this->auth->contactNumberTaken($normalized);
        if ($taken === null) {
            return back()->withErrors(['form' => 'Could not verify your contact number right now. Please try again.'])->withInput();
        }
        if ($taken) {
            return back()->withErrors(['contact_number' => 'This contact number is already in use by another account.'])->withInput();
        }

        [$body, $error] = $this->auth->signUp($data['email'], $data['password'], [
            'full_name' => trim($data['name']),
            'contact_number' => $contact, // user-entered form, for display
        ]);
        if ($error) {
            return back()->withErrors(['form' => $error])->withInput();
        }

        // Claim the number in profiles so the UNIQUE constraint closes any
        // race between two simultaneous sign-ups (service key — works whether
        // or not email confirmation withheld a session).
        $userId = (string) ($body['user']['id'] ?? $body['id'] ?? '');
        if ($userId !== '' && ($claimError = $this->auth->claimContactNumber($userId, $normalized))) {
            return back()->withErrors(['contact_number' => $claimError])->withInput();
        }

        // Sign in explicitly afterwards, same as the SPA (which dropped any
        // auto-created session before redirecting to /login).
        return redirect()->route('login')->with('status', 'Account created! Please sign in.');
    }
}
