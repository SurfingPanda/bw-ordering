<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RecordsAuditLog;
use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Models\SiteRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteRatingController extends Controller
{
    use RecordsAuditLog;

    private function authorizeStaff(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'ratings'), 403, 'Forbidden.');
    }

    public function index(Request $request)
    {
        $this->authorizeStaff($request);
        $email = $this->supabaseUser($request)['email'] ?? null;
        $settings = array_merge(SiteRating::PROMPT_DEFAULTS, (array) (SiteContent::find(1)?->data['siteRating'] ?? []));
        $total = SiteRating::count();
        $breakdown = SiteRating::query()->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        return view('admin.ratings.index', [
            'settings' => $settings,
            'total' => $total,
            'average' => $total ? round((float) SiteRating::avg('rating'), 1) : null,
            'breakdown' => $breakdown,
            'ratings' => SiteRating::latest()->paginate(25),
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($email),
            'navAccess' => $this->editorNavAccess($email),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeStaff($request);
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'delaySeconds' => ['required', 'integer', 'min:0', 'max:60'],
        ]);
        $settings = [
            'enabled' => $request->boolean('enabled'),
            'delaySeconds' => (int) $data['delaySeconds'],
        ];
        $current = SiteContent::find(1)?->data ?? [];
        SiteContent::updateOrCreate(['id' => 1], ['data' => array_merge($current, ['siteRating' => $settings])]);
        Cache::forget('site-content');
        $this->audit($request, 'site_rating.settings_updated', 'Rating prompt', $settings['enabled'] ? 'Rating prompt settings updated.' : 'Rating prompt turned off.', $settings);

        return redirect()->route('admin.ratings')->with('status', 'Rating prompt settings saved.');
    }
}
