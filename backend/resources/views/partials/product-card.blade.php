{{--
    Product card used by both the "What's New" and "Best Sellers" grids.
    Expects $product: ['name','img','tag','price'] required,
    ['calories','allergens' (array),'desc'] optional.
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
@endphp
<div
    class="product-card-trigger group cursor-pointer overflow-hidden rounded-2xl bg-white shadow-sm outline-none transition hover:shadow-xl focus-visible:ring-2 focus-visible:ring-brand-500"
    role="button"
    tabindex="0"
    aria-label="View {{ $p['name'] }}"
    data-id="{{ $p['id'] ?? '' }}"
    data-name="{{ $p['name'] }}"
    data-img="{{ $p['img'] ?: $fallbackImg }}"
    data-tag="{{ $p['tag'] ?? '' }}"
    data-price="{{ $p['price'] }}"
    data-desc="{{ $p['desc'] ?? '' }}"
    data-calories="{{ $p['calories'] ?? '' }}"
    data-allergens="{{ implode(',', $p['allergens'] ?? []) }}"
>
    <div class="relative overflow-hidden">
        <span class="relative block h-40 w-full overflow-hidden bg-slate-100 transition duration-300 group-hover:scale-105">
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
        @if(($p['calories'] ?? null) !== null || !empty($p['allergens']))
            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                @if(($p['calories'] ?? null) !== null)
                    <span class="inline-flex items-center rounded-full bg-navy-50 px-2 py-0.5 text-[10px] font-semibold text-navy-700">
                        {{ $p['calories'] }} cal
                    </span>
                @endif
                @foreach($p['allergens'] ?? [] as $a)
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700" title="Contains {{ $a }}">
                        {{ $a }}
                    </span>
                @endforeach
            </div>
        @endif
        <div class="mt-3 flex items-center justify-between">
            <span class="text-lg font-bold text-brand-600">{{ $p['price'] }}</span>
            {{-- Adds straight to the shared bw_cart (see landing.blade.php's
                 mini-cart script) instead of navigating to /menu — the landing
                 grids used to fake an "add" by deep-linking there. --}}
            <button type="button" data-add-to-cart="{{ $p['id'] ?? '' }}" onclick="event.stopPropagation()" aria-label="Add {{ $p['name'] }} to cart"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-navy-800 text-white transition hover:bg-brand-600">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
            </button>
        </div>
    </div>
</div>
