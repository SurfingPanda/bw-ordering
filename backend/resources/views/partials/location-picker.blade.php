@once
    @vite('resources/js/location-picker.js')
    <dialog id="location-dialog" data-required="{{ ($locationRequired ?? false) ? 'true' : 'false' }}" data-auto-open="{{ ($locationAutoOpen ?? false) ? 'true' : 'false' }}" aria-labelledby="location-title" aria-describedby="location-help" class="m-auto max-h-[90vh] w-[calc(100%_-_2rem)] max-w-md overflow-y-auto rounded-3xl border border-white/80 bg-white p-6 text-navy-800 shadow-2xl backdrop:bg-navy-950/50 backdrop:backdrop-blur-sm sm:p-8">
        <div id="location-heading" class="text-center">
            <div id="location-illustration" class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-brand-50 ring-8 ring-brand-50/50">
                <svg class="h-10 w-10 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            </div>
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Find your nearest branch</p>
            <h2 id="location-title" tabindex="-1" class="mt-2 text-2xl font-bold tracking-tight focus:outline-none">Fresh bakes, closer to you</h2>
            <p id="location-help" class="mt-3 text-sm leading-relaxed text-slate-500">Enable location to discover branches near you and get started with your order.</p>
        </div>
        <div id="location-instructions" class="mt-6 space-y-3 rounded-2xl bg-slate-50 p-4 text-sm">
            <p class="flex items-center gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-brand-600">1</span>Turn on your device location.</p>
            <p class="flex items-center gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-brand-600">2</span>Tap Allow when your browser asks.</p>
        </div>
        <button type="button" id="location-detect" class="mt-5 w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-3.5 text-sm font-bold text-white shadow-md shadow-brand-500/20 transition hover:brightness-105 focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-200 disabled:cursor-wait disabled:opacity-60">Enable location</button>
        <p id="location-privacy" class="mt-4 text-center text-xs leading-relaxed text-slate-400">Your location is shared with <a href="https://www.bigdatacloud.com/privacy-policy" target="_blank" rel="noopener noreferrer" class="underline">BigDataCloud</a> to find your area. This site saves only your selected branch.</p>
        <p id="location-status" class="mt-4 text-sm leading-relaxed text-slate-500 empty:hidden" role="status" aria-live="polite"></p>
        <div id="location-branches" hidden>
        <div class="mt-4 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs text-slate-500">Your approximate location</p>
                <p id="location-current-name" class="mt-1 text-sm font-semibold text-navy-800" role="status" aria-live="polite">Location not detected yet</p>
            </div>
            <button type="button" id="location-relocate" class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-600 transition hover:bg-brand-100 focus-visible:outline-brand-500 disabled:opacity-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                <span>Locate me</span>
            </button>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <label class="text-sm font-semibold">Region
                <select id="location-region" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-normal"><option value="">All regions</option></select>
            </label>
            <label class="text-sm font-semibold">City or area
                <input type="search" id="location-search" placeholder="Search city, area or branch" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 font-normal">
            </label>
        </div>
        <div id="location-results" class="mt-4 grid max-h-[32dvh] gap-2 overflow-y-auto overscroll-contain pr-1" aria-label="Nearby branches"></div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
            <button type="button" id="location-clear" class="text-sm font-semibold text-slate-500 underline">Clear selected location</button>
            <a id="location-map" href="/stores" class="text-sm font-semibold text-brand-600">View store map</a>
        </div>
        <p class="mt-3 text-xs text-slate-500">Distance is approximate. Delivery availability is confirmed at checkout.</p>
        </div>
        <div id="location-footer" class="mt-5 border-t border-slate-100 pt-4">
            <button type="button" data-location-close class="w-full rounded-full bg-navy-800 px-5 py-3 text-sm font-bold text-white transition hover:bg-navy-700 focus-visible:outline-brand-500">Close</button>
        </div>
    </dialog>
@endonce
