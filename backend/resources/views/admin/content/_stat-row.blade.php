{{-- One franchise trust stat ($i = index or repeater token, $item = saved stat). --}}
@php($item = (array) ($item ?? []))
<x-list-row heading="Edit stat" empty-label="New stat">
    <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Value</span>
            <input type="text" name="franchise[stats][{{ $i }}][value]" value="{{ $item['value'] ?? '' }}" placeholder="60+" data-summary-title class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Label</span>
            <input type="text" name="franchise[stats][{{ $i }}][label]" value="{{ $item['label'] ?? '' }}" placeholder="Branches nationwide" data-summary-meta class="{{ $input }}">
        </label>
    </div>
</x-list-row>
