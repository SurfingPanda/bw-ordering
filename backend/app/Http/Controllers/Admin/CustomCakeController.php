<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomCakeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Staff panel for the /custom-cake inquiries: list the requests, view the
 * details + private reference image, and walk each one through
 * new → quoted → closed. The status is reflected back to the customer on
 * /my-orders ("Request received" / "Quote sent" / "Closed").
 */
class CustomCakeController extends Controller
{
    public const STATUSES = ['new', 'quoted', 'closed'];

    private function authorizeStaff(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'custom-cakes'), 403, 'Forbidden.');
    }

    public function index(Request $request)
    {
        $this->authorizeStaff($request);

        $filter = $request->query('status');
        $counts = CustomCakeRequest::selectRaw('status, count(*) as n')
            ->groupBy('status')->pluck('n', 'status')->all();

        $requests = CustomCakeRequest::when(
            in_array($filter, self::STATUSES, true),
            fn ($q) => $q->where('status', $filter)
        )
            ->orderByDesc('created_at')
            ->get();

        return view('admin.custom-cakes.index', [
            'requests' => $requests,
            'filter' => in_array($filter, self::STATUSES, true) ? $filter : null,
            'counts' => $counts,
            // Site Editor shell (this page lives in its Admin sidebar group).
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($this->supabaseUser($request)['email'] ?? null),
            'navAccess' => $this->editorNavAccess($this->supabaseUser($request)['email'] ?? null),
        ]);
    }

    public function updateStatus(Request $request, CustomCakeRequest $customCakeRequest)
    {
        $this->authorizeStaff($request);

        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
        ]);

        $customCakeRequest->update(['status' => $data['status']]);

        return redirect()->route('admin.custom-cakes', array_filter(['status' => $request->input('filter')]))
            ->with('status', "Request #{$customCakeRequest->id} marked as {$data['status']}.");
    }

    /** The uploaded design peg lives on the private disk — stream it to staff only. */
    public function reference(Request $request, CustomCakeRequest $customCakeRequest)
    {
        $this->authorizeStaff($request);

        $path = (string) $customCakeRequest->reference_path;
        abort_unless($path !== '' && Storage::exists($path), 404);

        return Storage::response($path);
    }
}
