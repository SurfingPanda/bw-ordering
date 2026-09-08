<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * The Site Editor shell's "Audit Log" page — a read-only, paginated history
 * of staff actions in the editor/admin area (content saves, product/store/
 * voucher saves, role changes, order & queue status changes). Open to
 * editors and admins (the two roles that make changes worth auditing).
 * Rows are written by the RecordsAuditLog trait on the Admin\* controllers.
 */
class AuditLogController extends Controller
{
    private function authorize(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->isEditor($email), 403, 'Editors only.');
    }

    public function index(Request $request)
    {
        $this->authorize($request);
        $email = $this->supabaseUser($request)['email'] ?? null;

        $filters = [
            'action' => (string) $request->query('action', ''),
            'actor' => (string) $request->query('actor', ''),
            'q' => trim((string) $request->query('q', '')),
        ];

        $logs = AuditLog::query()
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['actor'] !== '', fn ($q) => $q->where('actor_email', $filters['actor']))
            ->when($filters['q'] !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('summary', 'like', "%{$filters['q']}%")
                    ->orWhere('target', 'like', "%{$filters['q']}%")
                    ->orWhere('actor_name', 'like', "%{$filters['q']}%")
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit-log.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actionOptions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'actorOptions' => AuditLog::query()->whereNotNull('actor_email')->select('actor_email')->distinct()->orderBy('actor_email')->pluck('actor_email'),
            // Site Editor shell (this page lives in its own nav group).
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($email),
            'navAccess' => $this->editorNavAccess($email),
        ]);
    }
}
