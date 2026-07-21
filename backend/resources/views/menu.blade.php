<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Online — BW Superbakeshop</title>
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body class="bg-navy-50/40 text-navy-800">

    {{-- Product + voucher data for the vanilla-JS cart below. No JS bundler
         in this Blade migration, so the cart/menu interactivity is plain DOM
         script reading/writing localStorage — see CLAUDE.md's locked-in
         decision that carts stay client-side (`bw_cart`); the server
         (OrderCreationService, via /checkout) re-validates everything at
         submit time regardless. --}}
    <script id="menu-products-data" type="application/json">{!! $products->toJson() !!}</script>
    <script id="menu-promo-data" type="application/json">{!! json_encode($menuPromo) !!}</script>
    <script id="menu-category-images-data" type="application/json">{!! json_encode($categoryImages) !!}</script>
    <script id="menu-declared-categories-data" type="application/json">{!! json_encode($declaredCategories) !!}</script>

    <div class="flex min-h-screen flex-col lg:h-screen lg:flex-row lg:overflow-hidden">
        {{-- categories sidebar — sticky at the top on mobile (where it's a
             horizontal-scroll bar, not a sidebar) so switching categories
             doesn't require scrolling back up; on lg+ it's already
             effectively pinned since the whole page is height-locked and
             only the main column scrolls (see the header's own comment
             below), so sticky is turned back off there. --}}
        <aside class="sticky top-0 z-30 border-b border-navy-900/10 bg-navy-900 lg:static lg:flex lg:h-full lg:w-60 lg:shrink-0 lg:flex-col">
            <a href="/" class="hidden h-24 shrink-0 items-center justify-center px-4 lg:flex">
                <img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-20 w-auto">
            </a>
            {{-- Categories scroll on their own only if they overflow the
                 viewport (many categories) — the logo above stays put either
                 way, and the scrollbar itself is hidden (still scrolls via
                 wheel/touch/drag), so it never reads as a layout bug. --}}
            <nav id="category-nav" class="scrollbar-hide flex gap-3 overflow-x-auto p-3 lg:min-h-0 lg:flex-1 lg:flex-col lg:gap-1.5 lg:overflow-y-auto">
                {{-- filled by JS from the product list --}}
            </nav>
        </aside>

        {{-- main column: the one scrollable pane — header stays put via
             sticky, and this is the only element that scrolls on desktop. --}}
        <div class="flex min-w-0 flex-1 flex-col lg:h-full lg:overflow-y-auto">
            <header class="z-20 border-b border-slate-100 bg-white/90 backdrop-blur lg:sticky lg:top-0">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6">
                    <h2 class="text-lg font-bold text-navy-800">Order Online</h2>
                    <div class="flex items-center gap-4">
                        @if($user)
                            @if($isAdmin)
                                <a href="{{ route('admin.dashboard') }}" class="rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600">Admin</a>
                            @elseif($isEditor)
                                <a href="{{ route('admin.content') }}" class="rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600">Edit Site</a>
                            @endif
                            @if($isCashier)
                                <a href="{{ route('admin.dashboard') }}" class="rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600">Cashier</a>
                            @endif

                            <div class="relative" id="account-menu-wrap">
                                <button type="button" id="account-menu-btn" aria-haspopup="menu" aria-expanded="false"
                                    class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm font-medium text-navy-700 transition hover:bg-navy-50 hover:text-brand-600">
                                    <span class="relative flex h-8 w-8 items-center justify-center rounded-full bg-navy-50 text-navy-800">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />
                                        </svg>
                                        {{-- active-orders badge, mirrors #cart-count-badge; hidden
                                             while the dropdown is open (the count shows inside it) --}}
                                        @if(($activeOrders ?? 0) > 0)
                                            <span id="account-orders-badge" class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-500 px-1 text-[0.6rem] font-bold text-white">{{ $activeOrders }}</span>
                                        @endif
                                    </span>
                                    <span class="hidden sm:block">{{ explode(' ', $user['name'] ?? 'Account')[0] }}</span>
                                    <svg id="account-menu-chevron" class="h-4 w-4 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                                <div id="account-menu" role="menu" class="absolute right-0 top-full z-30 mt-2 hidden w-44 overflow-hidden rounded-xl border border-slate-100 bg-white py-1 shadow-lg">
                                    <div class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500">
                                        Signed in as<br>
                                        <span class="font-semibold text-navy-700">{{ $user['name'] ?? $user['email'] ?? '' }}</span>
                                    </div>
                                    <a href="/profile" role="menuitem" class="block px-4 py-2 text-sm font-medium text-navy-700 transition hover:bg-navy-50 hover:text-brand-600">Profile</a>
                                    <a href="{{ route('my-orders') }}" role="menuitem" class="flex items-center justify-between px-4 py-2 text-sm font-medium text-navy-700 transition hover:bg-navy-50 hover:text-brand-600">
                                        My Orders
                                        @if(($activeOrders ?? 0) > 0)
                                            <span class="rounded-full bg-brand-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $activeOrders }}</span>
                                        @endif
                                    </a>
                                    <button type="button" role="menuitem" onclick="document.getElementById('account-menu').classList.add('hidden'); showLogoutConfirm();"
                                        class="block w-full px-4 py-2 text-left text-sm font-medium text-navy-700 transition hover:bg-navy-50 hover:text-brand-600">
                                        Logout
                                    </button>
                                </div>
                            </div>
                        @else
                            <a href="/" class="text-sm font-medium text-navy-700 transition hover:text-brand-600">← Back to home</a>
                        @endif

                        {{-- cart icon, desktop --}}
                        <div class="relative hidden h-10 w-10 items-center justify-center rounded-full bg-navy-50 text-navy-800 lg:flex">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                            </svg>
                            <span class="cart-count-icon absolute -right-1 -top-1 hidden h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1 text-[0.65rem] font-bold text-white">0</span>
                        </div>
                        {{-- cart icon, mobile (opens the drawer) --}}
                        <button type="button" id="open-cart" class="relative flex h-10 w-10 items-center justify-center rounded-full bg-navy-50 text-navy-800 lg:hidden">
                            🛒
                            <span id="cart-count-badge" class="absolute -right-1 -top-1 hidden h-5 min-w-5 items-center justify-center rounded-full bg-brand-500 px-1 text-[0.65rem] font-bold text-white">0</span>
                        </button>
                    </div>
                </div>
            </header>

            @if($user)
                <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
                <div id="logout-confirm-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" onclick="hideLogoutConfirm(event)" role="dialog" aria-modal="true" aria-label="Log out?">
                    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
                        <h3 class="text-lg font-bold text-navy-800">Log out?</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">You'll be signed out of your account.</p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" onclick="hideLogoutConfirm()" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">Cancel</button>
                            <button type="button" onclick="document.getElementById('logout-form').submit()" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">Log out</button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-1 flex-col lg:flex-row">
                <main class="flex-1 px-4 py-6 sm:px-6">
                    @if(request('staff') === 'blocked')
                        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Staff accounts can't place orders. Browse freely — checkout is for customer accounts.
                        </div>
                    @endif
                    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 id="category-title" class="text-2xl font-bold text-navy-800">Our Menu</h1>
                            <p class="mt-1 text-sm text-slate-500">Tap a treat to add it to your cart.</p>
                        </div>
                        <div class="relative w-full sm:w-72">
                            <input type="search" id="menu-search" placeholder="Search treats…"
                                class="w-full rounded-full border border-slate-300 bg-white py-2.5 pl-4 pr-4 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </div>
                    </div>

                    <div id="promo-banner"></div>
                    <div id="tag-filters" class="mb-5 hidden flex-wrap gap-2"></div>

                    <div id="product-grid" class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4"></div>
                    <p id="empty-state" class="hidden py-16 text-center text-sm text-slate-500">No treats found.</p>
                </main>

                {{-- cart (desktop) --}}
                <div class="hidden bg-white lg:block lg:w-96 lg:shrink-0">
                    <div class="lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)]">
                        @include('partials.cart')
                    </div>
                </div>
            </div>
        </div>

        {{-- cart drawer (mobile) --}}
        <div id="cart-drawer" class="fixed inset-0 z-50 hidden lg:hidden">
            <div class="absolute inset-0 bg-black/40" onclick="document.getElementById('cart-drawer').classList.add('hidden')"></div>
            <div class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-white shadow-2xl">
                <button type="button" onclick="document.getElementById('cart-drawer').classList.add('hidden')"
                    class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-navy-50 text-navy-800">✕</button>
                @include('partials.cart')
            </div>
        </div>

        {{-- floating cart button (mobile) — reachable from anywhere on the
             page without scrolling back up to the header's cart icon. Below
             the drawer's z-50 so opening the drawer covers it; badge reuses
             .cart-count-icon, the same class the desktop header cart icon
             uses, so renderCart() updates it with no extra JS. --}}
        <button type="button" id="open-cart-fab" aria-label="Open cart"
            class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/40 transition hover:scale-105 lg:hidden">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
            </svg>
            <span class="cart-count-icon absolute -right-2 -top-2 hidden h-6 min-w-6 items-center justify-center rounded-full bg-white px-1.5 text-xs font-bold text-brand-600 ring-2 ring-brand-500">0</span>
        </button>
    </div>

    {{-- Product detail modal (port of Menu.jsx's ProductMenuModal), filled by
         renderProductModal() from the clicked card's product. --}}
    @include('partials.product-modal')

    {{-- Shared pricing formula + peso formatter (also used by checkout.blade.php). --}}
    @include('partials.order-pricing')

    <script>
    (function () {
        const { peso, computeTotals, renderTotalsHTML } = window.OrderPricing;

        const PRODUCTS = JSON.parse(document.getElementById('menu-products-data').textContent || '[]');
        const MENU_PROMO = JSON.parse(document.getElementById('menu-promo-data').textContent || '{}');
        const CATEGORY_IMAGES = JSON.parse(document.getElementById('menu-category-images-data').textContent || '{}');
        const DECLARED_CATEGORIES = JSON.parse(document.getElementById('menu-declared-categories-data').textContent || '[]');
        const STATUS_LABEL = { new: 'New', best_seller: 'Best Seller', bundle: 'Bundle', sold_out: 'Sold out' };

        // "Includes: Jumbo Loaf ×1, Mongo Loaf ×2" — resolves a bundle's
        // linked-product-id => quantity map to display names. '' for
        // non-bundles or bundles with nothing linked yet.
        function bundleIncludesText(p) {
            if (p.type !== 'bundle' || !p.bundle_product_ids) return '';
            const parts = Object.entries(p.bundle_product_ids).map(([id, qty]) => {
                const linked = PRODUCTS.find(x => x.id === id);
                return linked ? `${linked.name} ×${qty}` : null;
            }).filter(Boolean);
            return parts.length ? `Includes: ${parts.join(', ')}` : '';
        }

        // Products without an image show this default picture instead; if it
        // fails to load, the capturing error listener below degrades any
        // <img data-img-fallback> to the "no image" tile (or, for the small
        // cart thumbs — data-img-fallback="remove" — just clears the img,
        // leaving the plain gray box). Capture phase because error events
        // don't bubble, and one listener covers every re-render.
        const FALLBACK_IMG = 'https://xhy0hjgguaqll6zn.public.blob.vercel-storage.com/custom-cake-refs/1781654816384-p23ferfqoq.png';
        document.addEventListener('error', (e) => {
            const img = e.target;
            if (!(img instanceof HTMLImageElement) || !img.hasAttribute('data-img-fallback')) return;
            if (img.getAttribute('data-img-fallback') === 'remove') { img.remove(); return; }
            img.parentElement?.classList.remove('animate-pulse');
            const tile = document.createElement('div');
            tile.className = 'flex h-full w-full items-center justify-center text-xs font-medium text-slate-400';
            tile.textContent = 'no image';
            img.replaceWith(tile);
        }, true);
        // Grid cards pulse their image tile while the photo loads (instead of
        // sitting on flat gray, which reads as broken/missing); 'load' doesn't
        // bubble either, hence the same capture-phase pattern as the listener above.
        document.addEventListener('load', (e) => {
            const img = e.target;
            if (!(img instanceof HTMLImageElement) || !img.hasAttribute('data-img-fallback')) return;
            img.parentElement?.classList.remove('animate-pulse');
        }, true);

        // Editor-controlled "What's New" promo slides (content.menuPromo) —
        // mirrors Menu.jsx's `promoSlides`/`promoActive`.
        const PROMO_SLIDES = MENU_PROMO.enabled === false ? [] : (MENU_PROMO.slides || [])
            .filter(s => s && (s.title || s.image || s.badge || s.description));
        const PROMO_ACTIVE = PROMO_SLIDES.length > 0;

        function readCart() {
            try { return JSON.parse(localStorage.getItem('bw_cart') || '{}') || {}; } catch { return {}; }
        }
        function writeCart(cart) {
            try { localStorage.setItem('bw_cart', JSON.stringify(cart)); } catch {}
        }

        let cart = readCart();

        // Deep-add from the landing page (?add=<name>&qty=<n> — the product
        // card quick-add icon and the shared product modal's "Order now"
        // button both link here since the real cart only lives on this
        // page). Resolves by name, folds the qty into the cart, then scrubs
        // the params so a refresh/share of the URL doesn't re-add it.
        (function () {
            const params = new URLSearchParams(window.location.search);
            const addName = params.get('add');
            if (addName) {
                const product = PRODUCTS.find(p => p.name.toLowerCase() === addName.toLowerCase());
                if (product) {
                    const qty = Math.max(1, parseInt(params.get('qty'), 10) || 1);
                    cart[product.id] = (cart[product.id] || 0) + qty;
                    writeCart(cart);
                    document.getElementById('cart-drawer')?.classList.remove('hidden');
                }
                params.delete('add');
                params.delete('qty');
                const qs = params.toString();
                history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
            }
        })();

        // Lands on "All" by default; a ?category= deep link (e.g. the landing
        // page's "See What's New" button) still opens its tab when it exists.
        // (categories() is hoisted, and only reads consts defined above.)
        const requestedTab = new URLSearchParams(window.location.search).get('category');
        let active = requestedTab && categories().some(c => c.name === requestedTab) ? requestedTab : 'All';
        let tag = 'all';
        let query = '';
        let voucher = null; // { code, type, value, label }
        let voucherDefs = {};

        fetch('/api/vouchers/active').then(r => r.ok ? r.json() : []).then(rows => {
            voucherDefs = {};
            (rows || []).forEach(v => { voucherDefs[v.code] = { type: v.type, value: Number(v.value) || 0, label: v.label || '' }; });
        }).catch(() => {});

        // ---- categories ----
        // Smart tabs ("What's New"/"Best Sellers") show only when matching
        // products (or, for What's New, an active promo) exist; real
        // categories use the editor-set badge image only (Site Editor →
        // Menu Categories is the single source — no product-photo fallback,
        // so what editors see there is exactly what renders here). Also
        // keeps a plain "All" tab (not present in the original nav) so the
        // full catalogue stays reachable — see Menu.jsx's `categories` memo.
        function categories() {
            const seen = new Set();
            PRODUCTS.forEach(p => { if (p.category) seen.add(p.category); });
            // Declared-but-still-empty categories (Site Editor) list too, so
            // a just-added category shows before its first product exists.
            DECLARED_CATEGORIES.forEach(name => seen.add(name));
            const hasNew = PRODUCTS.some(p => p.status === 'new');
            const hasBest = PRODUCTS.some(p => p.status === 'best_seller');
            const cats = [{ name: 'All', icon: 'all' }];
            if (hasNew || PROMO_ACTIVE) cats.push({ name: "What's New", icon: 'new' });
            if (hasBest) cats.push({ name: 'Best Sellers', icon: 'best' });
            seen.forEach(name => cats.push({ name, img: CATEGORY_IMAGES[name] || '' }));
            return cats;
        }

        const SPARKLE_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3-1.9 4.9L5 9.8l4.9 1.9L12 16.6l1.9-4.9 5-1.9-5-1.9z"/></svg>';
        const TROPHY_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/><path d="M17 5h3a2 2 0 0 1 2 2 4 4 0 0 1-4 4"/><path d="M7 5H4a2 2 0 0 0-2 2 4 4 0 0 0 4 4"/></svg>';
        const GRID_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>';
        const ICON_SVG = { new: SPARKLE_SVG, best: TROPHY_SVG, all: GRID_SVG };
        const ICON_GRADIENT = { new: 'from-emerald-400 to-teal-500', best: 'from-amber-400 to-orange-500', all: 'from-sky-400 to-blue-500' };
        const TRASH_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';

        function renderCategories() {
            const nav = document.getElementById('category-nav');
            nav.innerHTML = '';
            categories().forEach(c => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'flex shrink-0 items-center gap-3 rounded-full p-1.5 pr-5 text-sm font-semibold transition lg:w-full ' +
                    (active === c.name ? 'bg-white text-navy-900 shadow-lg ring-2 ring-brand-500' : 'text-white hover:bg-white/10');
                let badge = '';
                if (c.icon) {
                    badge = `<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br ${ICON_GRADIENT[c.icon]} text-white shadow ring-1 ring-black/5 [&_svg]:h-5 [&_svg]:w-5">${ICON_SVG[c.icon]}</span>`;
                } else if (c.img !== undefined) {
                    badge = `<span class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-white shadow ring-1 ring-black/5">${c.img ? `<img src="${c.img}" alt="" class="h-full w-full object-cover">` : ''}</span>`;
                }
                btn.innerHTML = badge + `<span>${c.name}</span>`;
                btn.addEventListener('click', () => { active = c.name; tag = 'all'; renderAll(); });
                nav.appendChild(btn);
            });
        }

        // ---- product grid ----
        function inActiveCategory(p) {
            if (active === 'All') return true;
            if (active === "What's New") return p.status === 'new';
            if (active === 'Best Sellers') return p.status === 'best_seller';
            return p.category === active;
        }

        function availableTags() {
            const present = new Set(PRODUCTS.filter(inActiveCategory).map(p => p.status).filter(Boolean));
            return ['new', 'best_seller', 'bundle'].filter(t => present.has(t));
        }

        function renderTagFilters() {
            const wrap = document.getElementById('tag-filters');
            const tags = availableTags();
            if (!tags.length) { wrap.classList.add('hidden'); wrap.innerHTML = ''; return; }
            wrap.classList.remove('hidden');
            wrap.innerHTML = '';
            [{ key: 'all', label: 'All' }, ...tags.map(t => ({ key: t, label: STATUS_LABEL[t] }))].forEach(t => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = t.label;
                btn.className = 'rounded-full px-4 py-1.5 text-sm font-semibold transition ' +
                    (tag === t.key ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/30' : 'bg-white text-navy-700 ring-1 ring-slate-200 hover:bg-slate-50');
                btn.addEventListener('click', () => { tag = t.key; renderGrid(); renderTagFilters(); });
                wrap.appendChild(btn);
            });
        }

        function visibleProducts() {
            const q = query.trim().toLowerCase();
            return PRODUCTS.filter(p => {
                const matchesTag = tag === 'all' || p.status === tag;
                const matches = !q || p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q);
                return inActiveCategory(p) && matchesTag && matches;
            });
        }

        function qtyControls(p) {
            const qty = cart[p.id] || 0;
            // ml-auto (not justify-between on the row — see renderGrid) keeps
            // this pinned to the right when it fits next to the price, and
            // lets it wrap to its own full-width row instead of being clipped
            // by the card's overflow-hidden when it doesn't (narrow 2-column
            // mobile cards + a longer price + "In cart · N" pill).
            if (p.status === 'sold_out') {
                return '<span class="ml-auto rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-400">Sold out</span>';
            }
            if (qty === 0) {
                return `<button type="button" data-add="${p.id}" class="ml-auto rounded-full bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600">Add</button>`;
            }
            // Quantity itself is only ever adjusted/removed in the cart panel;
            // the card just confirms it's in the cart and offers a quick +1.
            return `<div class="ml-auto flex items-center gap-1.5">
                <span class="flex items-center gap-1 whitespace-nowrap rounded-full bg-brand-50 px-2.5 py-1.5 text-xs font-semibold text-brand-600">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                    In cart · ${qty}
                </span>
                <button type="button" data-add="${p.id}" aria-label="Add another ${p.name}" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-navy-100 text-base font-bold text-navy-800 hover:bg-brand-500 hover:text-white">+</button>
            </div>`;
        }

        // ---- promo banner (What's New tab, content.menuPromo) ----
        let promoIndex = 0;
        let promoTimer = null;
        function renderPromoBanner() {
            const wrap = document.getElementById('promo-banner');
            if (active !== "What's New" || !PROMO_ACTIVE) {
                wrap.innerHTML = '';
                if (promoTimer) { clearInterval(promoTimer); promoTimer = null; }
                return;
            }
            const i = promoIndex % PROMO_SLIDES.length;
            const s = PROMO_SLIDES[i];
            wrap.innerHTML = `
                <div class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-amber-500 via-brand-500 to-orange-600 shadow-lg shadow-brand-500/25">
                    <div class="flex flex-col-reverse sm:flex-row">
                        <div class="flex flex-1 flex-col justify-center gap-2 p-6 sm:p-8">
                            ${s.badge ? `<span class="w-fit rounded-full bg-white/25 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-wider text-white backdrop-blur-sm">${s.badge}</span>` : ''}
                            ${s.title ? `<h2 class="text-2xl font-bold text-white drop-shadow-sm sm:text-3xl">${s.title}</h2>` : ''}
                            ${s.description ? `<p class="line-clamp-2 max-w-md text-sm text-white/90">${s.description}</p>` : ''}
                            ${(s.price || s.buttonLabel) ? `<div class="mt-1 flex items-center gap-3">
                                ${s.price ? `<span class="text-xl font-bold text-white drop-shadow-sm">${s.price}</span>` : ''}
                                ${s.buttonLabel ? `<button type="button" id="promo-btn" class="rounded-full bg-white px-5 py-2 text-sm font-bold text-brand-600 shadow-md transition hover:bg-white/90">${s.buttonLabel}</button>` : ''}
                            </div>` : ''}
                            ${PROMO_SLIDES.length > 1 ? `<div class="mt-3 flex gap-1.5">${PROMO_SLIDES.map((slide, idx) => `<button type="button" data-promo-dot="${idx}" aria-label="Show ${slide.title || ('slide ' + (idx + 1))}" class="h-1.5 rounded-full transition-all ${idx === i ? 'w-5 bg-white' : 'w-1.5 bg-white/40 hover:bg-white/60'}"></button>`).join('')}</div>` : ''}
                        </div>
                        <div class="relative h-40 w-full shrink-0 overflow-hidden bg-gradient-to-br from-brand-500 to-orange-600 sm:h-auto sm:w-72">
                            ${s.image ? `<img src="${s.image}" alt="${s.title || 'Promo'}" class="h-full w-full object-cover">` : `<div class="flex h-full w-full items-center justify-center text-5xl opacity-90">🧁</div>`}
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-orange-600/40 to-transparent sm:bg-gradient-to-r sm:from-brand-500/60 sm:to-transparent"></div>
                        </div>
                    </div>
                </div>`;

            const btn = document.getElementById('promo-btn');
            if (btn) btn.addEventListener('click', () => handlePromoButton(s));
            wrap.querySelectorAll('[data-promo-dot]').forEach(dot => dot.addEventListener('click', () => {
                promoIndex = Number(dot.dataset.promoDot);
                renderPromoBanner();
                resetPromoTimer();
            }));

            resetPromoTimer();
        }
        function resetPromoTimer() {
            if (promoTimer) clearInterval(promoTimer);
            if (PROMO_SLIDES.length > 1) {
                promoTimer = setInterval(() => { promoIndex = (promoIndex + 1) % PROMO_SLIDES.length; renderPromoBanner(); }, 4500);
            }
        }
        function handlePromoButton(slide) {
            // Bundle slide: add one bundle line named after the promo and open
            // the cart. The server verifies the exact linked-product set, so
            // the bundle is only orderable while every component is live — if
            // any is archived or sold out, the button does nothing rather than
            // charging the promo price for a partial bundle. The legacy
            // button-link fallback below still serves pre-bundle promos.
            const linked = slide?.products || [];
            if (linked.length) {
                const all = linked.map(id => PRODUCTS.find(p => p.id === id));
                if (all.every(p => p && p.status !== 'sold_out')) {
                    add('bundle:' + encodeURIComponent((slide.title || '').trim() || 'Promo bundle') + ':' + linked.join(','));
                    document.getElementById('cart-drawer').classList.remove('hidden');
                }
                return;
            }
            const link = (slide?.buttonLink || '').trim();
            if (link) {
                try {
                    const url = new URL(link, window.location.origin);
                    const addName = url.searchParams.get('add');
                    if (addName) {
                        const product = PRODUCTS.find(p => p.name.toLowerCase() === addName.toLowerCase());
                        if (product) { add(product.id); document.getElementById('cart-drawer').classList.remove('hidden'); return; }
                    }
                    if (url.origin === window.location.origin) window.location.href = url.pathname + url.search;
                    else window.open(link, '_blank', 'noopener');
                } catch { window.location.href = link; }
                return;
            }
            // No bundle and no legacy link (the editor no longer offers one):
            // fall back to matching a product by the slide's title, so a
            // simple one-product promo still adds it to the cart.
            const byTitle = PRODUCTS.find(p => p.name.toLowerCase() === (slide?.title || '').trim().toLowerCase());
            if (byTitle && byTitle.status !== 'sold_out') {
                add(byTitle.id);
                document.getElementById('cart-drawer').classList.remove('hidden');
            }
        }

        // ---- infinite scroll: render in batches, grow as the sentinel nears ----
        const GRID_BATCH = 12;
        let gridLimit = GRID_BATCH;
        let gridFilterKey = '';
        let gridObserver = null;

        function renderGrid() {
            const grid = document.getElementById('product-grid');
            const empty = document.getElementById('empty-state');
            const rows = visibleProducts();
            document.getElementById('category-title').textContent = active === 'All' ? 'Our Menu' : active;
            renderPromoBanner();

            // Changing category/tag/search starts the window over; cart
            // re-renders keep the scroll position the shopper already earned.
            const filterKey = active + '|' + tag + '|' + query.trim().toLowerCase();
            if (filterKey !== gridFilterKey) { gridLimit = GRID_BATCH; gridFilterKey = filterKey; }
            if (gridObserver) { gridObserver.disconnect(); gridObserver = null; }

            if (!rows.length) {
                grid.innerHTML = '';
                // On What's New the promo banner is the content, so don't show
                // "no treats found" when there simply aren't any new-status
                // products — matches Menu.jsx.
                empty.classList.toggle('hidden', active === "What's New" && PROMO_ACTIVE);
                return;
            }
            empty.classList.add('hidden');

            grid.innerHTML = rows.slice(0, gridLimit).map(p => {
                const soldOut = p.status === 'sold_out';
                const onSale = p.original_price != null && Number(p.original_price) > Number(p.price);
                return `
                <div data-view="${p.id}" role="button" tabindex="0" aria-label="View ${p.name}"
                    class="flex cursor-pointer flex-col overflow-hidden rounded-2xl bg-white shadow-sm outline-none transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-brand-500">
                    <div class="relative h-40 w-full overflow-hidden bg-slate-100 sm:h-48 animate-pulse">
                        <img data-img-fallback src="${p.image_path || FALLBACK_IMG}" alt="${p.name}" loading="lazy" class="h-full w-full object-cover ${soldOut ? 'opacity-60 grayscale' : ''}">
                        ${p.status ? `<span class="absolute left-2 top-2 rounded-full px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide ${soldOut ? 'bg-slate-700/90 text-white' : 'bg-white/90 text-brand-600'}">${STATUS_LABEL[p.status] || p.status}</span>` : ''}
                    </div>
                    <div class="flex flex-1 flex-col p-4">
                        <h3 class="text-sm font-semibold text-navy-800">${p.name}</h3>
                        ${bundleIncludesText(p)
                            ? `<p class="mt-1 line-clamp-2 text-xs font-medium text-brand-600">${bundleIncludesText(p)}</p>`
                            : `<p class="mt-1 line-clamp-2 text-xs text-slate-500">${p.description || ''}</p>`}
                        {{-- flex-wrap (not a fixed single row): on a narrow
                             2-column mobile card, price + the qty controls
                             (esp. the "In cart · N" pill) can be wider than
                             the card — qtyControls' own ml-auto keeps it
                             right-aligned whether it shares this line with
                             the price or wraps to its own. --}}
                        <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1.5 pt-3">
                            <span class="flex items-baseline gap-1.5">
                                <span class="text-lg font-bold text-brand-600">${peso(p.price)}</span>
                                ${onSale ? `<span class="text-xs text-slate-400 line-through">${peso(p.original_price)}</span>` : ''}
                            </span>
                            ${qtyControls(p)}
                        </div>
                    </div>
                </div>`;
            }).join('');

            // More matches than rendered → drop a sentinel at the end of the
            // grid; when it scrolls near the viewport, widen the window and
            // re-render. No pagination buttons, ever.
            if (rows.length > gridLimit) {
                const sentinel = document.createElement('div');
                sentinel.id = 'grid-sentinel';
                sentinel.style.gridColumn = '1 / -1';
                sentinel.className = 'flex items-center justify-center gap-2 py-6 text-xs font-medium text-slate-400';
                sentinel.innerHTML = '<span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-brand-500"></span> Loading more treats…';
                grid.appendChild(sentinel);
                gridObserver = new IntersectionObserver((entries) => {
                    if (entries.some(e => e.isIntersecting)) {
                        gridLimit += GRID_BATCH;
                        renderGrid();
                    }
                }, { rootMargin: '400px 0px' });
                gridObserver.observe(sentinel);
            }

            grid.querySelectorAll('[data-add]').forEach(btn => btn.addEventListener('click', () => add(btn.dataset.add)));
            // Clicking a card (not its Add/+ button) opens the detail modal.
            grid.querySelectorAll('[data-view]').forEach(el => {
                el.addEventListener('click', (e) => { if (e.target.closest('button')) return; openProductModal(el.dataset.view); });
                el.addEventListener('keydown', (e) => {
                    if ((e.key === 'Enter' || e.key === ' ') && !e.target.closest('button')) { e.preventDefault(); openProductModal(el.dataset.view); }
                });
            });
        }

        // ---- cart mutations ----
        // A bundle-type product travels in the cart as one normal line, just
        // like any other product — its linked products are display-only (see
        // cartLines()'s `includes` field), never added as separate lines, so
        // there's nothing bundle-specific to do here.
        function add(id) { cart[id] = (cart[id] || 0) + 1; writeCart(cart); renderAll(); }
        function dec(id) {
            if ((cart[id] || 0) <= 1) delete cart[id];
            else cart[id] -= 1;
            writeCart(cart); renderAll();
        }
        function remove(id) { confirmRemoveId = null; delete cart[id]; writeCart(cart); renderAll(); }

        // Cart line pending the small "Remove?" confirmation (✕ was clicked).
        let confirmRemoveId = null;

        // A promo bundle travels in the cart as one line under a synthetic key
        // ("bundle:<encoded title>:<id,id,…>") so it survives reloads without
        // extra storage. It renders as a single line named after the promo.
        // Price comes from the matching live slide's bundlePrice (the same
        // saved value OrderCreationService re-verifies at order time), falling
        // back to the components' regular total; the line vanishes if any
        // component stops being purchasable. `verified` marks that a live
        // slide still matches — only then does checkout submit it as a bundle.
        function bundleFromKey(id) {
            if (!id.startsWith('bundle:')) return null;
            const rest = id.slice(7);
            const sep = rest.indexOf(':');
            if (sep < 0) return null;
            const ids = rest.slice(sep + 1).split(',').filter(Boolean);
            const items = ids.map(pid => PRODUCTS.find(p => p.id === pid));
            if (!ids.length || items.some(p => !p || p.status === 'sold_out')) return null;
            const setKey = ids.slice().sort().join(',');
            const slide = PROMO_SLIDES.find(s => (s.products || []).slice().sort().join(',') === setKey);
            const regularTotal = items.reduce((s, p) => s + Number(p.price), 0);
            return {
                id,
                name: (slide && slide.title) || decodeURIComponent(rest.slice(0, sep)) || 'Promo bundle',
                price: slide && Number(slide.bundlePrice) > 0 ? Number(slide.bundlePrice) : regularTotal,
                image_path: (items.find(p => p.image_path) || {}).image_path || null,
                bundleItems: items,
                regularTotal,
                verified: !!slide,
            };
        }

        // A product-level bundle (Product.type === 'bundle') gets the same
        // display treatment as a promo bundle (badge + "includes" bullets +
        // struck-through regular total in renderCart()) — but it's a real
        // product row with its own id/price, so unlike a promo bundle it's
        // never decomposed at checkout (see the checkout-btn handler's
        // `p.bundleItems` check, which this deliberately doesn't set).
        function withBundleDisplay(product) {
            if (!product || product.type !== 'bundle' || !product.bundle_product_ids) return product;
            const entries = Object.entries(product.bundle_product_ids);
            if (!entries.length) return product;
            const includes = entries.map(([id]) => PRODUCTS.find(p => p.id === id)).filter(Boolean);
            const regularTotal = entries.reduce((s, [id, qty]) => {
                const p = PRODUCTS.find(x => x.id === id);
                return s + (p ? Number(p.price) * Number(qty) : 0);
            }, 0);
            return { ...product, includes, regularTotal };
        }

        function cartLines() {
            return Object.entries(cart)
                .map(([id, qty]) => ({
                    product: withBundleDisplay(id.startsWith('bundle:') ? bundleFromKey(id) : PRODUCTS.find(p => p.id === id)),
                    qty,
                }))
                .filter(l => l.product);
        }

        function renderCart() {
            const lines = cartLines();
            const itemCount = lines.reduce((s, l) => s + l.qty, 0);
            const subtotal = lines.reduce((s, l) => s + Number(l.product.price) * l.qty, 0);

            document.querySelectorAll('.cart-count').forEach(el => { el.textContent = itemCount; el.classList.toggle('hidden', itemCount === 0); });
            const badge = document.getElementById('cart-count-badge');
            if (badge) { badge.textContent = itemCount; badge.classList.toggle('hidden', itemCount === 0); badge.classList.toggle('flex', itemCount > 0); }
            document.querySelectorAll('.cart-count-icon').forEach(el => { el.textContent = itemCount; el.classList.toggle('hidden', itemCount === 0); el.classList.toggle('flex', itemCount > 0); });

            // No items → no fees: hide the voucher/totals/checkout footer
            // entirely instead of quoting a ₱${DELIVERY_FEE} total on nothing.
            document.querySelectorAll('.cart-footer').forEach(el => el.classList.toggle('hidden', !lines.length));

            document.querySelectorAll('.cart-list').forEach(list => {
                if (!lines.length) {
                    list.innerHTML = '<div class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center"><div class="text-5xl">🛒</div><p class="mt-3 text-sm text-slate-500">Your cart is empty.<br>Add some treats to get started!</p></div>';
                    return;
                }
                list.innerHTML = lines.map(({ product: p, qty }) => { const bundled = p.bundleItems || p.includes; return `
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100"><img data-img-fallback="remove" src="${p.image_path || FALLBACK_IMG}" alt="" class="h-full w-full object-cover"></span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-1.5 text-sm font-medium text-navy-800"><span class="min-w-0 break-words">${p.name}</span>${bundled ? '<span class="shrink-0 rounded-full bg-brand-50 px-1.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-wide text-brand-600">Bundle</span>' : ''}</p>
                            ${bundled ? `<ul class="mt-0.5 space-y-0.5 text-xs text-slate-400">${bundled.map(b => `<li class="break-words">• ${b.name}</li>`).join('')}</ul>` : ''}
                            <p class="text-xs text-slate-500">${peso(p.price)} each${p.regularTotal > p.price ? ` <span class="text-slate-400 line-through">${peso(p.regularTotal)}</span>` : ''}</p>
                        </div>
                        ${confirmRemoveId === p.id
                            ? `<div class="flex shrink-0 items-center gap-1.5 text-xs">
                                <span class="font-medium text-slate-500">Remove?</span>
                                <button type="button" data-confirm-remove="${p.id}" class="rounded-full bg-red-600 px-2.5 py-1 font-semibold text-white hover:bg-red-700">Yes</button>
                                <button type="button" data-cancel-remove class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-navy-700 hover:bg-slate-200">No</button>
                            </div>`
                            : `<div class="flex items-center gap-1.5">
                                <button type="button" data-dec="${p.id}" class="flex h-7 w-7 items-center justify-center rounded-full bg-navy-100 text-base font-bold text-navy-800 hover:bg-brand-500 hover:text-white">−</button>
                                <span class="w-4 text-center text-sm font-semibold">${qty}</span>
                                <button type="button" data-add="${p.id}" class="flex h-7 w-7 items-center justify-center rounded-full bg-navy-100 text-base font-bold text-navy-800 hover:bg-brand-500 hover:text-white">+</button>
                            </div>
                            <button type="button" data-remove="${p.id}" aria-label="Remove item" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600 [&_svg]:h-4 [&_svg]:w-4">${TRASH_SVG}</button>`}
                    </li>`; }).join('');
                list.querySelectorAll('[data-add]').forEach(btn => btn.addEventListener('click', () => add(btn.dataset.add)));
                // − at qty 1 would remove the item, so it arms the "Remove?"
                // prompt instead of deleting outright (same as ✕ below).
                list.querySelectorAll('[data-dec]').forEach(btn => btn.addEventListener('click', () => {
                    const id = btn.dataset.dec;
                    if ((cart[id] || 0) <= 1) { confirmRemoveId = id; renderCart(); return; }
                    dec(id);
                }));
                // ✕ only arms the small inline "Remove?" prompt — Yes deletes, No restores.
                list.querySelectorAll('[data-remove]').forEach(btn => btn.addEventListener('click', () => { confirmRemoveId = btn.dataset.remove; renderCart(); }));
                list.querySelectorAll('[data-confirm-remove]').forEach(btn => btn.addEventListener('click', () => remove(btn.dataset.confirmRemove)));
                list.querySelectorAll('[data-cancel-remove]').forEach(btn => btn.addEventListener('click', () => { confirmRemoveId = null; renderCart(); }));
            });

            const t = computeTotals({ subtotal, voucher });

            document.querySelectorAll('.cart-totals').forEach(el => {
                el.innerHTML = renderTotalsHTML(t, { showFreeDeliveryHint: !!voucher });
            });

            document.querySelectorAll('.voucher-box').forEach(box => {
                box.innerHTML = voucher
                    ? `<div class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm">
                         <span class="font-medium text-green-700">🎟️ ${voucher.code} — ${voucher.label || ''}</span>
                         <button type="button" data-remove-voucher class="text-xs font-semibold text-slate-400 hover:text-red-600">Remove</button>
                       </div>`
                    : `<div class="flex gap-2">
                         <input type="text" class="voucher-input w-full rounded-full border border-slate-300 px-4 py-2 text-sm uppercase text-navy-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" placeholder="Voucher code">
                         <button type="button" class="voucher-apply shrink-0 rounded-full bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Apply</button>
                       </div>
                       <p class="voucher-error mt-1.5 hidden text-xs text-red-600"></p>`;
                const removeBtn = box.querySelector('[data-remove-voucher]');
                if (removeBtn) removeBtn.addEventListener('click', () => { voucher = null; renderCart(); });
                const applyBtn = box.querySelector('.voucher-apply');
                if (applyBtn) applyBtn.addEventListener('click', () => {
                    const input = box.querySelector('.voucher-input');
                    const key = (input.value || '').trim().toUpperCase();
                    const err = box.querySelector('.voucher-error');
                    if (!key) return;
                    const def = voucherDefs[key];
                    if (!def) { err.textContent = 'Invalid voucher code'; err.classList.remove('hidden'); return; }
                    voucher = { code: key, ...def };
                    renderCart();
                });
            });

            document.querySelectorAll('.checkout-btn').forEach(btn => {
                btn.onclick = () => {
                    const summary = {
                        // A still-verified bundle is submitted as its product-id
                        // set so the server can re-verify it against the saved
                        // slide and charge the saved bundle price. A bundle
                        // whose slide no longer matches expands back into plain
                        // products at their regular prices.
                        items: lines.flatMap(({ product: p, qty }) => {
                            if (p.bundleItems && p.verified) {
                                return [{ bundle_products: p.bundleItems.map(b => b.id), name: p.name, qty, img: p.image_path, price: p.price }];
                            }
                            return (p.bundleItems || [p]).map(b => ({ product_id: b.id, name: b.name, qty, img: b.image_path, price: b.price }));
                        }),
                        voucher: voucher?.code || null,
                    };
                    try { localStorage.setItem('bw_checkout', JSON.stringify(summary)); } catch {}
                    // window.location navigation isn't instant — disable and
                    // relabel so the click reads as "working" instead of dead
                    // for the gap until the checkout page loads.
                    btn.disabled = true;
                    btn.classList.add('cursor-not-allowed', 'opacity-60');
                    btn.textContent = 'Loading…';
                    window.location.href = '{{ route("checkout") }}';
                };
            });
        }

        // ---- product detail modal (port of Menu.jsx's ProductMenuModal) ----
        // Shell + populate logic live in partials/product-modal.blade.php,
        // shared with landing.blade.php; only the price/qty/Add-to-cart
        // footer here is menu-specific, since it drives the real cart.
        let modalProductId = null;
        const pmModal = window.ProductModal.init({
            // Deliberately no backdrop-click close — only the ✕ button (and
            // Escape) dismisses, so a stray click can't lose the reader's place.
            closeOnBackdrop: false,
            onClose: () => { modalProductId = null; document.body.classList.remove('overflow-hidden'); },
        });

        function openProductModal(id) { modalProductId = id; renderProductModal(); }
        function closeProductModal() { pmModal.close(); }

        function renderProductModal() {
            const p = PRODUCTS.find(x => x.id === modalProductId);
            if (!p) { closeProductModal(); return; }
            const qty = cart[p.id] || 0;
            const soldOut = p.status === 'sold_out';
            const onSale = p.original_price != null && Number(p.original_price) > Number(p.price);
            document.body.classList.add('overflow-hidden');
            pmModal.open({
                img: p.image_path || FALLBACK_IMG,
                name: p.name,
                badge: p.status ? (STATUS_LABEL[p.status] || p.status) : '',
                desc: p.description || '',
                calories: p.calories,
                allergens: p.features || [],
                dim: soldOut,
                grayscale: soldOut,
            }, (footer) => {
                footer.innerHTML = `
                    ${bundleIncludesText(p) ? `<p class="mb-4 text-sm font-medium text-brand-600">${bundleIncludesText(p)}</p>` : ''}
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-brand-600">${peso(p.price)}</span>
                            ${onSale ? `<span class="text-sm text-slate-400 line-through">${peso(p.original_price)}</span>` : ''}
                        </span>
                        ${!soldOut && qty > 0 ? `<div class="flex shrink-0 items-center gap-3 rounded-full bg-slate-100 px-2 py-1.5">
                            <button type="button" data-dec="${p.id}" aria-label="Decrease quantity" class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-navy-800 shadow-sm transition hover:bg-slate-50">−</button>
                            <span class="w-5 text-center text-sm font-bold text-navy-800">${qty}</span>
                            <button type="button" data-add="${p.id}" aria-label="Increase quantity" class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-navy-800 shadow-sm transition hover:bg-slate-50">+</button>
                        </div>` : ''}
                    </div>
                    ${soldOut
                        ? '<span class="mt-5 flex items-center justify-center rounded-full bg-slate-100 px-6 py-3.5 text-sm font-semibold text-slate-400">Sold out</span>'
                        : qty === 0
                            ? `<button type="button" data-add="${p.id}" class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /><path d="M16 10v-4" /><path d="M14 8h4" /></svg> Add to cart</button>`
                            : ''}`;
                footer.querySelectorAll('[data-add]').forEach(btn => btn.addEventListener('click', () => add(btn.dataset.add)));
                footer.querySelectorAll('[data-dec]').forEach(btn => btn.addEventListener('click', () => dec(btn.dataset.dec)));
            });
        }

        function renderAll() {
            renderCategories();
            renderTagFilters();
            renderGrid();
            renderCart();
            // Keep an open detail modal in sync (its qty controls) after add/dec.
            if (modalProductId) renderProductModal();
        }

        document.getElementById('menu-search').addEventListener('input', (e) => { query = e.target.value; renderGrid(); });
        document.getElementById('open-cart').addEventListener('click', () => document.getElementById('cart-drawer').classList.remove('hidden'));
        document.getElementById('open-cart-fab').addEventListener('click', () => document.getElementById('cart-drawer').classList.remove('hidden'));

        // ---- account dropdown (mirrors Menu.jsx's MenuHeader: click to
        // toggle, outside-click or Escape closes) ----
        const menuBtn = document.getElementById('account-menu-btn');
        const menuWrap = document.getElementById('account-menu-wrap');
        if (menuBtn && menuWrap) {
            const menu = document.getElementById('account-menu');
            const chevron = document.getElementById('account-menu-chevron');
            const ordersBadge = document.getElementById('account-orders-badge');
            const closeMenu = () => { menu.classList.add('hidden'); chevron.classList.remove('rotate-180'); menuBtn.setAttribute('aria-expanded', 'false'); ordersBadge?.classList.remove('hidden'); };
            menuBtn.addEventListener('click', () => {
                const open = menu.classList.toggle('hidden') === false;
                chevron.classList.toggle('rotate-180', open);
                menuBtn.setAttribute('aria-expanded', String(open));
                // The open menu shows the count next to "My Orders" — drop the avatar dot meanwhile.
                ordersBadge?.classList.toggle('hidden', open);
            });
            document.addEventListener('mousedown', (e) => { if (!menuWrap.contains(e.target)) closeMenu(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
        }

        window.showLogoutConfirm = function () {
            const el = document.getElementById('logout-confirm-modal');
            if (el) { el.classList.remove('hidden'); el.classList.add('flex'); }
        };
        window.hideLogoutConfirm = function (e) {
            if (e && e.target !== e.currentTarget) return;
            const el = document.getElementById('logout-confirm-modal');
            if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
        };

        renderAll();
    })();
    </script>
</body>
</html>
