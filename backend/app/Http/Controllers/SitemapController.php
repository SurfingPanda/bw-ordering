<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml — static public routes plus one /menu?category=X URL per
 * distinct product category. Generated (not a static file) since the
 * category list is DB-backed and changes as products are edited; cached
 * like site-content/stores.index elsewhere in the app.
 */
class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $siteUrl = rtrim(config('app.url'), '/');

            $urls = [
                ['loc' => $siteUrl.'/', 'priority' => '1.0'],
                ['loc' => $siteUrl.'/menu', 'priority' => '0.9'],
                ['loc' => $siteUrl.'/stores', 'priority' => '0.8'],
                ['loc' => $siteUrl.'/franchise', 'priority' => '0.5'],
                ['loc' => $siteUrl.'/custom-cake', 'priority' => '0.7'],
                ['loc' => $siteUrl.'/about', 'priority' => '0.4'],
                ['loc' => $siteUrl.'/contact', 'priority' => '0.4'],
            ];

            $categories = app(ProductController::class)->cachedList()
                ->pluck('category')
                ->filter()
                ->unique()
                ->values();

            foreach ($categories as $category) {
                $urls[] = [
                    'loc' => $siteUrl.'/menu?category='.urlencode($category),
                    'priority' => '0.6',
                ];
            }

            $body = collect($urls)->map(fn ($u) => sprintf(
                "  <url>\n    <loc>%s</loc>\n    <priority>%s</priority>\n  </url>\n",
                e($u['loc']),
                $u['priority'],
            ))->implode('');

            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
                "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n".
                $body.
                "</urlset>\n";
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
