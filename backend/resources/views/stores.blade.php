{{-- "Find a Store" page — Blade port of the SPA's Stores.jsx. Store cards are
     server-rendered; resources/js/stores.js drives the MapLibre 3D map plus
     region/search filtering and selection. --}}
@php
    $dirHref = fn ($address) => 'https://www.google.com/maps/dir/?api=1&destination='.urlencode($address);
    $regions = ['All', 'Metro Manila', 'Luzon', 'Visayas', 'Mindanao'];
    $mapStores = $stores->map(fn ($s) => [
        'name' => $s->name,
        'region' => $s->region,
        'address' => $s->address,
        'hours' => $s->hours,
        'latitude' => $s->latitude,
        'longitude' => $s->longitude,
    ])->values();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BW Superbakeshop | Store Locator</title>
    <meta name="description" content="Find the bw Superbakeshop branch nearest you. Branches nationwide with directions, hours, and contact details.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Pacifico&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/stores.js'])
</head>
<body>
    {{-- JSON_HEX_TAG so a "</script>" inside store data can't break out. --}}
    <script id="stores-data" type="application/json">{!! json_encode($mapStores, JSON_HEX_TAG) !!}</script>

    <div class="min-h-screen bg-white text-navy-800">
        <header class="sticky top-0 z-50 border-b border-slate-100 bg-white">
            <div class="mx-auto flex h-20 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="/" class="flex min-w-0 items-center gap-2">
                    <img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24">
                </a>
                <div class="flex items-center gap-4">
                    <a href="/" class="text-sm font-medium text-navy-700 transition hover:text-brand-600">← Back to home</a>
                    <a href="{{ route('menu') }}" class="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block">
                        Order Now
                    </a>
                </div>
            </div>
        </header>

        {{-- hero + search --}}
        <section class="relative overflow-hidden bg-navy-900">
            <img src="/images/bakery-interior.jpg" alt="" aria-hidden="true" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-navy-900/85 via-navy-900/80 to-navy-900/90"></div>
            <div class="relative mx-auto max-w-3xl px-4 py-16 text-center sm:px-6">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
                    <svg class="h-7 w-7 text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 9l1.5-5h15L21 9" /><path d="M4 9v11h16V9" /><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" /><path d="M9 20v-5h6v5" />
                    </svg>
                </span>
                @php $storesPageTypography = \App\Models\SiteContent::typographyStyle($hero['typography'] ?? []); @endphp
                <h1 class="mt-5 text-4xl font-bold text-white sm:text-5xl" style="{{ $storesPageTypography }}">{{ $hero['title'] }}</h1>
                @if(!empty($hero['subtitle']))
                    <p class="mx-auto mt-4 max-w-md text-base text-navy-50/80" style="{{ $storesPageTypography }}">{{ $hero['subtitle'] }}</p>
                @endif
                <div class="relative mx-auto mt-7 max-w-md">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="search" id="store-search" placeholder="Search by city, area, or branch name"
                        class="w-full rounded-full border border-white/20 bg-white py-3.5 pl-11 pr-4 text-sm text-navy-800 outline-none transition focus:ring-2 focus:ring-brand-400/40">
                </div>
                <div class="mt-4 flex flex-col items-center gap-2">
                    <button type="button" id="find-nearest"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:opacity-60">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="7" /><circle cx="12" cy="12" r="2.5" /><line x1="12" y1="2" x2="12" y2="5" /><line x1="12" y1="19" x2="12" y2="22" /><line x1="2" y1="12" x2="5" y2="12" /><line x1="19" y1="12" x2="22" y2="12" />
                        </svg>
                        Find nearest store
                    </button>
                    <p id="nearest-status" class="hidden text-xs text-navy-50/80" role="status"></p>
                </div>
            </div>
        </section>

        {{-- listings --}}
        <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
            <div class="mb-8 flex flex-wrap justify-center gap-2">
                @foreach($regions as $r)
                    <button type="button" data-region="{{ $r }}"
                        class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $r === 'All' ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/30' : 'border border-slate-200 bg-white text-navy-700 hover:border-brand-200 hover:text-brand-600' }}">
                        {{ $r }}
                    </button>
                @endforeach
            </div>

            <p id="store-count" class="mb-5 text-center text-sm text-slate-500">
                {{ count($stores) }} {{ count($stores) === 1 ? 'store' : 'stores' }} found
            </p>

            <div id="stores-empty" class="hidden py-16 text-center">
                <div class="text-5xl">📍</div>
                <p class="mt-3 text-sm text-slate-500">No stores found. Try another area.</p>
            </div>

            <div id="stores-grid" class="grid gap-6 lg:grid-cols-2 {{ count($stores) === 0 ? 'hidden' : '' }}">
                {{-- map (top on mobile, right on desktop) --}}
                <div class="order-1 lg:order-2">
                    <div class="lg:sticky lg:top-20">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                            <div id="store-map" class="h-72 w-full bg-slate-100 lg:h-[26rem]"></div>
                        </div>
                        <div class="mt-3 flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <h3 id="selected-name" class="truncate text-sm font-bold text-navy-800">{{ $stores[0]->name ?? '' }}</h3>
                                <p id="selected-address" class="truncate text-xs text-slate-500">{{ $stores[0]->address ?? '' }}</p>
                            </div>
                            <a id="selected-directions" href="{{ isset($stores[0]) ? $dirHref($stores[0]->address) : '#' }}" target="_blank" rel="noreferrer"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" /><circle cx="12" cy="10" r="3" />
                                </svg>
                                Get directions
                            </a>
                        </div>
                    </div>
                </div>

                {{-- list (bottom on mobile, left on desktop) --}}
                <div class="order-2 space-y-3 lg:order-1">
                    @foreach($stores as $store)
                        @php($f = $store->fulfillment ?? 'both')
                        <button type="button" data-store-card="{{ $store->name }}"
                            class="w-full rounded-2xl border border-slate-100 bg-white p-5 text-left shadow-sm transition hover:shadow-md">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600">{{ $store->region }}</span>
                                <span class="w-fit rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold text-navy-700">
                                    {{ $f === 'delivery' ? '🚚 Delivery' : ($f === 'pickup' ? '🏪 Pickup' : '🚚 Delivery · 🏪 Pickup') }}
                                </span>
                            </div>
                            <h3 class="mt-3 text-base font-bold text-navy-800">{{ $store->name }}</h3>
                            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                <li class="flex gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                                    {{ $store->address }}
                                </li>
                                @if($store->hours)
                                    <li class="flex gap-2">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><polyline points="12 7 12 12 15 14" /></svg>
                                        {{ $store->hours }}
                                    </li>
                                @endif
                                @if($store->phone)
                                    <li class="flex gap-2">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z" /></svg>
                                        {{ $store->phone }}
                                    </li>
                                @endif
                            </ul>
                            <div class="mt-4 flex items-center gap-2">
                                <span data-show-on-map class="inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-4 py-2 text-sm font-semibold text-navy-700 transition">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6" /><line x1="8" y1="2" x2="8" y2="18" /><line x1="16" y1="6" x2="16" y2="22" /></svg>
                                    <span>Show on map</span>
                                </span>
                                <a href="{{ $dirHref($store->address) }}" target="_blank" rel="noreferrer"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                                    Directions
                                </a>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
</body>
</html>
