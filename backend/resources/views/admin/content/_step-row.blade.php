{{-- One franchise "how it works" step ($i = index or repeater token, $item = saved step). --}}
@php($item = (array) ($item ?? []))
<x-list-row heading="Edit step" empty-label="New step">
    <div class="grid gap-3 sm:grid-cols-[5rem_1fr]">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Number</span>
            <input type="text" name="franchise[steps][{{ $i }}][n]" value="{{ $item['n'] ?? '' }}" placeholder="01" data-summary-title class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
            <input type="text" name="franchise[steps][{{ $i }}][title]" value="{{ $item['title'] ?? '' }}" data-summary-title class="{{ $input }}">
        </label>
    </div>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Text</span>
        <textarea name="franchise[steps][{{ $i }}][text]" rows="2" data-summary-meta class="{{ $input }}">{{ $item['text'] ?? '' }}</textarea>
    </label>
</x-list-row>
