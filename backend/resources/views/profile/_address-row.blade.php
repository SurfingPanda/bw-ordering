{{-- One saved delivery address ($i = index or repeater token, $addr = ['label', 'address']). --}}
<div data-address-row class="flex items-start gap-2 rounded-lg border border-slate-200 bg-slate-50/60 p-3">
    <div class="min-w-0 flex-1 space-y-2">
        <input type="text" name="addresses[{{ $i }}][label]" value="{{ $addr['label'] ?? '' }}" placeholder="Label (e.g. Home, Work, Partner)" maxlength="60"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
        <textarea name="addresses[{{ $i }}][address]" rows="2" maxlength="500" placeholder="House / unit no., street, barangay, city"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">{{ $addr['address'] ?? '' }}</textarea>
    </div>
    <button type="button" data-remove-address aria-label="Remove address"
        class="mt-1 shrink-0 rounded-lg px-2 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50">
        Remove
    </button>
</div>
