{{-- One footer link column ($i = index or __COL__ token, $item = saved column).
     Contains its own nested links repeater; the link token __LINK__ survives the
     column-level __COL__ replacement so "+ Add link" keeps working in cloned
     columns (link rows are remove-only, matching the old editor). --}}
@php($item = (array) ($item ?? []))
<x-item-card>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Column title</span>
        <input type="text" name="footer[columns][{{ $i }}][title]" value="{{ $item['title'] ?? '' }}" placeholder="Shop" class="{{ $input }}">
    </label>
    <div data-repeater data-token="__LINK__" class="space-y-3 rounded-lg border border-slate-200 bg-white p-3">
        <div data-rows class="space-y-3">
            @foreach(array_values((array) ($item['links'] ?? [])) as $j => $link)
                @include('admin.content._footer-link-row', ['i' => $i, 'j' => $j, 'item' => $link])
            @endforeach
        </div>
        <template>@include('admin.content._footer-link-row', ['i' => $i, 'j' => '__LINK__', 'item' => []])</template>
        <button type="button" data-add class="w-full rounded-lg border-2 border-dashed border-slate-300 py-2 text-xs font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">
            + Add link
        </button>
    </div>
</x-item-card>
