{{-- One menu-promo slide ($i = index or repeater token, $item = saved slide). --}}
@php($item = (array) ($item ?? []))
<x-item-card>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Badge</span>
        <input type="text" name="menuPromo[slides][{{ $i }}][badge]" value="{{ $item['badge'] ?? '' }}" placeholder="✨ Just Launched" class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
        <input type="text" name="menuPromo[slides][{{ $i }}][title]" value="{{ $item['title'] ?? '' }}" class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
        <textarea name="menuPromo[slides][{{ $i }}][description]" rows="3" class="{{ $input }}">{{ $item['description'] ?? '' }}</textarea>
    </label>
    @include('admin.content._image-field', ['name' => "menuPromo[slides][$i][image]", 'value' => $item['image'] ?? '', 'fieldLabel' => 'Image', 'wide' => true])
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Price text</span>
            <input type="text" name="menuPromo[slides][{{ $i }}][price]" value="{{ $item['price'] ?? '' }}" placeholder="₱720.00" class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Button label</span>
            <input type="text" name="menuPromo[slides][{{ $i }}][buttonLabel]" value="{{ $item['buttonLabel'] ?? '' }}" placeholder="Add to cart" class="{{ $input }}">
        </label>
    </div>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Button link</span>
        <input type="text" name="menuPromo[slides][{{ $i }}][buttonLink]" value="{{ $item['buttonLink'] ?? '' }}" class="{{ $input }}">
    </label>
    <p class="text-xs text-slate-400">
        Tip: use <code>/menu?add=Product Name</code> to add that product to the cart, an internal path like <code>/franchise</code>, or a full <code>https://</code> URL.
    </p>
</x-item-card>
