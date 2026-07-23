<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;

/**
 * Static "About Us" page, linked from the footer's Company column. Like
 * LegalController's pages, the copy itself isn't Site Editor content — just
 * the shared header/footer chrome, same as franchise.blade.php. Pulls the
 * franchise page's trust stats (real figures an editor already entered —
 * see /admin/content → Franchise → Trust Stats) so this page doesn't
 * duplicate/invent its own numbers.
 */
class AboutController extends Controller
{
    public function index(Request $request)
    {
        $content = SiteContent::find(1)?->data ?? [];

        return view('about', [
            'stats' => (array) ($content['franchise']['stats'] ?? []),
            'footerContent' => array_merge(LandingController::DEFAULT_CONTENT['footer'], (array) ($content['footer'] ?? [])),
            'social' => (array) ($content['social'] ?? LandingController::DEFAULT_CONTENT['social']),
        ]);
    }
}
