<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Mixed into the Admin\* controllers to drop a row into `audit_logs` after a
 * staff mutation. The actor is read from the Supabase session the request
 * already carries (Controller::supabaseUser / effectiveRole), so a call site
 * is a single line right before the redirect. Logging never breaks the real
 * action — a failure here is reported and swallowed.
 */
trait RecordsAuditLog
{
    protected function audit(
        Request $request,
        string $action,
        ?string $target = null,
        ?string $summary = null,
        array $meta = [],
    ): void {
        try {
            $user = $this->supabaseUser($request) ?? [];
            $email = $user['email'] ?? null;

            AuditLog::create([
                'actor_email' => $email,
                'actor_name' => $user['name'] ?? null,
                'actor_role' => $email ? $this->effectiveRole($email) : null,
                'action' => $action,
                'target' => $target,
                'summary' => $summary,
                'meta' => $meta ?: null,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed: '.$e->getMessage(), ['action' => $action]);
        }
    }
}
