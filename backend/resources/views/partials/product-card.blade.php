{{--
    Product card used by both the "What's New" and "Best Sellers" grids.
    Expects $product: ['name','img','tag','price'] required,
    ['calorie_info' (array of {amount,unit}),'allergens' (array),'desc',
    'net_weight','storage_condition','serving_note'] optional.
    Clicking anywhere on the card opens the shared #product-modal (see
    landing.blade.php), populated from this card's data-* attributes — the
    old React version rendered a fresh <ProductModal> per card instead, but a
    single shared modal avoids duplicating the markup for every product.
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
@endphp
<div
    class="product-card-trigger product-card group relative cursor-pointer rounded-2xl bg-white shadow-sm outline-none transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-brand-500"
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
             popped-out chip; -translate-y-[calc(100%+gap)] pins it fully
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
    <div class="relative overflow-hidden rounded-t-2xl">
        {{-- bg-white (not slate-100): several product photos are cutout PNGs
             with real transparent margins — on a gray backdrop that shows up
             as a visible seam around the subject even with object-cover;
             white blends into the card body below instead. --}}
        <span class="relative block h-40 w-full overflow-hidden bg-white transition duration-300 group-hover:scale-105">
            <img src="{{ $p['img'] ?: $fallbackImg }}" alt="{{ $p['name'] }}" loading="lazy" decoding="async" width="320" height="160"
                class="h-full w-full object-cover"
                onerror="this.nextElementSibling.classList.replace('hidden', 'flex'); this.remove();">
            <span class="hidden h-full w-full items-center justify-center text-xs font-medium text-slate-400">no image</span>
        </span>
        @if(!empty($p['tag']))
            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide text-brand-600">
                {{ $p['tag'] }}
            </span>
        @endif
    </div>
    <div class="p-4">
        <h3 class="text-sm font-semibold text-navy-800">{{ $p['name'] }}</h3>
        <div class="mt-3 flex items-center justify-between">
            <span class="text-lg font-bold text-brand-600">{{ $p['price'] }}</span>
            {{-- Adds straight to the shared bw_cart (see landing.blade.php's
                 mini-cart script) instead of navigating to /menu — the landing
                 grids used to fake an "add" by deep-linking there.
                 data-cart-control is a re-render target: renderCardControls()
                 swaps this button for an "In cart · N" pill once the product
                 is actually in the cart, matching /menu's qtyControls(). --}}
            <div data-cart-control="{{ $p['id'] ?? '' }}" onclick="event.stopPropagation()">
                <button type="button" data-add-to-cart="{{ $p['id'] ?? '' }}" aria-label="Add {{ $p['name'] }} to cart"
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-navy-800 text-white transition hover:bg-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
