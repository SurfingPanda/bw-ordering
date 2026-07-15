{{-- One footer link ($i = column index/token, $j = link index/token, $item = saved link). --}}
@php($item = (array) ($item ?? []))
<div data-row class="flex items-start gap-2">
    <div class="grid flex-1 gap-2 sm:grid-cols-2">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Label</span>
            <input type="text" name="footer[columns][{{ $i }}][links][{{ $j }}][label]" value="{{ $item['label'] ?? '' }}" class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">URL</span>
            <input type="text" name="footer[columns][{{ $i }}][links][{{ $j }}][url]" value="{{ $item['url'] ?? '' }}" placeholder="/menu or https://…" class="{{ $input }}">
        </label>
    </div>
    <button type="button" data-remove class="mt-6 rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
</div>
