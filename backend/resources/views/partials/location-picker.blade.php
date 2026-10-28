<button type="button" data-location-open class="inline-flex min-w-0 items-center gap-1.5 rounded-full border px-3 py-2 text-xs font-semibold {{ ($locationLight ?? false) ? 'border-slate-300 text-navy-800' : 'border-white/40 text-white' }}" aria-haspopup="dialog">
    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
    <span data-location-label class="max-w-28 truncate">Choose location</span>
</button>
@once
    @vite('resources/js/location-picker.js')
    <dialog id="location-dialog" aria-labelledby="location-title" class="m-auto max-h-[85vh] w-[calc(100%_-_2rem)] max-w-lg overflow-y-auto rounded-2xl bg-white p-5 text-navy-800 shadow-xl backdrop:bg-black/40">
        <div class="flex items-center justify-between gap-3">
            <h2 id="location-title" class="text-lg font-bold">Choose your location</h2>
            <button type="button" data-location-close class="rounded-full border border-slate-300 px-3 py-1.5 text-sm">Close</button>
        </div>
        <p class="mt-2 text-sm text-slate-500">Find a branch in your area, then select it for your order.</p>
        <button type="button" id="location-detect" class="mt-4 rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-600 disabled:opacity-50">Use my location</button>
        <p class="mt-1 text-xs text-slate-500">Your browser will ask permission. You can also choose an area below.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="text-sm font-semibold">Region
                <select id="location-region" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-normal"><option value="">All regions</option></select>
            </label>
            <label class="text-sm font-semibold">City or area
                <input type="search" id="location-search" placeholder="Search city, area or branch" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-normal">
            </label>
        </div>
        <p id="location-status" class="my-3 text-sm text-slate-500" role="status"></p>
        <div id="location-results" class="grid gap-2"></div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
            <button type="button" id="location-clear" class="text-sm font-semibold text-slate-500 underline">Clear selected location</button>
            <a id="location-map" href="/stores" class="text-sm font-semibold text-brand-600">View store map</a>
        </div>
        <p class="mt-3 text-xs text-slate-500">Distance is approximate. Delivery availability is confirmed at checkout.</p>
    </dialog>
@endonce
