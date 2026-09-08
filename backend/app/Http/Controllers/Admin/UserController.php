<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RecordsAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController as ApiUserController;
use App\Models\UserRole;
use Illuminate\Http\Request;

/**
 * Users / role manager (Blade port of the SPA's AdminUsers.jsx — replaces the
 * ComingSoonController::users() placeholder). Lists every Supabase account
 * via the Admin API and assigns roles through the user_roles table; env
 * allowlist accounts stay read-only ("founding admin"). Admin only.
 */
class UserController extends Controller
{
    use RecordsAuditLog;

    private function authorizeAdmin(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->isAdmin($email), 403, 'Admins only.');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $result = app(ApiUserController::class)->allUsers();

        return view('admin.users.index', [
            'users' => $result['users'] ?? [],
            'error' => $result['error'] ?? null,
            'myEmail' => strtolower($this->supabaseUser($request)['email'] ?? ''),
            // Per-user extra section grants (beyond role defaults).
            'grants' => UserRole::all()->mapWithKeys(
                fn ($r) => [strtolower($r->email) => (array) ($r->permissions ?? [])]
            )->all(),
            // Site Editor shell (this page lives in its Admin sidebar group).
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => true, // authorizeAdmin() above guarantees it
            'navAccess' => ['users' => true, 'orders' => true, 'customCakes' => true],
        ]);
    }

    /**
     * Save a user's role and extra section grants in one go (the row's Edit
     * modal). Same rules as the SPA-era endpoints: founding admins are
     * immutable, you can't demote yourself, 'customer' clears the stored row
     * (role and grants), and only true add-ons beyond the role's defaults
     * are persisted.
     */
    public function update(Request $request)
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,editor,cashier,customer',
            'access' => 'nullable|array',
            'access.*' => 'in:'.implode(',', UserRole::SECTIONS),
        ]);

        $email = strtolower($data['email']);
        $me = strtolower($this->supabaseUser($request)['email'] ?? '');

        if ($this->isEnvAdmin($email)) {
            return back()->withErrors(['role' => 'This account is a founding admin (set in the server .env) and cannot be changed here.']);
        }
        if ($email === $me && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot remove your own admin access.']);
        }

        $wasRole = $this->effectiveRole($email) ?? 'customer';

        if ($data['role'] === 'customer') {
            UserRole::where('email', $email)->delete();

            $this->audit($request, 'user.role_updated', $email, "Role {$wasRole} → customer", ['from' => $wasRole, 'to' => 'customer']);

            return back()->with('status', "{$email} is now a customer (no staff role).");
        }

        $grants = $data['role'] === 'admin'
            ? [] // admins open everything already
            : array_values(array_diff($data['access'] ?? [], self::ROLE_SECTIONS[$data['role']] ?? []));
        UserRole::updateOrCreate(['email' => $email], ['role' => $data['role'], 'permissions' => $grants]);

        $this->audit($request, 'user.role_updated', $email, "Role {$wasRole} → {$data['role']}", array_filter([
            'from' => $wasRole,
            'to' => $data['role'],
            'grants' => $grants ?: null,
        ]));

        return back()->with('status', "Updated {$email} — {$data['role']}".($grants ? ', +'.count($grants).' extra section'.(count($grants) === 1 ? '' : 's').'.' : '.'));
    }
}
