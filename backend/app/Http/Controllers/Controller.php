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

    /**
     * Same as supabaseUser(), but for public Blade pages that are not gated by
     * the `supabase.session` middleware (e.g. /menu) — reads whatever session
     * the login flow may have already established, without redirecting a
     * guest to /login. Falls back to the request attribute first so it also
     * works transparently on routes that *are* behind the middleware.
     */
    protected function optionalSupabaseUser(Request $request): ?array
    {
        return $request->attributes->get('supabase_user') ?? $request->session()->get('supabase_user');
    }

    /**
     * The Site Editor's live-preview draft for a public page, or null.
     *
     * When an editor loads a previewable page with `?preview=1`, return the
     * unsaved CMS blob they staged via Admin\SiteContentController::preview()
     * (kept in their own session) so the preview iframe reflects edits before
     * they're saved. Returns null for real visitors: it requires both the
     * preview flag and an editor session, so normal page loads are untouched.
     */
    protected function previewDraft(Request $request): ?array
    {
        if (! $this->isEditablePreview($request)) {
            return null;
        }
        $draft = $request->session()->get('content_draft');

        return is_array($draft) ? $draft : null;
    }

    /**
     * True only for a genuine Site Editor preview: `?preview=1` from a
     * request carrying an editor's own session (never for a real visitor who
     * merely has the query param, e.g. a leaked/bookmarked preview link).
     * Public controllers pass this to their view as `editable` to decide
     * whether to render click-to-edit affordances (see
     * partials/_editor-bridge.blade.php) — those affordances intercept every
     * click on the page (to keep the preview "look, don't touch" safe for
     * navigation), so they must never activate for a non-editor.
     */
    protected function isEditablePreview(Request $request): bool
    {
        if (! $request->boolean('preview')) {
            return false;
        }
        $email = $this->optionalSupabaseUser($request)['email'] ?? null;

        return $this->isEditor($email);
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

    protected function isCashier(?string $email): bool
    {
        return $this->effectiveRole($email) === 'cashier';
    }

    /** Anyone allowed to open the orders dashboard (admins + cashiers). */
    protected function isStaff(?string $email): bool
    {
        return $this->isAdmin($email) || $this->isCashier($email);
    }

    /**
     * Admin sections each role can open by default. Admins can grant extra
     * sections per account from Users & Roles (user_roles.permissions);
     * canAccess() combines both. Section keys match UserRole::SECTIONS.
     */
    public const ROLE_SECTIONS = [
        'editor' => ['content', 'products', 'stores', 'vouchers', 'contact-messages'],
        'cashier' => ['orders', 'custom-cakes', 'contact-messages'],
    ];

    /** True if this account may open the given admin section. */
    protected function canAccess(?string $email, string $section): bool
    {
        $role = $this->effectiveRole($email);
        if ($role === 'admin') {
            return true;
        }
        if (in_array($section, self::ROLE_SECTIONS[$role] ?? [], true)) {
            return true;
        }

        return $email !== null && in_array($section, UserRole::grantsFor($email), true);
    }

    /** Which items of the Site Editor sidebar's Admin group to show. */
    protected function editorNavAccess(?string $email): array
    {
        return [
            'users' => $this->isAdmin($email),
            'orders' => $this->canAccess($email, 'orders'),
            'customCakes' => $this->canAccess($email, 'custom-cakes'),
            'contactMessages' => $this->canAccess($email, 'contact-messages'),
        ];
    }
}
