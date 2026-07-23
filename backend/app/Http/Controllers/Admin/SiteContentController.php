<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SiteContentController as PublicSiteContentController;
use App\Models\Product;
use App\Models\SiteContent;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Full Site Content CMS editor — replaces the old React AdminContent.jsx
 * mega-editor (frontend/src/pages/AdminContent.jsx, recovered from git
 * history) and the ComingSoonController::content() placeholder it used to
 * fall back to.
 *
 * Edits the single site_content row (id = 1, `data` JSON blob) that
 * LandingController, MenuController, Auth\SessionController (login), and
 * CheckoutController already read via the same cached `site-content` key
 * (App\Http\Controllers\SiteContentController::cachedData()).
 *
 * Products, Vouchers, and Stores live in their own tables with their own
 * admin CRUD pages (Admin\ProductController / Admin\VoucherController /
 * Admin\StoreController) — this controller only ever read-modify-writes the
 * CMS blob's *other* top-level keys, always merging onto the currently-saved
 * blob so keys this form doesn't manage are never clobbered by a save.
 */
class SiteContentController extends Controller
{
    /** Mirrors frontend/src/lib/content.js's LANDING_BUTTONS. */
    public const LANDING_BUTTONS = [
        ['key' => 'navOrder', 'label' => 'Order Now', 'group' => 'Navigation bar'],
        ['key' => 'navSignIn', 'label' => 'Sign In', 'group' => 'Navigation bar'],
        ['key' => 'bestSellersMenu', 'label' => 'See full menu', 'group' => 'Best Sellers'],
        ['key' => 'promoOrder', 'label' => 'Order a custom cake', 'group' => 'Promo banner'],
        ['key' => 'storeLocatorFind', 'label' => 'Find a store (+ search box)', 'group' => 'Store locator'],
        ['key' => 'newsletterSubscribe', 'label' => 'Subscribe form', 'group' => 'Newsletter'],
        ['key' => 'menuCheckout', 'label' => 'Proceed to checkout', 'group' => 'Menu page'],
    ];

    /**
     * Top-level content-blob keys this form's sections manage. Everything
     * else already stored in the blob is preserved untouched on save — e.g.
     * `menuCategories`/`menuCategoryImages` (owned by the Menu
     * Categories mini-form below), and the SPA-era `bestSellers`/
     * `categories`/`whatsNewProducts` card lists (dead keys now: the Blade
     * landing derives all three from the products table, so this form
     * neither edits nor overwrites them).
     */
    private const MANAGED_KEYS = [
        'maintenance', 'announcement', 'announcementVisible', 'announcementTypography', 'banners', 'bannersVisible',
        'whatsNew', 'customCake', 'customCakeForm', 'newsletter', 'franchise', 'storeLocator', 'storesPage',
        'footer', 'menuPromo', 'payment', 'authPanel', 'social', 'buttons',
    ];

    /**
     * Form pre-fill defaults for every managed section: the landing page's
     * own defaults plus the sections no ported page holds defaults for yet
     * (menuPromo, payment, authPanel, franchise — values carried over from
     * the SPA's content.js DEFAULT_CONTENT).
     */
    private static function defaults(): array
    {
        return LandingController::DEFAULT_CONTENT + [
            'customCakeForm' => \App\Http\Controllers\CustomCakeController::DEFAULT_FORM,
            'menuPromo' => [
                'enabled' => true,
                'slides' => [
                    [
                        'badge' => '✨ Just Launched',
                        'title' => 'Ube Chiffon Cake',
                        'description' => 'Light-as-air purple yam chiffon with sweet ube halaya swirl.',
                        'image' => '',
                        'price' => '₱720.00',
                        'buttonLabel' => 'Add to cart',
                        'buttonLink' => '/menu?add=Ube Chiffon Cake',
                        'products' => [],
                    ],
                ],
            ],
            'payment' => ['qrPayload' => '', 'qrImage' => ''],
            'authPanel' => [
                'logo' => '/images/logo (1).png',
                'tagline' => 'Freshly baked. Made with love.',
                'script' => 'Ordered with ease.',
                'image' => '/images/cake.png',
                'showGoogle' => true,
                'showFacebook' => true,
            ],
            'franchise' => [
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
                'packagesEnabled' => true,
                'packages' => [
                    ['name' => 'Kiosk', 'price' => '₱1.2M – 1.8M', 'blurb' => 'A compact counter for malls and transit hubs — fast to open, high foot traffic.', 'features' => ['25–40 sqm space', 'Core bestseller menu', 'Equipment & signage', '2-week crew training'], 'featured' => false],
                    ['name' => 'Inline Store', 'price' => '₱2.5M – 3.5M', 'blurb' => 'The flagship bakeshop experience with full product range and seating.', 'features' => ['60–100 sqm space', 'Full menu + custom cakes', 'Bake-on-site setup', 'Dedicated launch support'], 'featured' => true],
                    ['name' => 'Master Franchise', 'price' => 'Let’s talk', 'blurb' => 'Develop multiple branches across an entire region or province.', 'features' => ['Territory rights', 'Multi-store rollout plan', 'Priority supply allocation', 'Executive business reviews'], 'featured' => false],
                ],
            ],
        ];
    }

    private function authorizeEditor(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'content'), 403, 'Forbidden.');
    }

    /**
     * Badge counts for the shared Site Editor sidebar
     * (admin/content/_editor-nav) — also used by the Stores/Vouchers CRUD
     * pages, which render inside the same shell.
     */
    public static function navCounts(): array
    {
        $content = (array) app(PublicSiteContentController::class)->cachedData();

        return [
            'banners' => count((array) ($content['banners'] ?? [])),
            'products' => Product::whereNull('archived_at')->count(),
            'vouchers' => Voucher::count(),
            'stores' => Store::count(),
        ];
    }

    public function edit(Request $request)
    {
        $this->authorizeEditor($request);

        // Saved keys win; defaults fill the gaps — same top-level shallow
        // merge the old AdminContent.jsx did with content.js's
        // DEFAULT_CONTENT, so a fresh site's form starts from what the public
        // pages already render (and its first save persists that) instead of
        // starting blank and blanking the site.
        $content = array_merge(self::defaults(), (array) app(PublicSiteContentController::class)->cachedData());
        $products = Product::whereNull('archived_at')->orderBy('category')->orderBy('name')->get();

        $counts = [];
        foreach ($products as $p) {
            if ($p->category) {
                $counts[$p->category] = ($counts[$p->category] ?? 0) + 1;
            }
        }
        $declared = $content['menuCategories'] ?? [];
        $allCategories = collect(array_keys($counts))->merge($declared)->unique()->sort()->values();

        $buttonGroups = [];
        foreach (self::LANDING_BUTTONS as $b) {
            $buttonGroups[$b['group']][] = $b;
        }

        return view('admin.content.index', [
            'content' => $content,
            // Menu Promo slides can link real products (a bundle) — the picker
            // in _slide-row lists the live catalogue.
            'bundleProducts' => $products,
            'categories' => $allCategories,
            'categoryCounts' => $counts,
            'categoryImages' => $content['menuCategoryImages'] ?? [],
            'buttonGroups' => $buttonGroups,
            // Banners badge reflects what this form shows (defaults-merged),
            // not just the raw saved blob.
            'navCounts' => array_merge(self::navCounts(), ['banners' => count((array) ($content['banners'] ?? []))]),
            // The Site Editor shell shows an "Orders" shortcut for admins only,
            // mirroring the old AdminContent.jsx sidebar.
            'isAdminUser' => $this->isAdmin($this->supabaseUser($request)['email'] ?? null),
            'navAccess' => $this->editorNavAccess($this->supabaseUser($request)['email'] ?? null),
        ]);
    }

    /** Read-modify-write the whole CMS blob: merge this form's managed keys
     * onto whatever is currently saved, so unmanaged keys survive untouched. */
    public function update(Request $request)
    {
        $this->authorizeEditor($request);

        // Most of this form is free-text CMS copy with no fixed shape worth
        // rejecting — but these few fields fail *silently* without this: a
        // typo'd bundle price used to be coerced with (float) (e.g. "1o0"
        // silently becomes ₱1, not ₱100), and an invalid frosting hex or
        // franchise email used to just fall back to a default with no
        // indication anything was wrong. See collectManagedFields() for the
        // coercions this makes unreachable for these fields (kept anyway, as
        // defense-in-depth for non-form submitters of this endpoint).
        $request->validate([
            'franchise.email' => ['nullable', 'email'],
            'menuPromo.slides.*.bundlePrice' => ['nullable', 'numeric', 'min:0'],
            'customCakeForm.colors.*.hex' => ['nullable', 'regex:/^#[0-9a-f]{3,8}$/i'],
        ], [
            'franchise.email.email' => 'Franchise contact email must be a valid email address.',
            'menuPromo.slides.*.bundlePrice.numeric' => 'Bundle price must be a number.',
            'menuPromo.slides.*.bundlePrice.min' => "Bundle price can't be negative.",
            'customCakeForm.colors.*.hex.regex' => "One of the frosting colors isn't a valid color.",
        ]);

        $current = SiteContent::find(1)?->data ?? [];
        $updates = $this->collectManagedFields($request);

        SiteContent::updateOrCreate(['id' => 1], ['data' => array_merge($current, $updates)]);
        Cache::forget('site-content');

        // `section` is a hidden input the form's tab JS keeps in sync, so the
        // editor lands back on the tab they saved from.
        return redirect()->route('admin.content', array_filter(['section' => $request->input('section')]))
            ->with('status', 'Content saved.');
    }

    /**
     * Live-preview draft: normalize the current form exactly as update() would,
     * merge onto the saved blob, and stash it in the editor's session (not the
     * DB). The public pages read it when loaded with ?preview=1 (see
     * Controller::previewDraft), so the Site Editor's iframe reflects unsaved
     * edits. Returns 204 — the editor JS just reloads the iframe afterward.
     */
    public function preview(Request $request)
    {
        $this->authorizeEditor($request);

        $current = SiteContent::find(1)?->data ?? [];
        $updates = $this->collectManagedFields($request);
        $request->session()->put('content_draft', array_merge($current, $updates));

        return response()->noContent();
    }

    private function collectManagedFields(Request $request): array
    {
        $updates = [];
        foreach (self::MANAGED_KEYS as $key) {
            $updates[$key] = $request->input($key);
        }

        $updates['announcement'] = (string) ($updates['announcement'] ?? '');
        $updates['announcementVisible'] = $request->boolean('announcementVisible');
        $updates['announcementTypography'] = SiteContent::normalizeTypography($updates['announcementTypography'] ?? null);

        // List sections — reindex (repeater rows may submit non-sequential
        // keys after add/remove) and turn per-item "one per line" textareas
        // back into arrays.
        $updates['banners'] = array_values((array) ($updates['banners'] ?? []));
        $updates['bannersVisible'] = $request->boolean('bannersVisible');

        // Per-section "Show on page" toggles ("0"/"1" hidden+checkbox pairs).
        $updates['whatsNew'] = (array) ($updates['whatsNew'] ?? []);
        $updates['whatsNew']['visible'] = $request->boolean('whatsNew.visible');
        $updates['whatsNew']['typography'] = SiteContent::normalizeTypography($updates['whatsNew']['typography'] ?? null);
        $updates['customCake'] = (array) ($updates['customCake'] ?? []);
        $updates['customCake']['visible'] = $request->boolean('customCake.visible');
        $updates['customCake']['typography'] = SiteContent::normalizeTypography($updates['customCake']['typography'] ?? null);
        $updates['newsletter'] = (array) ($updates['newsletter'] ?? []);
        $updates['newsletter']['visible'] = $request->boolean('newsletter.visible');
        $updates['newsletter']['typography'] = SiteContent::normalizeTypography($updates['newsletter']['typography'] ?? null);
        $updates['storeLocator'] = (array) ($updates['storeLocator'] ?? []);
        $updates['storeLocator']['visible'] = $request->boolean('storeLocator.visible');
        $updates['storeLocator']['typography'] = SiteContent::normalizeTypography($updates['storeLocator']['typography'] ?? null);
        $updates['storesPage'] = (array) ($updates['storesPage'] ?? []);
        $updates['storesPage']['typography'] = SiteContent::normalizeTypography($updates['storesPage']['typography'] ?? null);

        // Custom Cake Page wizard: repeater rows → clean arrays (blank rows
        // drop out; a bad hex falls back to a neutral cream).
        $ccf = (array) ($updates['customCakeForm'] ?? []);
        foreach (['occasions', 'flavors', 'sizes'] as $list) {
            $ccf[$list] = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($ccf[$list] ?? []))));
        }
        $ccf['colors'] = array_values(array_filter(array_map(function ($c) {
            $c = (array) $c;
            $name = trim((string) ($c['name'] ?? ''));
            $hex = strtolower(trim((string) ($c['hex'] ?? '')));
            if (! preg_match('/^#[0-9a-f]{3,8}$/', $hex)) {
                $hex = '#fbe3c4';
            }

            return $name === '' ? null : ['name' => $name, 'hex' => $hex];
        }, (array) ($ccf['colors'] ?? []))));
        $ccf['typography'] = SiteContent::normalizeTypography($ccf['typography'] ?? null);
        $updates['customCakeForm'] = $ccf;
        $updates['newsletter'] = (array) ($updates['newsletter'] ?? []);
        $updates['payment'] = (array) ($updates['payment'] ?? []);
        $updates['authPanel'] = (array) ($updates['authPanel'] ?? []);
        // Unchecked checkboxes don't submit — coerce to real booleans.
        $updates['authPanel']['showGoogle'] = $request->boolean('authPanel.showGoogle');
        $updates['authPanel']['showFacebook'] = $request->boolean('authPanel.showFacebook');
        $updates['authPanel']['typography'] = SiteContent::normalizeTypography($updates['authPanel']['typography'] ?? null);
        $updates['social'] = (array) ($updates['social'] ?? []);
        $updates['buttons'] = (array) ($updates['buttons'] ?? []);

        $updates['maintenance'] = (array) ($updates['maintenance'] ?? []);
        $updates['maintenance']['enabled'] = $request->boolean('maintenance.enabled');
        $updates['maintenance']['typography'] = SiteContent::normalizeTypography($updates['maintenance']['typography'] ?? null);

        $updates['menuPromo'] = (array) ($updates['menuPromo'] ?? []);
        $updates['menuPromo']['enabled'] = $request->boolean('menuPromo.enabled');
        // Each slide may carry linked product ids (bundle) — keep them a clean
        // list of strings so the menu JS can match them against the catalogue.
        // bundlePrice is the authoritative charged price for the whole bundle
        // (OrderCreationService re-reads it from the saved blob at order time);
        // blank means "charge the products' regular total".
        $updates['menuPromo']['slides'] = array_values(array_map(function ($s) {
            $s = (array) $s;
            $s['products'] = array_values(array_filter(array_map(
                fn ($id) => trim((string) $id),
                (array) ($s['products'] ?? [])
            )));
            $s['bundlePrice'] = ($s['bundlePrice'] ?? '') === '' ? null : max(0, (float) $s['bundlePrice']);

            return $s;
        }, (array) ($updates['menuPromo']['slides'] ?? [])));

        $fr = (array) ($updates['franchise'] ?? []);
        $fr['hero'] = (array) ($fr['hero'] ?? []);
        $fr['hero']['typography'] = SiteContent::normalizeTypography($fr['hero']['typography'] ?? null);
        // Per-section show/hide toggles ("0"/"1" via hidden+checkbox pairs).
        $fr['visible'] = array_map(fn ($v) => (bool) $v, (array) ($fr['visible'] ?? []));
        $fr['perks'] = array_values((array) ($fr['perks'] ?? []));
        $fr['steps'] = array_values((array) ($fr['steps'] ?? []));
        $fr['packagesEnabled'] = $request->boolean('franchise.packagesEnabled');
        $fr['packages'] = array_values(array_map(function ($pkg) {
            $pkg = (array) $pkg;
            $pkg['features'] = $this->linesToArray($pkg['features'] ?? '');
            $pkg['featured'] = ! empty($pkg['featured']);

            return $pkg;
        }, (array) ($fr['packages'] ?? [])));
        $updates['franchise'] = $fr;

        $fo = (array) ($updates['footer'] ?? []);
        $columns = array_values((array) ($fo['columns'] ?? []));
        $fo['columns'] = array_map(function ($col) {
            $col = (array) $col;
            $col['links'] = array_values((array) ($col['links'] ?? []));

            return $col;
        }, $columns);
        $updates['footer'] = $fo;

        return $updates;
    }

    private function linesToArray($text): array
    {
        if (is_array($text)) {
            return array_values(array_filter(array_map('trim', $text)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
    }

    /* -------------------------------------------------------------------- */
    /* Menu Categories — categories are derived from the products table, so */
    /* renaming (= merging) and deleting (= reassigning) mutate Product rows */
    /* directly and take effect immediately (not deferred to the "Save      */
    /* changes" button above). The declared-categories list and per-category */
    /* image map still live in the content blob and are saved the same      */
    /* read-merge-write way as the rest of this controller, but via their   */
    /* own small "Save categories" button so this tab is fully self-        */
    /* contained (it straddles two different data sources: Product rows and */
    /* the content blob).                                                   */
    /* -------------------------------------------------------------------- */

    public function saveCategories(Request $request)
    {
        $this->authorizeEditor($request);

        $current = SiteContent::find(1)?->data ?? [];
        $declared = array_values(array_filter(array_map('trim', (array) $request->input('menuCategories', []))));
        $images = (array) $request->input('menuCategoryImages', []);
        $images = array_filter($images, fn ($url) => trim((string) $url) !== '');

        $current['menuCategories'] = $declared;
        $current['menuCategoryImages'] = $images;
        // The tab-header "Show on landing" toggle for the category grid.
        $current['categoriesVisible'] = $request->boolean('categoriesVisible');

        SiteContent::updateOrCreate(['id' => 1], ['data' => $current]);
        Cache::forget('site-content');

        return redirect()->route('admin.content', ['section' => 'menuCategories'])->with('status', 'Categories saved.');
    }

    public function renameCategory(Request $request, string $category)
    {
        $this->authorizeEditor($request);

        $to = trim((string) $request->input("rename_to.{$category}", ''));
        if ($to !== '' && $to !== $category) {
            Product::where('category', $category)->update(['category' => $to]);

            $content = SiteContent::find(1)?->data ?? [];
            $declared = array_map(fn ($c) => $c === $category ? $to : $c, $content['menuCategories'] ?? []);
            $content['menuCategories'] = array_values(array_unique($declared));

            $images = $content['menuCategoryImages'] ?? [];
            if (isset($images[$category]) && ! isset($images[$to])) {
                $images[$to] = $images[$category];
            }
            unset($images[$category]);
            $content['menuCategoryImages'] = $images;

            SiteContent::updateOrCreate(['id' => 1], ['data' => $content]);
            Cache::forget('site-content');
            Cache::forget('products.index');
        }

        return redirect()->route('admin.content', ['section' => 'menuCategories'])
            ->with('status', $to !== '' ? "Renamed \"{$category}\" to \"{$to}\"." : 'Enter a name to rename to.');
    }

    public function deleteCategory(Request $request, string $category)
    {
        $this->authorizeEditor($request);

        $to = trim((string) $request->input("delete_to.{$category}", ''));
        Product::where('category', $category)->update(['category' => ($to !== '' && $to !== 'Other') ? $to : null]);

        $content = SiteContent::find(1)?->data ?? [];
        $content['menuCategories'] = array_values(array_filter($content['menuCategories'] ?? [], fn ($c) => $c !== $category));
        $images = $content['menuCategoryImages'] ?? [];
        unset($images[$category]);
        $content['menuCategoryImages'] = $images;

        SiteContent::updateOrCreate(['id' => 1], ['data' => $content]);
        Cache::forget('site-content');
        Cache::forget('products.index');

        // back(): reachable from both the Menu Categories tab and the
        // Products toolbar's 🗑 button — return to whichever sent it.
        return back(fallback: route('admin.content', ['section' => 'menuCategories']))
            ->with('status', "Deleted \"{$category}\".");
    }
}
