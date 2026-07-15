<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;

/**
 * Public "Partner with us" franchise page (Blade port of the SPA's
 * Franchise.jsx). Content is the CMS blob's `franchise` key, editable from the
 * Site Editor's Franchise section; this page merges it over the defaults below
 * so it renders fully even before anything is saved.
 */
class FranchiseController extends Controller
{
    /** Franchise defaults, ported verbatim from the SPA's content.js DEFAULT_CONTENT.franchise. */
    private const DEFAULT_FRANCHISE = [
        'hero' => [
            'eyebrow' => 'Own a bakeshop',
            'title' => 'Partner with us',
            'subtitle' => 'Partner with a trusted, decades-old brand and turn your community’s love for fresh bread and cakes into a thriving business.',
        ],
        'email' => 'franchise@bwsuperbakeshop.com',
        'perks' => [
            ['icon' => '🧡', 'title' => 'A Trusted Name', 'text' => 'Partner with an established bakeshop brand and a loyal, ever-growing customer base.'],
            ['icon' => '👨‍🍳', 'title' => 'Training & Support', 'text' => 'Hands-on training, proven recipes, and day-to-day operations guidance from our team.'],
            ['icon' => '🚚', 'title' => 'Supply Chain', 'text' => 'A reliable ingredient and equipment supply chain so you can focus on serving customers.'],
            ['icon' => '📣', 'title' => 'Marketing Power', 'text' => 'Ready-made campaigns, branded materials, and nationwide promotions to launch you fast.'],
            ['icon' => '📍', 'title' => 'Site Selection', 'text' => 'We help you scout, evaluate, and secure the right location for your branch.'],
            ['icon' => '📈', 'title' => 'Proven Model', 'text' => 'A time-tested business system designed for healthy margins and repeat customers.'],
        ],
        'steps' => [
            ['n' => '01', 'title' => 'Inquire', 'text' => 'Send us your details and preferred location. We’ll share the franchise kit.'],
            ['n' => '02', 'title' => 'Discovery call', 'text' => 'Meet our franchising team to discuss investment, requirements, and timelines.'],
            ['n' => '03', 'title' => 'Sign & set up', 'text' => 'Finalize the agreement, secure your site, and begin store build-out and training.'],
            ['n' => '04', 'title' => 'Grand opening', 'text' => 'Launch your branch with full marketing and operations support behind you.'],
        ],
        'packages' => [
            ['name' => 'Kiosk', 'price' => '₱1.2M – 1.8M', 'blurb' => 'A compact counter for malls and transit hubs — fast to open, high foot traffic.', 'features' => ['25–40 sqm space', 'Core bestseller menu', 'Equipment & signage', '2-week crew training'], 'featured' => false],
            ['name' => 'Inline Store', 'price' => '₱2.5M – 3.5M', 'blurb' => 'The flagship bakeshop experience with full product range and seating.', 'features' => ['60–100 sqm space', 'Full menu + custom cakes', 'Bake-on-site setup', 'Dedicated launch support'], 'featured' => true],
            ['name' => 'Master Franchise', 'price' => 'Let’s talk', 'blurb' => 'Develop multiple branches across an entire region or province.', 'features' => ['Territory rights', 'Multi-store rollout plan', 'Priority supply allocation', 'Executive business reviews'], 'featured' => false],
        ],
    ];

    public function index(Request $request)
    {
        // Site Editor live preview (?preview=1, editor session) shows the
        // unsaved draft; everyone else sees the saved blob.
        $content = $this->previewDraft($request) ?? (SiteContent::find(1)?->data ?? []);

        // Merge saved franchise over defaults key-by-key (top level), mirroring
        // the JSX's `resolved.franchise || DEFAULT` + per-field fallbacks.
        $fr = array_merge(self::DEFAULT_FRANCHISE, (array) ($content['franchise'] ?? []));
        $fr['hero'] = array_merge(self::DEFAULT_FRANCHISE['hero'], (array) ($fr['hero'] ?? []));

        return view('franchise', [
            'fr' => $fr,
            // CMS footer/social so this page shares the landing page's footer
            // (same key-by-key default fallback LandingController::index applies).
            'footerContent' => array_merge(LandingController::DEFAULT_CONTENT['footer'], (array) ($content['footer'] ?? [])),
            'social' => (array) ($content['social'] ?? LandingController::DEFAULT_CONTENT['social']),
        ]);
    }
}
