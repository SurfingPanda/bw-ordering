{{-- One "What's New" product card ($i = index or repeater token, $item = saved
     card). The card's "+" on the landing page adds to cart by name, so the name
     should match a real menu product. --}}
@php($item = (array) ($item ?? []))
<x-item-card>
    @include('admin.content._image-field', ['name' => "whatsNewProducts[$i][img]", 'value' => $item['img'] ?? '', 'fieldLabel' => 'Image'])
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Name</span>
        <input type="text" name="whatsNewProducts[{{ $i }}][name]" value="{{ $item['name'] ?? '' }}" class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
        <textarea name="whatsNewProducts[{{ $i }}][desc]" rows="3" class="{{ $input }}">{{ $item['desc'] ?? '' }}</textarea>
    </label>
    <div class="grid grid-cols-2 gap-2">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Price</span>
            <input type="text" name="whatsNewProducts[{{ $i }}][price]" value="{{ $item['price'] ?? '' }}" placeholder="₱720" class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Tag</span>
            <input type="text" name="whatsNewProducts[{{ $i }}][tag]" value="{{ $item['tag'] ?? '' }}" placeholder="New" class="{{ $input }}">
        </label>
    </div>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Calories (optional)</span>
        <input type="number" min="0" name="whatsNewProducts[{{ $i }}][calories]" value="{{ $item['calories'] ?? '' }}" class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Allergens (one per line)</span>
        <textarea name="whatsNewProducts[{{ $i }}][allergens]" rows="3" class="{{ $input }}">{{ implode("\n", (array) ($item['allergens'] ?? [])) }}</textarea>
    </label>
</x-item-card>
