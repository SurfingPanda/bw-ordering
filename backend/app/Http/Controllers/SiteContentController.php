<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteContentController extends Controller
{
    /**
     * Public: the single landing/franchise CMS blob (or {} if unset).
     *
     * Cached server-side (busted on update) and publicly cacheable for the
     * browser / Hostinger CDN. Editors (Bearer token) skip the cache header so
     * their edits show immediately.
     */
    public function show(Request $request)
    {
        $data = $this->cachedData();

        $res = response()->json($data);

        if (! $request->bearerToken()) {
            $res->header('Cache-Control', 'public, max-age=120, stale-while-revalidate=600');
        }

        return $res;
    }

    /**
     * The same cached CMS blob used by show() above — shared with the Blade
     * menu/checkout pages (menu copy, the QR Ph merchant payload) so they read
     * from the exact same cache key and are busted by update() below the same
     * way. Returns {} (empty stdClass) when nothing has been saved yet, same
     * as show() always has — callers should `(array)` cast before indexing.
     */
    public function cachedData()
    {
        return Cache::remember('site-content', now()->addMinutes(10), function () {
            $row = SiteContent::find(1);

            return $row?->data ?? (object) [];
        });
    }

    /** Admin/editor: persist the CMS blob (single row, id = 1). */
    public function update(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isEditor($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->all();

        SiteContent::updateOrCreate(['id' => 1], ['data' => $data]);

        Cache::forget('site-content');

        return response()->json(['ok' => true]);
    }
}
