<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Public "Find a Store" page (Blade port of the SPA's Stores.jsx) — the store
 * list with region/search filtering and the MapLibre 3D locator map
 * (resources/js/stores.js). Distinct from StoreController, which is the JSON
 * API + shares the cached list this page reads.
 */
class StoresController extends Controller
{
    public function index(Request $request)
    {
        // CMS footer/social so this page shares the landing page's footer
        // (same key-by-key default fallback LandingController::index applies).
        $content = (array) app(SiteContentController::class)->cachedData();

        return view('stores', [
            'stores' => app(StoreController::class)->cachedList(),
            'footerContent' => array_merge(LandingController::DEFAULT_CONTENT['footer'], (array) ($content['footer'] ?? [])),
            'social' => (array) ($content['social'] ?? LandingController::DEFAULT_CONTENT['social']),
        ]);
    }
}
