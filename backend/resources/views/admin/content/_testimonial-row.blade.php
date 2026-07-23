{{-- One franchisee testimonial ($i = index or repeater token, $item = saved testimonial). --}}
@php($item = (array) ($item ?? []))
<x-list-row heading="Edit testimonial" empty-label="New testimonial">
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Quote</span>
        <textarea name="franchise[testimonials][{{ $i }}][quote]" rows="3" data-summary-meta class="{{ $input }}">{{ $item['quote'] ?? '' }}</textarea>
    </label>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Name</span>
            <input type="text" name="franchise[testimonials][{{ $i }}][name]" value="{{ $item['name'] ?? '' }}" data-summary-title class="{{ $input }}">
        </label>
        <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-500">Role / location</span>
            <input type="text" name="franchise[testimonials][{{ $i }}][role]" value="{{ $item['role'] ?? '' }}" placeholder="Franchise Owner, Quezon City" class="{{ $input }}">
        </label>
    </div>
</x-list-row>
