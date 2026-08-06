<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;

/**
 * Static Privacy Policy / Terms of Service pages, linked from the footer.
 * Unlike the CMS-driven pages, the legal copy itself isn't Site Editor
 * content — just the shared header/footer chrome, same as franchise.blade.php.
 */
class LegalController extends Controller
{
    public function privacy(Request $request)
    {
        return $this->page($request, 'privacy');
    }

    public function terms(Request $request)
    {
        return $this->page($request, 'terms');
    }

    public function dataDeletion(Request $request)
    {
        return $this->page($request, 'dataDeletion');
    }

    private function page(Request $request, string $key)
    {
        $shared = $this->sharedData($request);

        return view('legal.page', $shared + [
            'page' => array_merge(
                LandingController::DEFAULT_CONTENT['legal'][$key],
                (array) (($shared['content']['legal'] ?? [])[$key] ?? [])
            ),
        ]);
    }

    private function sharedData(Request $request): array
    {
        $content = SiteContent::find(1)?->data ?? [];

        return [
            'content' => $content,
            'footerContent' => array_merge(LandingController::DEFAULT_CONTENT['footer'], (array) ($content['footer'] ?? [])),
            'social' => (array) ($content['social'] ?? LandingController::DEFAULT_CONTENT['social']),
        ];
    }
}
