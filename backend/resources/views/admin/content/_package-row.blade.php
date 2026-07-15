{{-- One franchise package ($i = index or repeater token, $item = saved package). --}}
@php($item = (array) ($item ?? []))
<x-list-row heading="Edit package" empty-label="New package">
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Name</span>
            <input type="text" name="franchise[packages][{{ $i }}][name]" value="{{ $item['name'] ?? '' }}" placeholder="Kiosk" data-summary-title class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Price text</span>
            <input type="text" name="franchise[packages][{{ $i }}][price]" value="{{ $item['price'] ?? '' }}" placeholder="₱1.2M – 1.8M" data-summary-meta class="{{ $input }}">
        </label>
    </div>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Blurb</span>
        <textarea name="franchise[packages][{{ $i }}][blurb]" rows="2" data-summary-meta class="{{ $input }}">{{ $item['blurb'] ?? '' }}</textarea>
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Features (one per line)</span>
        <textarea name="franchise[packages][{{ $i }}][features]" rows="3" class="{{ $input }}">{{ implode("\n", (array) ($item['features'] ?? [])) }}</textarea>
    </label>
    <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="franchise[packages][{{ $i }}][featured]" value="1" @checked(! empty($item['featured'])) class="h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
        Featured (highlighted card)
    </label>
</x-list-row>
