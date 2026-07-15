{{-- One promo-banner slide ($i = index or repeater token, $item = saved slide). --}}
@php($item = (array) ($item ?? []))
<x-item-card>
    @include('admin.content._image-field', ['name' => "banners[$i][img]", 'value' => $item['img'] ?? '', 'fieldLabel' => 'Banner image', 'wide' => true])
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Alt text</span>
        <input type="text" name="banners[{{ $i }}][alt]" value="{{ $item['alt'] ?? '' }}" class="{{ $input }}">
    </label>
</x-item-card>
