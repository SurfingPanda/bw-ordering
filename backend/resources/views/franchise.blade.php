{{-- "Partner with us" franchise page — Blade port of the SPA's Franchise.jsx.
     Content comes from the CMS blob's `franchise` key (Site Editor → Franchise). --}}
@php
    $hero = $fr['hero'];
    // Site Editor per-section toggles (franchise.visible.*); absent = shown.
    $frVisible = (array) ($fr['visible'] ?? []);
    $showSection = fn (string $key): bool => (bool) ($frVisible[$key] ?? true);
    $email = $fr['email'] ?? 'franchise@bwsuperbakeshop.com';
    $inquireBody = "Name:\nContact number:\nPreferred location / city:\nPackage of interest:\nMessage:\n";
    $href = 'mailto:'.$email.'?subject=Franchise%20Inquiry&body='.rawurlencode($inquireBody);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BW Superbakeshop | Partner with us</title>
    <meta name="description" content="Own a bw Superbakeshop. Partner with a trusted bakeshop brand — training, supply chain, and marketing support included.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Pacifico&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
    <div class="min-h-screen bg-white text-navy-800">
        {{-- header --}}
        <header class="sticky top-0 z-50 border-b border-slate-100 bg-white">
            <div class="mx-auto flex h-20 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="/" class="flex min-w-0 items-center gap-2">
                    <img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24">
                </a>
                <div class="flex items-center gap-4">
                    <a href="/" class="text-sm font-medium text-navy-700 transition hover:text-brand-600">← Back to home</a>
                    <a href="{{ $href }}" class="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block">
                        Inquire now
                    </a>
                </div>
            </div>
        </header>

        {{-- hero --}}
        @if($showSection('hero'))
        <section class="relative overflow-hidden bg-navy-900">
            <img src="/images/bakery-interior.jpg" alt="" aria-hidden="true" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-navy-900/85 via-navy-900/80 to-navy-900/90"></div>
            <div class="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400">{{ $hero['eyebrow'] ?? '' }}</span>
                <h1 class="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl">{{ $hero['title'] ?? '' }}</h1>
                <p class="mx-auto mt-5 max-w-xl text-base text-navy-50/80">{{ $hero['subtitle'] ?? '' }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ $href }}" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        Inquire about a franchise
                    </a>
                    <!-- <a href="#packages" class="rounded-full border border-white/30 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        View packages
                    </a> -->
                </div>
            </div>
        </section>
        @endif

        {{-- perks --}}
        @if($showSection('perks'))
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Why franchise with us</span>
                <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">A partnership built to rise</h2>
                <p class="mt-3 text-sm text-slate-500">We give you the brand, the systems, and the support — you bring the passion for your community.</p>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($fr['perks'] ?? [] as $p)
                    <div class="h-full rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:shadow-lg">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-2xl">{{ $p['icon'] ?? '' }}</span>
                        <h3 class="mt-4 text-base font-semibold text-navy-800">{{ $p['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $p['text'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- steps --}}
        @if($showSection('steps'))
        <section class="bg-navy-50/60 py-16">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">How it works</span>
                    <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">From inquiry to grand opening</h2>
                    <p class="mt-3 text-sm text-slate-500">A clear, guided path to opening your own branch.</p>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($fr['steps'] ?? [] as $s)
                        <div class="h-full rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md">
                            <span class="font-script text-3xl text-brand-500">{{ $s['n'] ?? '' }}</span>
                            <h3 class="mt-2 text-base font-semibold text-navy-800">{{ $s['title'] ?? '' }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $s['text'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- packages --}}
        @if($fr['packagesEnabled'] ?? true)
        <section id="packages" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Franchise packages</span>
                <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">Pick the format that fits you</h2>
                <p class="mt-3 text-sm text-slate-500">Indicative investment ranges — final figures depend on size, location, and build-out.</p>
            </div>
            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach($fr['packages'] ?? [] as $pkg)
                    @php($featured = ! empty($pkg['featured']))
                    <div class="flex h-full flex-col rounded-2xl border p-6 shadow-sm transition hover:shadow-lg {{ $featured ? 'border-brand-400 ring-2 ring-brand-500/20' : 'border-slate-100 bg-white' }}">
                        @if($featured)
                            <span class="mb-3 w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600">Most popular</span>
                        @endif
                        <h3 class="text-lg font-bold text-navy-800">{{ $pkg['name'] ?? '' }}</h3>
                        <p class="mt-1 text-2xl font-bold text-brand-600">{{ $pkg['price'] ?? '' }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ $pkg['blurb'] ?? '' }}</p>
                        <ul class="mt-4 space-y-2 text-sm text-slate-600">
                            @foreach($pkg['features'] ?? [] as $f)
                                <li class="flex items-start gap-2"><span class="mt-0.5 text-brand-500">✓</span>{{ $f }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ $href }}" class="mt-6 block rounded-full px-6 py-3 text-center text-sm font-semibold transition {{ $featured ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md shadow-brand-500/30 hover:from-brand-600 hover:to-brand-600' : 'bg-navy-800 text-white hover:bg-brand-600' }}">
                            Inquire
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- CTA --}}
        <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <div class="rounded-3xl bg-gradient-to-r from-navy-800 to-navy-900 px-8 py-12 text-center text-white shadow-xl sm:px-12">
                <h2 class="text-2xl font-bold sm:text-3xl">Ready to start your own branch?</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-navy-50/80">Tell us about yourself and your location. Our franchising team will reply with the full kit and next steps.</p>
                <a href="{{ $href }}" class="mt-6 inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    Inquire about a franchise
                </a>
                <p class="mt-4 text-xs text-navy-50/70">
                    Or email <a href="mailto:{{ $email }}" class="font-semibold text-brand-400 hover:text-brand-300">{{ $email }}</a>
                </p>
            </div>
        </section>

        {{-- footer --}}
        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
</body>
</html>
