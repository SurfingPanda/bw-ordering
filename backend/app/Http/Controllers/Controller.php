<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use Illuminate\Http\Request;

abstract class Controller
{
    /** The Supabase user attached by SupabaseAuth middleware (id/email/name). */
    protected function supabaseUser(Request $request): ?array
    {
        return $request->attributes->get('supabase_user');
    }

    /** True if this email is hard-coded into an env allowlist (the bootstrap layer). */
    private function inEnvList(?string $email, string $key): bool
    {
        if (! $email) {
            return false;
        }
        $list = array_map('strtolower', config("supabase.{$key}", []));

        return in_array(strtolower($email), $list, true);
    }

    /**
     * The single effective role for an email, highest-privilege first. Env
     * allowlists win over the DB so the founding admin can never be locked out;
     * otherwise the editable user_roles table decides. Null means a customer.
     *
     * Pass `$dbRoles` (a lowercased-email => role map) to resolve the DB role
     * from memory instead of querying — used by the Users list to avoid an N+1
     * (one query per user). When omitted, falls back to a single lookup.
     */
    protected function effectiveRole(?string $email, ?array $dbRoles = null): ?string
    {
        if (! $email) {
            return null;
        }
        if ($this->inEnvList($email, 'admin_emails')) {
            return 'admin';
        }
        if ($this->inEnvList($email, 'editor_emails')) {
            return 'editor';
        }
        if ($this->inEnvList($email, 'hr_emails')) {
            return 'hr';
        }

        if ($dbRoles !== null) {
            return $dbRoles[strtolower($email)] ?? null;
        }

        return UserRole::roleFor($email); // cashier/editor/hr/admin or null
    }

    /** True if the founding admin is fixed in the env allowlist (cannot be demoted). */
    protected function isEnvAdmin(?string $email): bool
    {
        return $this->inEnvList($email, 'admin_emails');
    }

    protected function isAdmin(?string $email): bool
    {
        return $this->effectiveRole($email) === 'admin';
    }

    protected function isEditor(?string $email): bool
    {
        $role = $this->effectiveRole($email);

        return $role === 'admin' || $role === 'editor';
    }

    protected function isHr(?string $email): bool
    {
        $role = $this->effectiveRole($email);

        return $role === 'admin' || $role === 'hr';
    }

    protected function isCashier(?string $email): bool
    {
        return $this->effectiveRole($email) === 'cashier';
    }

    /** Anyone allowed to open the orders dashboard (admins + cashiers). */
    protected function isStaff(?string $email): bool
    {
        return $this->isAdmin($email) || $this->isCashier($email);
    }
}
