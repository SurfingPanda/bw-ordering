<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RecordsAuditLog;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

/**
 * Staff panel for /contact submissions: list the messages and walk each one
 * through new → read → replied. There's no in-app reply — staff respond via
 * the sender's own email/phone shown on each message.
 */
class ContactController extends Controller
{
    use RecordsAuditLog;

    public const STATUSES = ['new', 'read', 'replied'];

    private function authorizeStaff(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'contact-messages'), 403, 'Forbidden.');
    }

    public function index(Request $request)
    {
        $this->authorizeStaff($request);

        $filter = $request->query('status');
        $counts = ContactMessage::selectRaw('status, count(*) as n')
            ->groupBy('status')->pluck('n', 'status')->all();

        $messages = ContactMessage::when(
            in_array($filter, self::STATUSES, true),
            fn ($q) => $q->where('status', $filter)
        )
            ->orderByDesc('created_at')
            ->get();

        return view('admin.contact-messages.index', [
            'messages' => $messages,
            'filter' => in_array($filter, self::STATUSES, true) ? $filter : null,
            'counts' => $counts,
            // Site Editor shell (this page lives in its Admin sidebar group).
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($this->supabaseUser($request)['email'] ?? null),
            'navAccess' => $this->editorNavAccess($this->supabaseUser($request)['email'] ?? null),
        ]);
    }

    public function updateStatus(Request $request, ContactMessage $contactMessage)
    {
        $this->authorizeStaff($request);

        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
        ]);

        $was = $contactMessage->status;
        $contactMessage->update(['status' => $data['status']]);

        $ref = "Message #{$contactMessage->id}".($contactMessage->name ? " from {$contactMessage->name}" : '');
        $this->audit($request, 'contact_message.status_updated', $ref, "Status {$was} → {$data['status']}", ['from' => $was, 'to' => $data['status']]);

        return redirect()->route('admin.contact-messages', array_filter(['status' => $request->input('filter')]))
            ->with('status', "Message #{$contactMessage->id} marked as {$data['status']}.");
    }
}
