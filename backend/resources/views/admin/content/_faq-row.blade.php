{{-- One franchise FAQ ($i = index or repeater token, $item = saved FAQ). --}}
@php($item = (array) ($item ?? []))
<x-list-row heading="Edit FAQ" empty-label="New question">
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Question</span>
        <input type="text" name="franchise[faqs][{{ $i }}][q]" value="{{ $item['q'] ?? '' }}" data-summary-title class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Answer</span>
        <textarea name="franchise[faqs][{{ $i }}][a]" rows="3" data-summary-meta class="{{ $input }}">{{ $item['a'] ?? '' }}</textarea>
    </label>
</x-list-row>
