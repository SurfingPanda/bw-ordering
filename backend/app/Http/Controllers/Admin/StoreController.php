<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * The Site Editor's "Find a Store" section — Blade port of the old
 * AdminContent.jsx stores editor: one grid of branch cards edited freely and
 * persisted in a single "Save changes" (bulk upsert; cards removed from the
 * grid are deleted, mirroring the JSON API's StoreController::sync()). Shares
 * the same `stores.index` cache key so the public locator stays consistent.
 */
class StoreController extends Controller
{
    private function authorize(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'stores'), 403, 'Forbidden.');
    }

    /** Sidebar data for the Site Editor shell this page renders inside. */
    private function shell(Request $request): array
    {
        return [
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($this->supabaseUser($request)['email'] ?? null),
            'navAccess' => $this->editorNavAccess($this->supabaseUser($request)['email'] ?? null),
        ];
    }

    public function index(Request $request)
    {
        $this->authorize($request);

        return view('admin.stores.index', [
            'stores' => Store::orderBy('region')->orderBy('name')->get(),
        ] + $this->shell($request));
    }

    /**
     * Bulk save the whole grid: rows with an id update, rows without insert,
     * and any store in `originalIds` that no longer appears was removed in
     * the editor → delete it (same as the API sync). Blank new cards (no
     * name) are ignored; blank coordinates are stored as 0 so a typo never
     * discards the branch — fix them from Google Maps as the hint says.
     */
    public function sync(Request $request)
    {
        $this->authorize($request);

        $request->validate([
            'stores' => 'nullable|array',
            'stores.*.id' => 'nullable|integer',
            'stores.*.region' => ['nullable', Rule::in(['Metro Manila', 'Luzon', 'Visayas', 'Mindanao'])],
            'stores.*.fulfillment' => ['nullable', Rule::in(['both', 'delivery', 'pickup'])],
            'stores.*.latitude' => 'nullable|numeric|between:-90,90',
            'stores.*.longitude' => 'nullable|numeric|between:-180,180',
            'originalIds' => 'nullable|array',
            'originalIds.*' => 'integer',
        ]);

        $keptIds = [];
        foreach ((array) $request->input('stores', []) as $s) {
            $s = (array) $s;
            $name = trim((string) ($s['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $attrs = [
                'name' => $name,
                'region' => $s['region'] ?? 'Metro Manila',
                'fulfillment' => $s['fulfillment'] ?? 'both',
                'address' => trim((string) ($s['address'] ?? '')),
                'hours' => trim((string) ($s['hours'] ?? '')) ?: null,
                'phone' => trim((string) ($s['phone'] ?? '')) ?: null,
                'latitude' => (float) ($s['latitude'] ?? 0),
                'longitude' => (float) ($s['longitude'] ?? 0),
            ];

            if (! empty($s['id'])) {
                Store::where('id', $s['id'])->update($attrs);
                $keptIds[] = (int) $s['id'];
            } else {
                $keptIds[] = Store::create($attrs)->id;
            }
        }

        $removed = array_diff(array_map('intval', (array) $request->input('originalIds', [])), $keptIds);
        if (! empty($removed)) {
            Store::whereIn('id', array_values($removed))->delete();
        }

        Cache::forget('stores.index');

        return redirect()->route('admin.stores.index')->with('status', 'Stores saved.');
    }
}
