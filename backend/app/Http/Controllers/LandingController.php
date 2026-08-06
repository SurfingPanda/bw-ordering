<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LandingController extends Controller
{
    /**
     * Landing-page subset of the SPA's DEFAULT_CONTENT
     * (frontend/src/lib/content.js), ported verbatim so the page never breaks
     * on partial/missing CMS content. Only the keys this page reads are
     * included here — other CMS sections (franchise, careers, menu, ...) get
     * their own defaults when those pages are ported.
     *
     * Public because the Site Content editor (Admin\SiteContentController)
     * pre-fills its form from these same defaults, so a fresh site's first
     * save persists what the public pages were already rendering.
     */
    public const DEFAULT_CONTENT = [
        'maintenance' => [
            'enabled' => false,
            'title' => 'We’ll be right back',
            'message' => 'Our landing page is getting a fresh bake. Please check back soon — thanks for your patience!',
        ],
        'announcement' => '🚚 Free delivery on orders over ₱1,000  •  Freshly baked every morning  •  Order now and taste the love!',
        'banners' => [
            ['img' => '/images/Gemini_Generated_Image_wrt1thwrt1thwrt1.png', 'alt' => 'For the Best Dad — cake promo'],
            ['img' => '/images/Gemini_Generated_Image_wugzcgwugzcgwugz.png', 'alt' => 'For the Best Mom — cake promo'],
        ],
        'whatsNew' => [
            'eyebrow' => 'Fresh off the oven',
            'title' => "What's New?",
            'subtitle' => "The latest additions to our bakeshop — try them while they're still warm.",
        ],
        'buttons' => [
            'navOrder' => true,
            'navSignIn' => true,
            'bestSellersMenu' => true,
            'promoOrder' => true,
            'storeLocatorFind' => true,
            'newsletterSubscribe' => true,
            'menuCheckout' => true,
        ],
        'customCake' => [
            'eyebrow' => 'Celebrate every moment',
            'title' => 'Custom cakes for birthdays & special occasions',
            'subtitle' => 'Make it unforgettable with a personalized cake, baked fresh and decorated just the way you want it.',
            'buttonLabel' => 'Order a custom cake',
            'image' => '/images/custom-cake-tower.svg',
            'alt' => 'A tall three-tier custom celebration cake with drip icing and a candle on top',
            'buttonLink' => '/custom-cake',
        ],
        'storeLocator' => [
            'title' => '60+ stores, always near you',
            'subtitle' => 'Find your nearest branch or simply order online for delivery and pickup.',
            'placeholder' => 'Enter your city or area',
            'visible' => true,
        ],
        // The /stores page's own hero — distinct from storeLocator above,
        // which is just the landing page's teaser section.
        'storesPage' => [
            'title' => 'Find a store',
            'subtitle' => '60+ branches nationwide. Search for the BW Superbakeshop nearest you.',
        ],
        'newsletter' => [
            'title' => 'Get sweet deals in your inbox 🍰',
            'subtitle' => 'Subscribe for exclusive promos, new treats, and special occasion offers.',
            'placeholder' => 'Enter your email',
            'buttonLabel' => 'Subscribe',
        ],
        'social' => [
            'facebook' => 'https://www.facebook.com/bwsuperbakeshop',
            'tiktok' => '',
            'x' => '',
        ],
        'legal' => [
            'privacy' => [
                'title' => 'Privacy Policy',
                'description' => 'How BW Superbakeshop collects, uses, and protects your personal information.',
                'lastUpdated' => 'August 6, 2026',
                'body' => "Information we collect\nWhen you create an account, place an order, or submit a custom cake inquiry, we collect information such as your name, email address, contact number, delivery address, and order details.\n\nHow we use your information\nWe use your information to process and fulfill orders, communicate order and account updates, respond to inquiries, and improve our products and services. We do not sell your personal information to third parties.\n\nPayments and authentication\nOnline payments are processed by PayMongo. Account authentication is handled by Supabase. We do not store your full card or e-wallet credentials, and passwords are not stored in our application database.\n\nYour choices\nYou may update your account information at any time or contact us to request correction or deletion of your personal data, subject to records we are required to keep.",
            ],
            'terms' => [
                'title' => 'Terms of Service',
                'description' => 'The terms and conditions for ordering from and using BW Superbakeshop.',
                'lastUpdated' => 'August 6, 2026',
                'body' => "Acceptance of terms\nBy using BW Superbakeshop's website to browse, order, or submit a custom cake inquiry, you agree to these Terms of Service.\n\nOrders and pricing\nAll prices are shown in Philippine Peso and may change without notice. Order totals, delivery fees, and applicable vouchers are calculated and confirmed at checkout.\n\nPayment, delivery, and pickup\nOnline payments are confirmed by our payment gateway before an order is marked paid. Delivery availability, fees, and pickup times depend on the chosen store and order.\n\nCancellations and refunds\nCancellation and refund requests are handled case by case. Please contact the store handling your order as soon as possible if you need to change it.",
            ],
            'dataDeletion' => [
                'title' => 'Data Deletion Request',
                'description' => 'How to request deletion of your BW Superbakeshop account and personal data.',
                'lastUpdated' => 'August 6, 2026',
                'body' => "Requesting deletion\nTo request deletion of your BW Superbakeshop account and personal data, contact us using the contact details on our Contact page. Please include the email address and contact number associated with your account so we can verify your request.\n\nWhat we delete\nAfter we verify your request, we will delete or anonymize personal data that is no longer needed for a legitimate business or legal purpose.\n\nRecords we may retain\nWe may retain limited information where required for tax, accounting, fraud prevention, dispute resolution, or other legal obligations. Retained records are kept only for the required period.\n\nRequest status\nWe will confirm receipt of your request and let you know once it has been completed or if we need additional information to verify your identity.",
            ],
        ],
        'footer' => [
            'logo' => '/images/logo (1).png',
            'brand' => 'Superbakeshop',
            'description' => 'Freshly baked. Made with love. Ordered with ease. Bringing bakeshop happiness to your doorstep.',
            'copyright' => '© 2026 BW Superbakeshop. All rights reserved.',
            'columns' => [
                ['title' => 'Shop', 'links' => [
                    ['label' => 'Cakes', 'url' => '/menu'],
                    ['label' => 'Breads', 'url' => '/menu'],
                    ['label' => 'Pastries', 'url' => '/menu'],
                    ['label' => 'Delicacies', 'url' => '/menu'],
                ]],
                ['title' => 'Company', 'links' => [
                    ['label' => 'About Us', 'url' => '/about'],
                    ['label' => 'Our Stores', 'url' => '/stores'],
                    ['label' => 'Contact', 'url' => '/contact'],
                ]],
                ['title' => 'Support', 'links' => [
                    ['label' => 'Help Center', 'url' => ''],
                    ['label' => 'Delivery Info', 'url' => ''],
                    ['label' => 'Returns', 'url' => ''],
                    ['label' => 'FAQs', 'url' => ''],
                ]],
            ],
        ],
    ];

    /** Maps the products.status column to the badge label the old CMS-curated cards used. */
    private const STATUS_TAGS = [
        'best_seller' => 'Best Seller',
        'new' => 'New',
        'sold_out' => 'Sold Out',
    ];

    /**
     * Best Sellers / What's New render in a 4-wide grid (md:grid-cols-4) —
     * cap at two full rows. Unlike the old CMS-curated card lists, these are
     * every product with a given status, which is unbounded: an editor could
     * flag 50 products best_seller and this section would otherwise render
     * all 50 on the landing page.
     */
    private const LANDING_GRID_LIMIT = 8;

    public function index(Request $request)
    {
        // Site Editor live preview (?preview=1, editor session) shows the
        // unsaved draft; everyone else sees the saved blob.
        $saved = $this->previewDraft($request) ?? (SiteContent::find(1)?->data ?? []);

        // Top-level shallow merge, mirroring content.js's getSiteContent()
        // (`{ ...DEFAULT_CONTENT, ...data }`) — a saved top-level key fully
        // replaces the default. A handful of sections then re-merge their own
        // defaults underneath (matching the JSX components that do the same
        // `{ ...DEFAULT_CONTENT.x, ...data.x }` at render time) so partially
        // saved sub-objects still fall back cleanly key-by-key.
        $content = array_merge(self::DEFAULT_CONTENT, $saved);
        $content['maintenance'] = array_merge(self::DEFAULT_CONTENT['maintenance'], $content['maintenance'] ?? []);

        $user = session('supabase_user');
        $viewData = [
            'content' => $content,
            'user' => $user,
            'accountRoute' => $this->accountRoute($user['email'] ?? null),
            'bestSellers' => [],
            'whatsNewProducts' => [],
            'categories' => [],
        ];

        if ($content['maintenance']['enabled'] ?? false) {
            return view('landing', $viewData);
        }

        $content['customCake'] = array_merge(self::DEFAULT_CONTENT['customCake'], $content['customCake'] ?? []);
        $content['storeLocator'] = array_merge(self::DEFAULT_CONTENT['storeLocator'], $content['storeLocator'] ?? []);
        $content['newsletter'] = array_merge(self::DEFAULT_CONTENT['newsletter'], $content['newsletter'] ?? []);
        $content['footer'] = array_merge(self::DEFAULT_CONTENT['footer'], $content['footer'] ?? []);

        // Best Sellers / category grid are sourced from the live products
        // table — same cache key as ProductController::index() so the admin
        // product sync's Cache::forget('products.index') keeps invalidating
        // this page too. (The old SPA rendered a CMS-curated, hand-written
        // list here instead; that list goes stale the moment the real catalog
        // changes, so this page now reflects the actual menu.)
        $products = Cache::remember('products.index', now()->addMinutes(10), fn () => Product::whereNull('archived_at')
            ->orderBy('category')
            ->orderBy('name')
            ->get());

        $viewData['content'] = $content;
        $viewData['bestSellers'] = $products->where('status', 'best_seller')->take(self::LANDING_GRID_LIMIT)->values()
            ->map(fn (Product $p) => $this->presentProduct($p))->all();
        // What's New is likewise products-table-sourced: every product whose
        // status is "new" (set in the admin Products editor), not a
        // CMS-curated card list.
        $viewData['whatsNewProducts'] = $products->where('status', 'new')->take(self::LANDING_GRID_LIMIT)->values()
            ->map(fn (Product $p) => $this->presentProduct($p))->all();
        $viewData['categories'] = $this->categoriesFrom(
            $products,
            $content['menuCategoryImages'] ?? [],
            (array) ($content['menuCategories'] ?? []),
        );

        // Same cached list /stores' full locator uses (StoreController),
        // trimmed to the fields the landing map preview's markers/popups
        // need — see resources/js/landing-map.js.
        $viewData['mapStores'] = app(StoreController::class)->cachedList()->map(fn ($s) => [
            'name' => $s->name,
            'address' => $s->address,
            'hours' => $s->hours,
            'latitude' => $s->latitude,
            'longitude' => $s->longitude,
        ])->values();
        // The map script is loaded lazily (dynamic import(), triggered only
        // once the section nears the viewport — see landing.blade.php) rather
        // than via @vite(), so maplibre-gl's ~290KB never costs anything for
        // visitors who don't scroll this far. That means resolving its URLs
        // by hand instead of letting @vite() emit the tags.
        $viewData['mapJsSrc'] = \Illuminate\Support\Facades\Vite::asset('resources/js/landing-map.js');
        $viewData['mapCssHref'] = $this->viteEntryCssHref('resources/js/landing-map.js');

        return view('landing', $viewData);
    }

    /**
     * The stylesheet a Vite entry pulls in transitively (here: landing-map.js
     * → the shared maplibre-gl chunk → maplibre-gl.css), as a plain href —
     * @vite()/Vite::asset() only ever resolve the entry's own JS URL, and
     * there's no public API for "just the CSS this entry depends on" since
     * normally @vite() emits both tags itself. In local dev (Vite dev server
     * running) this returns null and that's correct: Vite's dev client
     * injects imported CSS itself as the module loads, no <link> needed.
     */
    private function viteEntryCssHref(string $entry): ?string
    {
        $html = (string) app(\Illuminate\Foundation\Vite::class)([$entry]);
        preg_match('/<link[^>]+href="([^"]+\.css)"/', $html, $m);

        return $m[1] ?? null;
    }

    /** Map a Product row to the flat shape the product-card partial expects. */
    private function presentProduct(Product $p): array
    {
        $price = (float) $p->price;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'img' => $p->image_path,
            'tag' => self::STATUS_TAGS[$p->status] ?? null,
            'price' => '₱'.(fmod($price, 1.0) === 0.0 ? number_format($price, 0) : number_format($price, 2)),
            'desc' => $p->description,
            'calories' => $p->calories,
            'allergens' => $p->features ?? [],
        ];
    }

    /**
     * One card per distinct product category. The image comes only from the
     * Site Editor's Menu Categories tab (content.menuCategoryImages) — no
     * product-photo fallback, so that tab is the single source of what shows
     * here and on the /menu sidebar. Declared-but-still-empty categories
     * (content.menuCategories) are appended after the in-use ones so a
     * just-added category shows up before its first product exists.
     */
    private function categoriesFrom($products, array $categoryImages, array $declared = []): array
    {
        $categories = [];
        foreach ($products as $p) {
            // Products without a category (e.g. left behind by a category
            // delete) get no card — the /menu sidebar skips them too.
            $cat = $p->category;
            if ($cat && ! isset($categories[$cat])) {
                $categories[$cat] = [
                    'name' => $cat,
                    'img' => $categoryImages[$cat] ?? '',
                ];
            }
        }
        foreach ($declared as $cat) {
            if ($cat !== '' && ! isset($categories[$cat])) {
                $categories[$cat] = [
                    'name' => $cat,
                    'img' => $categoryImages[$cat] ?? '',
                ];
            }
        }

        return array_values($categories);
    }

    /** Mirrors frontend/src/pages/Login.jsx's landingRoute() post-login guess. */
    private function accountRoute(?string $email): string
    {
        if ($this->isAdmin($email)) {
            return '/admin';
        }
        if ($this->isEditor($email)) {
            return '/admin/content';
        }

        return '/dashboard';
    }
}
