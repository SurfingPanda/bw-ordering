{{--
    Product card used by both the "What's New" and "Best Sellers" grids.
    Expects $product: ['name','img','tag','price'] required,
    ['calorie_info' (array of {amount,unit}),'allergens' (array),'desc',
    'net_weight','storage_condition','serving_note'] optional.
    Clicking anywhere on the card opens the shared #product-modal (see
    landing.blade.php), populated from this card's data-* attributes — the
    old React version rendered a fresh <ProductModal> per card instead, but a
    single shared modal avoids duplicating the markup for every product.

    The price-row "Add" button is re-rendered client-side by
    renderCardControls() in landing.blade.php (it swaps in an "In cart · N"
    state once the product is in the shared cart) — keep the markup here and
    that JS template string visually in sync.
--}}
@php
    $p = $product;
    // Default picture for products without an image; if it fails to load the
    // <img> onerror swaps in the "no image" tile (same rule as /menu).
    $fallbackImg = 'https://xhy0hjgguaqll6zn.public.blob.vercel-storage.com/custom-cake-refs/1781654816384-p23ferfqoq.png';
    // One product can carry several calorie entries now (e.g. "per piece"
    // and "per whole"). $calorieLines keeps them separate so the hover chip
    // can stack one entry per row; $calorieText joins them into one line for
    // the shared product modal (via data-calorie-text), which just renders
    // plain text with no client-side formatting needed.
    $calorieLines = collect($p['calorie_info'] ?? [])
        ->filter(fn ($e) => ($e['amount'] ?? null) !== null)
        ->map(fn ($e) => $e['amount'].' kcal per '.($e['unit'] ?: 'piece'));
    $calorieText = $calorieLines->implode(' · ');
    $tag = $p['tag'] ?? '';
    $tagIsBest = $tag !== '' && str_contains(strtolower($tag), 'best');
    // Favorite toggle is local-only (localStorage 'bw_favs'); key by product
    // id where available so it survives a name change, else fall back to name.
    $favKey = $p['id'] ?? $p['name'];
@endphp
<div
    class="product-card-trigger product-card group relative flex h-full cursor-pointer flex-col rounded-2xl border border-slate-200 bg-white shadow-sm outline-none transition duration-300 ease-out hover:-translate-y-1.5 hover:shadow-[0_16px_32px_rgba(0,0,0,0.1)] focus-visible:ring-2 focus-visible:ring-brand-500"
    role="button"
    tabindex="0"
    aria-label="View {{ $p['name'] }}"
    data-id="{{ $p['id'] ?? '' }}"
    data-name="{{ $p['name'] }}"
    data-img="{{ $p['img'] ?: $fallbackImg }}"
    data-tag="{{ $p['tag'] ?? '' }}"
    data-price="{{ $p['price'] }}"
    data-desc="{{ $p['desc'] ?? '' }}"
    data-calorie-text="{{ $calorieText }}"
    data-allergens="{{ json_encode($p['allergens'] ?? []) }}"
    data-net-weight="{{ $p['net_weight'] ?? '' }}"
    data-storage-condition="{{ $p['storage_condition'] ?? '' }}"
    data-serving-note="{{ $p['serving_note'] ?? '' }}"
>
    @if($calorieLines->isNotEmpty())
        {{-- Pops out above the card on hover/focus, bouncing in with the same
             back-out easing as .animate-pop-in elsewhere in the app. A
             sibling of the image wrapper below (not nested inside it) so
             neither of that wrapper's overflow-hidden layers can clip the
             popped-out chip; the card root deliberately has no overflow-hidden
             for the same reason. -translate-y-[calc(100%+gap)] pins it fully
             above regardless of how many calorie rows it stacks. The hovered
             card gets a z-index bump (see .product-card:hover in app.css) so
             this doesn't get covered by the next card's image. --}}
        <div class="pointer-events-none absolute left-1/2 top-0 z-20 flex w-max max-w-[85%] -translate-x-1/2 -translate-y-[calc(100%+0.5rem)] scale-75 items-center gap-2 rounded-2xl bg-gradient-to-br from-navy-800 to-navy-900 px-3.5 py-2 opacity-0 shadow-xl shadow-navy-900/40 ring-1 ring-white/10 transition duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:scale-100 group-hover:opacity-100 group-focus:scale-100 group-focus:opacity-100">
            <span aria-hidden="true" class="shrink-0 text-base leading-none">🔥</span>
            <span class="flex flex-col gap-0.5 text-left text-xs font-bold leading-snug text-white">
                @foreach($calorieLines as $line)
                    <span>{{ $line }}</span>
                @endforeach
            </span>
        </div>
    @endif

    {{-- Image area — the visual hero. Fixed 4:3 box + object-cover so the
         grid stays even however the source photos are shaped; own
         overflow-hidden layer to contain the hover zoom. Corners rounded to
         match the card top. --}}
    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-t-2xl bg-white">
        {{-- bg-white (not slate-100): several product photos are cutout PNGs
             with real transparent margins — on a gray backdrop that shows up
             as a visible seam around the subject even with object-cover;
             white blends into the card body below instead. --}}
        <img src="{{ $p['img'] ?: $fallbackImg }}" alt="{{ $p['name'] }}" loading="lazy" decoding="async" width="320" height="240"
            class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.05]"
            onerror="this.nextElementSibling.classList.replace('hidden', 'flex'); this.remove();">
        <span class="hidden h-full w-full items-center justify-center text-xs font-medium text-slate-400">no image</span>

        @if($tag !== '')
            <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-lg bg-white/90 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-brand-600 shadow-sm backdrop-blur-sm">
                @if($tagIsBest)
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2.5l2.9 5.87 6.48.94-4.69 4.57 1.11 6.45L12 17.77l-5.8 3.05 1.1-6.45-4.68-4.57 6.47-.94L12 2.5z" />
                    </svg>
                @endif
                {{ $tag }}
            </span>
        @endif

        {{-- Favorite toggle. Local visual state only (see the @once script
             below) — stops propagation so it never opens the product modal. --}}
        <button type="button" data-fav="{{ $favKey }}" aria-label="Save {{ $p['name'] }} to favorites" aria-pressed="false"
            onclick="event.stopPropagation()"
            class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-navy-700 shadow-sm ring-1 ring-black/5 backdrop-blur-sm transition duration-200 hover:scale-110 hover:text-brand-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
            </svg>
        </button>
    </div>

    {{-- Info — grows to fill the card so the price row bottom-aligns across
         cards regardless of name/description length. --}}
    <div class="flex flex-1 flex-col p-4">
        <h3 class="text-sm font-semibold leading-snug text-navy-800">{{ $p['name'] }}</h3>
        @if(!empty($p['desc']))
            <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-slate-500">{{ $p['desc'] }}</p>
        @endif
        <div class="mt-auto flex items-center justify-between gap-2 pt-4">
            <span class="text-lg font-bold text-brand-600">{{ $p['price'] }}</span>
            {{-- Adds straight to the shared bw_cart (see landing.blade.php's
                 mini-cart script) instead of navigating to /menu — the landing
                 grids used to fake an "add" by deep-linking there.
                 data-cart-control is a re-render target: renderCardControls()
                 swaps this button for an "In cart · N" pill once the product
                 is actually in the cart, matching /menu's qtyControls(). --}}
            <div data-cart-control="{{ $p['id'] ?? '' }}" onclick="event.stopPropagation()">
                <button type="button" data-add-to-cart="{{ $p['id'] ?? '' }}" aria-label="Add {{ $p['name'] }} to cart"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-navy-800 px-3 py-2 text-xs font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-brand-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    <span class="hidden sm:inline">Add</span>
                </button>
            </div>
        </div>
    </div>
</div>

@once
    {{-- Favorite toggle: local-only, no backend. Persists a list of product
         keys in localStorage['bw_favs'] and paints a filled heart for saved
         ones. Delegated so it also covers cards whose price-row markup
         renderCardControls() rewrites (that swap never touches [data-fav]). --}}
    <script>
        (function () {
            var KEY = 'bw_favs';
            function load() { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } }
            function save(list) { try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {} }
            function paint(btn, on) {
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                btn.classList.toggle('text-brand-600', on);
                btn.classList.toggle('text-navy-700', !on);
                var svg = btn.querySelector('svg');
                if (svg) svg.setAttribute('fill', on ? 'currentColor' : 'none');
            }
            function init() {
                var favs = load();
                document.querySelectorAll('[data-fav]').forEach(function (btn) {
                    paint(btn, favs.indexOf(btn.dataset.fav) !== -1);
                });
            }
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-fav]');
                if (!btn) return;
                e.stopPropagation();
                var id = btn.dataset.fav;
                var favs = load();
                var i = favs.indexOf(id);
                if (i === -1) { favs.push(id); } else { favs.splice(i, 1); }
                save(favs);
                paint(btn, i === -1);
            });
            if (document.readyState !== 'loading') init();
            else document.addEventListener('DOMContentLoaded', init);
        })();
    </script>
@endonce
