{{-- One branch ($i = index or repeater token, $store = row as array): a compact
     list row (name + region · address) with Edit/Remove; the full field set —
     matching the old AdminContent.jsx "Find a Store" card field-for-field —
     lives in a popup opened by Edit. The popup stays INSIDE the form so its
     inputs submit with "Save changes". Wiring lives in stores/index.blade.php. --}}
@php($store = (array) ($store ?? []))
<div data-row class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
    <input type="hidden" name="stores[{{ $i }}][id]" value="{{ $store['id'] ?? '' }}">

    <div class="flex items-center gap-3">
        <span data-row-number class="text-xs font-semibold uppercase tracking-wide text-slate-400">#</span>
        <div class="min-w-0 flex-1">
            <p data-store-name class="truncate text-sm font-semibold text-navy-800">{{ ($store['name'] ?? '') !== '' ? $store['name'] : 'New store' }}</p>
            <p data-store-meta class="truncate text-xs text-slate-500">{{ collect([$store['region'] ?? null, $store['address'] ?? null])->filter()->implode(' · ') }}</p>
        </div>
        <button type="button" data-edit class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">Edit</button>
        <button type="button" data-remove class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
    </div>

    <div data-modal class="hidden">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy-900/50"></div>
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-navy-800">Edit store</h3>
                    <button type="button" data-modal-close aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-navy-700">✕</button>
                </div>
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Branch name</span>
                        <input type="text" name="stores[{{ $i }}][name]" value="{{ $store['name'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Region</span>
                        @php($region = $store['region'] ?? 'Metro Manila')
                        <select name="stores[{{ $i }}][region]" class="{{ $input }} bg-white">
                            @foreach(['Metro Manila', 'Luzon', 'Visayas', 'Mindanao'] as $r)
                                <option value="{{ $r }}" @selected($region === $r)>{{ $r }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Accepts</span>
                        @php($fulfillment = $store['fulfillment'] ?? 'both')
                        <select name="stores[{{ $i }}][fulfillment]" class="{{ $input }} bg-white">
                            <option value="both" @selected($fulfillment === 'both')>Delivery & Pickup</option>
                            <option value="delivery" @selected($fulfillment === 'delivery')>Delivery only</option>
                            <option value="pickup" @selected($fulfillment === 'pickup')>Pickup only</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Address</span>
                        <textarea name="stores[{{ $i }}][address]" rows="3" class="{{ $input }}">{{ $store['address'] ?? '' }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Hours</span>
                        <input type="text" name="stores[{{ $i }}][hours]" value="{{ $store['hours'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Phone</span>
                        <input type="text" name="stores[{{ $i }}][phone]" value="{{ $store['phone'] ?? '' }}" class="{{ $input }}">
                    </label>
                    {{-- No name attribute → pasted embed code is never submitted;
                         JS extracts the !3d/!2d pair into the fields below. --}}
                    <label class="block">
                        <span class="mb-1 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                            Map location
                            <span tabindex="0" class="group relative inline-flex outline-none">
                                <span class="flex h-4 w-4 cursor-help items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-500 transition group-hover:bg-navy-800 group-hover:text-white">?</span>
                                <span class="pointer-events-none absolute bottom-full left-0 z-10 mb-2 hidden w-64 rounded-lg bg-navy-800 p-3 font-normal leading-relaxed text-white shadow-lg group-hover:block group-focus-within:block">
                                    How to get the embed code: open <b>Google Maps</b>, search for the branch, click <b>Share</b> → <b>Embed a map</b> → <b>Copy HTML</b>, then paste it here. The latitude & longitude fill in automatically.
                                </span>
                            </span>
                        </span>
                        <textarea data-map-embed rows="2" placeholder="Paste the Google Maps embed code or link here (Share → Embed a map) to fill the coordinates automatically" class="{{ $input }}"></textarea>
                        <span data-map-embed-status class="mt-1 hidden text-xs"></span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Latitude</span>
                            <input type="text" name="stores[{{ $i }}][latitude]" value="{{ $store['latitude'] ?? '' }}" class="{{ $input }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Longitude</span>
                            <input type="text" name="stores[{{ $i }}][longitude]" value="{{ $store['longitude'] ?? '' }}" class="{{ $input }}">
                        </label>
                    </div>
                </div>
                <button type="button" data-modal-close class="mt-5 w-full rounded-lg bg-navy-800 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-700">Done</button>
            </div>
        </div>
    </div>
</div>
