<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;

/**
 * Public "About Us" page, linked from the footer's Company column. Content
 * lives in the CMS blob's `about` key (Site Editor → Other Pages → About
 * Page), editable inline like franchise.blade.php; this page merges it over
 * the defaults below so it renders fully even before anything is saved.
 */
class AboutController extends Controller
{
    /** About defaults — the original fixed copy this page shipped with, now editable. */
    private const DEFAULT_ABOUT = [
        'hero' => [
            'eyebrow' => 'Our story',
            'title' => 'About bw Superbakeshop',
            'subtitle' => 'Freshly baked. Made with love. Ordered with ease. The same promise we\'ve kept in every branch, every day.',
        ],
        'story' => [
            'eyebrow' => 'How it started',
            'heading' => 'From one neighborhood oven to a name you trust',
            'paragraph1' => 'What started as a small bakeshop with a simple promise — proper ingredients, honest recipes, and warm service — has grown into bw Superbakeshop: a trusted bakery brand with branches nationwide. Through the years, the ovens have gotten bigger and the menu has grown, but what goes into every cake, loaf, and pastry hasn\'t changed.',
            'paragraph2' => 'Today, every branch still bakes the same way we started: fresh, every day, for the communities we\'re part of — whether that\'s a birthday cake picked up on the way home, a loaf grabbed for breakfast, or a custom celebration cake made to order.',
            'image' => '/images/mascot-chef.png',
        ],
        'values' => [
            'eyebrow' => 'What we stand for',
            'heading' => 'The values behind every bake',
            'items' => [
                ['icon' => '🌾', 'title' => 'Quality Ingredients', 'text' => 'We use trusted, quality ingredients in every recipe — a good bake starts long before it goes in the oven.'],
                ['icon' => '❤️', 'title' => 'Made With Love', 'text' => 'Every cake and loaf is prepared with the same care you\'d expect from a home kitchen, just at bakery scale.'],
                ['icon' => '🏘️', 'title' => 'Community First', 'text' => 'We\'re proud to be part of the neighborhoods we serve — from everyday treats to once-in-a-lifetime celebrations.'],
                ['icon' => '📦', 'title' => 'Ordered With Ease', 'text' => 'Visit a branch, order for delivery, or plan a custom cake — we\'ve made it simple to get what you\'re craving.'],
            ],
        ],
        'cta' => [
            'heading' => 'Come taste the difference',
            'subtitle' => 'Explore the full menu or find the bw Superbakeshop nearest you.',
            'backgroundColor' => '#083caa',
        ],
    ];

    public function index(Request $request)
    {
        // Site Editor live preview (?preview=1, editor session) shows the
        // unsaved draft; everyone else sees the saved blob.
        $content = $this->previewDraft($request) ?? (SiteContent::find(1)?->data ?? []);

        // Merge saved about over defaults key-by-key (top level), same
        // shallow-merge pattern FranchiseController uses.
        $ab = array_merge(self::DEFAULT_ABOUT, (array) ($content['about'] ?? []));
        $ab['hero'] = array_merge(self::DEFAULT_ABOUT['hero'], (array) ($ab['hero'] ?? []));
        $ab['story'] = array_merge(self::DEFAULT_ABOUT['story'], (array) ($ab['story'] ?? []));
        $ab['values'] = array_merge(self::DEFAULT_ABOUT['values'], (array) ($ab['values'] ?? []));
        $ab['cta'] = array_merge(self::DEFAULT_ABOUT['cta'], (array) ($ab['cta'] ?? []));

        return view('about', [
            'ab' => $ab,
            'nav' => $this->navConfig($content),
            'footerContent' => array_merge(LandingController::DEFAULT_CONTENT['footer'], (array) ($content['footer'] ?? [])),
            'social' => (array) ($content['social'] ?? LandingController::DEFAULT_CONTENT['social']),
            // Click-to-edit affordances (see partials/_editor-bridge).
            'editable' => $this->isEditablePreview($request),
        ]);
    }
}
