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
        // Site Editor live preview (?preview=1, editor session) shows the
        // unsaved draft, same as LandingController — otherwise the "Find a
        // Store Page" tab's edits would never appear in the preview iframe.
        $content = (array) ($this->previewDraft($request) ?? app(SiteContentController::class)->cachedData());

        return view('stores', [
            'stores' => app(StoreController::class)->cachedList(),
            'nav' => $this->navConfig($content),
            'footerContent' => array_merge(LandingController::DEFAULT_CONTENT['footer'], (array) ($content['footer'] ?? [])),
            'social' => (array) ($content['social'] ?? LandingController::DEFAULT_CONTENT['social']),
            'hero' => array_merge(LandingController::DEFAULT_CONTENT['storesPage'], (array) ($content['storesPage'] ?? [])),
            'editable' => $this->isEditablePreview($request),
        ]);
    }
}
