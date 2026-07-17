{{-- One menu-promo slide ($i = index or repeater token, $item = saved slide):
     a compact list row (title + badge · price summary); the full field set
     lives in the x-list-row edit popup, whose inputs stay inside the form so
     Save changes submits every slide, open or not. --}}
@php($item = (array) ($item ?? []))
<x-list-row hide-move heading="Edit slide" empty-label="New slide">
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Badge</span>
        <input type="text" name="menuPromo[slides][{{ $i }}][badge]" value="{{ $item['badge'] ?? '' }}" placeholder="✨ Just Launched" data-summary-meta class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
        <input type="text" name="menuPromo[slides][{{ $i }}][title]" value="{{ $item['title'] ?? '' }}" data-summary-title class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
        <textarea name="menuPromo[slides][{{ $i }}][description]" rows="3" class="{{ $input }}">{{ $item['description'] ?? '' }}</textarea>
    </label>
    @include('admin.content._image-field', ['name' => "menuPromo[slides][$i][image]", 'value' => $item['image'] ?? '', 'fieldLabel' => 'Image'])
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Price text</span>
            <input type="text" name="menuPromo[slides][{{ $i }}][price]" value="{{ $item['price'] ?? '' }}" placeholder="₱720.00" data-summary-meta class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Button label</span>
            <input type="text" name="menuPromo[slides][{{ $i }}][buttonLabel]" value="{{ $item['buttonLabel'] ?? '' }}" placeholder="Add to cart" class="{{ $input }}">
        </label>
    </div>
    {{-- Bundle: link catalogue products to this promo. When set, the slide's
         button adds all of them to the cart (and Button link is ignored). --}}
    <div data-bundle>
        <span class="mb-1 block text-xs font-medium text-slate-500">Bundle products</span>
        <div data-bundle-list class="mb-2 flex flex-wrap gap-1.5 empty:mb-0">
            @foreach((array) ($item['products'] ?? []) as $pid)
                @php($bp = ($bundleProducts ?? collect())->firstWhere('id', $pid))
                @if($bp)
                    <span data-bundle-chip class="inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-2.5 py-1 text-xs font-medium text-navy-700">
                        <input type="hidden" name="menuPromo[slides][{{ $i }}][products][]" value="{{ $bp->id }}">
                        {{ $bp->name }}
                        <button type="button" data-bundle-remove aria-label="Unlink {{ $bp->name }}" class="text-slate-400 transition hover:text-red-600">✕</button>
                    </span>
                @endif
            @endforeach
        </div>
        {{-- Searchable combobox: the option list is rendered by JS from the
             shared #bundle-products-data blob (see admin/content/index). --}}
        <div data-bundle-picker class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input type="text" data-bundle-search data-no-dirty autocomplete="off"
                data-name="menuPromo[slides][{{ $i }}][products][]"
                placeholder="Search products to link — name, code, or category…"
                class="{{ $input }} pl-9">
            <div data-bundle-menu class="absolute left-0 right-0 top-full z-20 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg"></div>
        </div>
        <label class="mt-2 block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Bundle price (₱) — the discounted price customers actually pay</span>
            <input type="number" step="0.01" min="0" name="menuPromo[slides][{{ $i }}][bundlePrice]" value="{{ $item['bundlePrice'] ?? '' }}"
                placeholder="Leave blank to charge the products' regular total" class="{{ $input }}">
        </label>
        <p class="mt-1 text-xs text-slate-400">
            The slide's button adds the linked products to the cart as one bundle, named after the Title. The bundle price above is what's charged at checkout (verified server-side); the Price text field is only the banner display, so keep them consistent.
        </p>
    </div>
</x-list-row>
