{{-- One About "values" card ($i = index or repeater token, $item = saved value). --}}
@php($item = (array) ($item ?? []))
<x-list-row heading="Edit value" empty-label="New value">
    <div class="grid gap-3 sm:grid-cols-[5rem_1fr]">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Icon</span>
            <input type="text" name="about[values][items][{{ $i }}][icon]" value="{{ $item['icon'] ?? '' }}" placeholder="🌾" data-summary-title class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
            <input type="text" name="about[values][items][{{ $i }}][title]" value="{{ $item['title'] ?? '' }}" data-summary-title class="{{ $input }}">
        </label>
    </div>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Text</span>
        <textarea name="about[values][items][{{ $i }}][text]" rows="2" data-summary-meta class="{{ $input }}">{{ $item['text'] ?? '' }}</textarea>
    </label>
</x-list-row>
