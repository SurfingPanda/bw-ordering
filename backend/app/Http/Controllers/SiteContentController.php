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
        $data = Cache::remember('site-content', now()->addMinutes(10), function () {
            $row = SiteContent::find(1);

            return $row?->data ?? (object) [];
        });

        $res = response()->json($data);

        if (! $request->bearerToken()) {
            $res->header('Cache-Control', 'public, max-age=120, stale-while-revalidate=600');
        }

        return $res;
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
