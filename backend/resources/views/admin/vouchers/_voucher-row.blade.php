{{-- One voucher card ($i = index or repeater token, $voucher = row as array),
     matching the old AdminContent.jsx vouchers section: the value field hides
     for free-delivery vouchers and its label follows the type (wired up by the
     script in admin/vouchers/index.blade.php). Expiry is a post-SPA addition
     kept here so existing expiry dates stay editable. --}}
@php($voucher = (array) ($voucher ?? []))
@php($type = $voucher['type'] ?? 'percent')
<x-item-card hide-move>
    <input type="hidden" name="vouchers[{{ $i }}][id]" value="{{ $voucher['id'] ?? '' }}">
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Code</span>
        <input type="text" name="vouchers[{{ $i }}][code]" value="{{ $voucher['code'] ?? '' }}" class="{{ $input }} uppercase">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Type</span>
        <select name="vouchers[{{ $i }}][type]" data-voucher-type class="{{ $input }} bg-white">
            <option value="percent" @selected($type === 'percent')>% off</option>
            <option value="amount" @selected($type === 'amount')>₱ off</option>
            <option value="freedel" @selected($type === 'freedel')>Free delivery</option>
        </select>
    </label>
    <label class="block {{ $type === 'freedel' ? 'hidden' : '' }}" data-voucher-value>
        <span data-value-label class="mb-1 block text-xs font-medium text-slate-500">{{ $type === 'amount' ? 'Amount off (₱)' : 'Percent off (%)' }}</span>
        <input type="number" step="0.01" min="0" name="vouchers[{{ $i }}][value]" value="{{ $voucher['value'] ?? '' }}" class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Label (shown to customer)</span>
        <input type="text" name="vouchers[{{ $i }}][label]" value="{{ $voucher['label'] ?? '' }}" class="{{ $input }}">
    </label>
    <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-500">Expires (optional)</span>
        <input type="date" name="vouchers[{{ $i }}][expires_at]" value="{{ $voucher['expires_at'] ?? '' }}" class="{{ $input }}">
    </label>
    <label class="flex items-center justify-between pt-1">
        <span class="text-xs font-medium text-slate-500">Active</span>
        <span class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
            <input type="checkbox" name="vouchers[{{ $i }}][active]" value="1" class="peer sr-only" @checked(! empty($voucher['active']))>
            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
        </span>
    </label>
</x-item-card>
