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
        'whatsNewProducts' => [
            ['name' => 'Ube Chiffon Cake', 'price' => '₱720', 'tag' => 'New', 'img' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=600&q=80'],
            ['name' => 'Red Velvet Slice', 'price' => '₱150', 'tag' => 'New', 'img' => 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?auto=format&fit=crop&w=600&q=80'],
        ],
        'buttons' => [
            'navOrder' => true,
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
            'image' => '/images/custom-cakes.png',
            'alt' => 'Custom tiered celebration cakes — wedding, themed, and princess designs',
            'bannerLink' => '/menu',
            'buttonLink' => '/custom-cake',
        ],
        'newsletter' => [
            'title' => 'Get sweet deals in your inbox 🍰',
            'subtitle' => 'Subscribe for exclusive promos, new treats, and special occasion offers.',
            'placeholder' => 'Enter your email',
            'buttonLabel' => 'Subscribe',
        ],
        'social' => [
            'facebook' => 'https://www.facebook.com/bwsuperbakeshop',
            'linkedin' => '',
            'x' => '',
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
                    ['label' => 'About Us', 'url' => ''],
                    ['label' => 'Our Stores', 'url' => '/stores'],
                    ['label' => 'Contact', 'url' => ''],
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
            'categories' => [],
        ];

        if ($content['maintenance']['enabled'] ?? false) {
            return view('landing', $viewData);
        }

        $content['customCake'] = array_merge(self::DEFAULT_CONTENT['customCake'], $content['customCake'] ?? []);
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
        $viewData['bestSellers'] = $products->where('status', 'best_seller')->values()
            ->map(fn (Product $p) => $this->presentProduct($p))->all();
        $viewData['categories'] = $this->categoriesFrom($products, $content['menuCategoryImages'] ?? []);

        return view('landing', $viewData);
    }

    /** Map a Product row to the flat shape the product-card partial expects. */
    private function presentProduct(Product $p): array
    {
        $price = (float) $p->price;

        return [
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
     * One card per distinct product category, image = an editor override from
     * the Site Editor (content.menuCategoryImages) or, failing that, the first
     * product photo in that category — same fallback the Menu page's sidebar
     * badges use.
     */
    private function categoriesFrom($products, array $categoryImages): array
    {
        $categories = [];
        foreach ($products as $p) {
            $cat = $p->category ?: 'Other';
            if (! isset($categories[$cat])) {
                $categories[$cat] = [
                    'name' => $cat,
                    'img' => $categoryImages[$cat] ?? $p->image_path ?? '',
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
