<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

// Admin user management: list every Supabase account, change a user's role, and
// rename a Supabase login email. Listing/renaming use the Supabase Admin API,
// which needs the service_role key (config('supabase.service_role_key')).
class UserController extends Controller
{
    /**
     * Role flags for the currently signed-in user. The frontend calls this to
     * refine the env-based defaults it computes synchronously.
     */
    public function me(Request $request)
    {
        $email = $this->supabaseUser($request)['email'] ?? null;

        return response()->json([
            'email' => $email,
            'role' => $this->effectiveRole($email) ?? 'customer',
            'is_admin' => $this->isAdmin($email),
            'is_editor' => $this->isEditor($email),
            'is_hr' => $this->isHr($email),
            'is_cashier' => $this->isCashier($email),
        ]);
    }

    /**
     * List every Supabase account with its effective role. Admin only. Pulls
     * from the Supabase Admin API (paginated) and merges roles from env +
     * the user_roles table.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $url = rtrim((string) config('supabase.url'), '/');
        $key = (string) config('supabase.service_role_key');
        if (! $url || ! $key) {
            return response()->json([
                'message' => 'User listing is unavailable: set SUPABASE_SERVICE_ROLE_KEY in the backend .env.',
            ], 503);
        }

        // DB-assigned roles, keyed by lowercased email, fetched once.
        $dbRoles = UserRole::pluck('role', 'email');

        $users = [];
        $page = 1;
        do {
            try {
                $resp = Http::withHeaders([
                    'apikey' => $key,
                    'Authorization' => "Bearer {$key}",
                ])->timeout(10)->get("{$url}/auth/v1/admin/users", [
                    'page' => $page,
                    'per_page' => 200,
                ]);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Could not reach Supabase: '.$e->getMessage()], 502);
            }

            if ($resp->failed()) {
                return response()->json(['message' => 'Supabase rejected the request (check the service_role key).'], 502);
            }

            $batch = $resp->json('users') ?? [];
            foreach ($batch as $u) {
                $email = $u['email'] ?? null;
                $meta = $u['user_metadata'] ?? [];
                $lower = $email ? strtolower($email) : null;
                // Env allowlist beats the DB; effectiveRole() handles that order.
                $role = $this->effectiveRole($email)
                    ?? ($lower ? ($dbRoles[$lower] ?? null) : null)
                    ?? 'customer';

                $users[] = [
                    'id' => $u['id'] ?? null,
                    'email' => $email,
                    'name' => $meta['full_name'] ?? $meta['name'] ?? null,
                    'role' => $role,
                    'is_env_admin' => $this->isEnvAdmin($email),
                    'created_at' => $u['created_at'] ?? null,
                    'last_sign_in_at' => $u['last_sign_in_at'] ?? null,
                ];
            }

            $page++;
            // Supabase returns up to per_page rows; stop when a short page arrives.
        } while (count($batch) === 200 && $page <= 50);

        return response()->json($users);
    }

    /**
     * Assign a role to an email (admin only). 'customer' removes any stored
     * role. Env-admins can't be changed, and you can't demote yourself.
     */
    public function updateRole(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,editor,cashier,hr,customer',
        ]);

        $email = strtolower($data['email']);
        $me = strtolower($this->supabaseUser($request)['email'] ?? '');

        if ($this->isEnvAdmin($email)) {
            throw ValidationException::withMessages([
                'email' => 'This account is a founding admin (set in the server .env) and cannot be changed here.',
            ]);
        }
        if ($email === $me && $data['role'] !== 'admin') {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own admin access.',
            ]);
        }

        if ($data['role'] === 'customer') {
            UserRole::where('email', $email)->delete();
        } else {
            UserRole::updateOrCreate(['email' => $email], ['role' => $data['role']]);
        }

        return response()->json(['email' => $email, 'role' => $data['role']]);
    }

    /**
     * Rename a Supabase account's login email (admin only) via the Admin API,
     * then carry any stored role over to the new email. Used to turn the spare
     * admin@ account into cashier@.
     */
    public function renameEmail(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'id' => 'required|string',
            'new_email' => 'required|email',
            'old_email' => 'nullable|email',
        ]);

        $url = rtrim((string) config('supabase.url'), '/');
        $key = (string) config('supabase.service_role_key');
        if (! $url || ! $key) {
            return response()->json([
                'message' => 'Renaming is unavailable: set SUPABASE_SERVICE_ROLE_KEY in the backend .env.',
            ], 503);
        }

        $newEmail = strtolower($data['new_email']);

        try {
            $resp = Http::withHeaders([
                'apikey' => $key,
                'Authorization' => "Bearer {$key}",
            ])->timeout(10)->put("{$url}/auth/v1/admin/users/{$data['id']}", [
                'email' => $newEmail,
                'email_confirm' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not reach Supabase: '.$e->getMessage()], 502);
        }

        if ($resp->failed()) {
            $msg = $resp->json('msg') ?? $resp->json('error_description') ?? 'Supabase rejected the rename.';

            return response()->json(['message' => $msg], 422);
        }

        // Move any stored role from the old email to the new one.
        if (! empty($data['old_email'])) {
            $old = strtolower($data['old_email']);
            if ($old !== $newEmail) {
                $existing = UserRole::where('email', $old)->first();
                if ($existing) {
                    UserRole::where('email', $newEmail)->delete();
                    $existing->update(['email' => $newEmail]);
                }
            }
        }

        return response()->json(['id' => $data['id'], 'email' => $newEmail]);
    }

    /** Throw a 403 unless the caller is an admin. */
    private function authorizeAdmin(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        if (! $this->isAdmin($email)) {
            abort(403, 'Admins only.');
        }
    }
}
