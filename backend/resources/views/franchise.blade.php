{{-- "Partner with us" franchise page — Blade port of the SPA's Franchise.jsx.
     Content comes from the CMS blob's `franchise` key (Site Editor → Franchise). --}}
@php
    $hero = $fr['hero'];
    // Highlight the brand name within the (free-text, admin-editable) hero
    // title — escape first, then wrap only that known literal substring in
    // trusted markup, same script-font/brand-orange accent already used for
    // single-word highlights elsewhere (e.g. stores.blade.php's "store").
    // Falls back to the plain escaped title untouched if the phrase isn't
    // in there verbatim (a custom title, a typo, etc.) rather than erroring.
    $heroTitleHtml = preg_replace(
        '/bw superbakeshop/i',
        '<span class="font-script font-normal text-brand-400">$0</span>',
        e($hero['title'] ?? ''),
    );
    // Site Editor per-section toggles (franchise.visible.*); absent = shown.
    $frVisible = (array) ($fr['visible'] ?? []);
    $showSection = fn (string $key): bool => (bool) ($frVisible[$key] ?? true);
    $email = $fr['email'] ?? 'franchise@bwsuperbakeshop.com';
    $inquireBody = "Name:\nContact number:\nPreferred location / city:\nPackage of interest:\nMessage:\n";
    $href = 'mailto:'.$email.'?subject=Franchise%20Inquiry&body='.rawurlencode($inquireBody);
    $metaTitle = 'BW Superbakeshop | Partner with us';
    $metaDescription = 'Own a bw Superbakeshop. Partner with a trusted bakeshop brand — training, supply chain, and marketing support included.';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo-meta')
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
                @php $franchiseHeroTypography = \App\Models\SiteContent::typographyStyle($hero['typography'] ?? []); @endphp
                <span data-editable="franchise.hero.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400" style="{{ $franchiseHeroTypography }}">{{ $hero['eyebrow'] ?? '' }}</span>
                <h1 data-editable="franchise.hero.title" class="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl" style="{{ $franchiseHeroTypography }}">{!! $heroTitleHtml !!}</h1>
                <p data-editable="franchise.hero.subtitle" data-editable-multiline class="mx-auto mt-5 max-w-xl text-base text-navy-50/80" style="{{ $franchiseHeroTypography }}">{{ $hero['subtitle'] ?? '' }}</p>
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

        {{-- stats — hidden unless an editor has added at least one real figure. --}}
        @if($showSection('stats') && count($fr['stats'] ?? []))
        <section id="franchise-stats" class="overflow-hidden border-b border-slate-100 bg-navy-50/40 py-12 sm:py-16">
            {{-- Always one row: a plain centered flex row when it fits, or a
                 seamless looping marquee when it doesn't — "fits" depends on
                 both viewport width AND how many stats an editor has added,
                 so this is measured in JS (scrollWidth vs container width)
                 rather than guessed with a fixed breakpoint. Re-measured on
                 resize/font-load so it stays correct if either changes. --}}
            <div data-stats-viewport class="mx-auto flex max-w-5xl justify-center overflow-hidden px-4 sm:px-6" style="mask-image: linear-gradient(to right, transparent, black 6%, black 94%, transparent); -webkit-mask-image: linear-gradient(to right, transparent, black 6%, black 94%, transparent);">
                <div data-stats-track class="flex w-max gap-10 sm:gap-16">
                    @foreach($fr['stats'] as $i => $s)
                        <div class="w-32 shrink-0 text-center sm:w-40">
                            <p data-editable="franchise.stats.{{ $i }}.value" class="text-2xl font-bold text-brand-600 sm:text-3xl lg:text-4xl">{{ $s['value'] ?? '' }}</p>
                            <p data-editable="franchise.stats.{{ $i }}.label" class="mt-2 text-balance text-xs font-medium text-slate-500 sm:text-sm">{{ $s['label'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <script>
            (function () {
                var viewport = document.querySelector('#franchise-stats [data-stats-viewport]');
                var track = document.querySelector('#franchise-stats [data-stats-track]');
                if (!viewport || !track) return;
                var originalHTML = track.innerHTML;
                var looping = false;

                function availableWidth() {
                    // clientWidth includes the viewport's own padding, which
                    // the track doesn't get to use — exclude it so the fit
                    // check matches the space actually available to it.
                    var cs = window.getComputedStyle(viewport);
                    return viewport.clientWidth - parseFloat(cs.paddingLeft || 0) - parseFloat(cs.paddingRight || 0);
                }

                function layout() {
                    // Reset to the single centered copy before measuring, so
                    // a viewport that's grown back past the fit threshold
                    // (resize, rotation) un-loops correctly instead of
                    // staying stuck in marquee mode.
                    if (looping) {
                        track.innerHTML = originalHTML;
                        track.classList.remove('animate-marquee');
                        viewport.classList.add('justify-center');
                        looping = false;
                    }
                    if (track.scrollWidth > availableWidth()) {
                        // Duplicate once so the -50% keyframe lands on an
                        // identical copy, making the loop seamless.
                        track.innerHTML = originalHTML + originalHTML;
                        viewport.classList.remove('justify-center');
                        track.classList.add('animate-marquee');
                        looping = true;
                    }
                }

                layout();
                window.addEventListener('resize', layout);
                window.addEventListener('load', layout);
            })();
            </script>
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
                @foreach($fr['perks'] ?? [] as $i => $p)
                    <div class="h-full rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:shadow-lg">
                        <span data-editable="franchise.perks.{{ $i }}.icon" class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-2xl">{{ $p['icon'] ?? '' }}</span>
                        <h3 data-editable="franchise.perks.{{ $i }}.title" class="mt-4 text-base font-semibold text-navy-800">{{ $p['title'] ?? '' }}</h3>
                        <p data-editable="franchise.perks.{{ $i }}.text" data-editable-multiline class="mt-2 text-sm text-slate-500">{{ $p['text'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- testimonials — hidden unless an editor has added at least one real quote. --}}
        @if($showSection('testimonials') && count($fr['testimonials'] ?? []))
        <section class="bg-navy-50/60 py-16">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Franchisee stories</span>
                    <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">Hear it from our partners</h2>
                </div>

                @php
                    // Small per-card rotation/offset/animation-delay so the
                    // grid reads as a loose scatter of notes instead of a
                    // rigid table — deterministic (indexed by position, not
                    // random) so it's stable across page loads and doesn't
                    // reshuffle on every save. These class strings need to
                    // appear literally somewhere for Tailwind's scanner to
                    // compile them (same reason as the $statsColsClass match
                    // in the stats section above) — the array here doubles
                    // as that literal listing.
                    $scatterRotate = ['-rotate-2', 'rotate-2', '-rotate-1', 'rotate-1', '-rotate-3', 'rotate-3'];
                    $scatterOffset = ['mt-0', 'mt-6', 'mt-3', 'mt-8', 'mt-2', 'mt-5'];
                    $floatDelays = ['0s', '0.6s', '1.2s', '1.8s', '2.4s', '3s'];
                @endphp
                {{-- items-start (not the grid default of stretch) is the fix
                     for mismatched quote lengths: without it, every card in a
                     row gets stretched to match the tallest one, leaving dead
                     space under the short ones. line-clamp-6 caps even the
                     longest quote at a sane height with an ellipsis — no
                     scrollbar, just a preview — since the real fix for "one
                     testimonial is a full paragraph" is capping it, not
                     scrolling it. Each card floats slowly and continuously
                     (its own animation-delay so they don't bob in sync); on
                     hover it stops, straightens out of its scattered tilt,
                     lifts, and its shadow/quote-mark/avatar animate — same
                     lift-on-hover language the perks/steps/packages cards
                     above already use. --}}
                <div class="mt-10 grid items-start gap-x-5 gap-y-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($fr['testimonials'] as $i => $t)
                        <div
                            class="group animate-card-float {{ $scatterRotate[$i % 6] }} {{ $scatterOffset[$i % 6] }} flex flex-col rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:rotate-0 hover:border-transparent hover:shadow-xl hover:shadow-brand-500/10 hover:[animation-play-state:paused] sm:p-8"
                            style="animation-delay: {{ $floatDelays[$i % 6] }}"
                        >
                            <span class="text-3xl leading-none text-brand-300 transition-colors duration-300 group-hover:text-brand-400" aria-hidden="true">&ldquo;</span>
                            <p data-editable="franchise.testimonials.{{ $i }}.quote" data-editable-multiline class="-mt-1 line-clamp-6 flex-1 text-sm italic text-slate-600 sm:text-base">{{ $t['quote'] ?? '' }}</p>
                            <div class="mt-6 flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-600 transition-transform duration-300 group-hover:scale-110" aria-hidden="true">
                                    {{ strtoupper(substr(trim((string) ($t['name'] ?? '')), 0, 1)) ?: '?' }}
                                </span>
                                <div class="min-w-0">
                                    <p data-editable="franchise.testimonials.{{ $i }}.name" class="truncate text-sm font-semibold text-navy-800">{{ $t['name'] ?? '' }}</p>
                                    <p data-editable="franchise.testimonials.{{ $i }}.role" class="truncate text-xs text-slate-500">{{ $t['role'] ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
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
                    @foreach($fr['steps'] ?? [] as $i => $s)
                        <div class="h-full rounded-2xl bg-white p-6 shadow-sm transition hover:shadow-md">
                            <span data-editable="franchise.steps.{{ $i }}.n" class="font-script text-3xl text-brand-500">{{ $s['n'] ?? '' }}</span>
                            <h3 data-editable="franchise.steps.{{ $i }}.title" class="mt-2 text-base font-semibold text-navy-800">{{ $s['title'] ?? '' }}</h3>
                            <p data-editable="franchise.steps.{{ $i }}.text" data-editable-multiline class="mt-2 text-sm text-slate-500">{{ $s['text'] ?? '' }}</p>
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
                @foreach($fr['packages'] ?? [] as $i => $pkg)
                    @php($featured = ! empty($pkg['featured']))
                    <div class="flex h-full flex-col rounded-2xl border p-6 shadow-sm transition hover:shadow-lg {{ $featured ? 'border-brand-400 ring-2 ring-brand-500/20' : 'border-slate-100 bg-white' }}">
                        @if($featured)
                            <span class="mb-3 w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600">Most popular</span>
                        @endif
                        <h3 data-editable="franchise.packages.{{ $i }}.name" class="text-lg font-bold text-navy-800">{{ $pkg['name'] ?? '' }}</h3>
                        <p data-editable="franchise.packages.{{ $i }}.price" class="mt-1 text-2xl font-bold text-brand-600">{{ $pkg['price'] ?? '' }}</p>
                        <p data-editable="franchise.packages.{{ $i }}.blurb" data-editable-multiline class="mt-2 text-sm text-slate-500">{{ $pkg['blurb'] ?? '' }}</p>
                        <ul data-editable="franchise.packages.{{ $i }}.features" data-editable-list class="mt-4 space-y-2 text-sm text-slate-600">
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

        {{-- faqs — hidden unless an editor has added at least one Q&A. --}}
        @if($showSection('faqs') && count($fr['faqs'] ?? []))
        <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Common questions</span>
                <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">Frequently asked questions</h2>
            </div>
            <div class="mt-10 space-y-3">
                @foreach($fr['faqs'] as $i => $f)
                    <details class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-navy-800">
                            <span data-editable="franchise.faqs.{{ $i }}.q">{{ $f['q'] ?? '' }}</span>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </summary>
                        <p data-editable="franchise.faqs.{{ $i }}.a" data-editable-multiline class="mt-3 text-sm text-slate-500">{{ $f['a'] ?? '' }}</p>
                    </details>
                @endforeach
            </div>
        </section>
        @endif

        {{-- CTA --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="rounded-3xl bg-gradient-to-r from-navy-800 to-navy-900 px-8 py-12 text-center text-white shadow-xl sm:px-12">
                <h2 class="text-2xl font-bold sm:text-3xl">Ready to start your own branch?</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-navy-50/80">Tell us about yourself and your location. Our franchising team will reply with the full kit and next steps.</p>
                <a href="{{ $href }}" class="mt-6 inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    Inquire about a franchise
                </a>
                <p class="mt-4 text-xs text-navy-50/70">
                    Or email <a href="mailto:{{ $email }}" data-editable="franchise.email" class="font-semibold text-brand-400 hover:text-brand-300">{{ $email }}</a>
                </p>
            </div>
        </section>

        {{-- footer --}}
        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
    @if($editable ?? false)
        @include('partials._editor-bridge')
    @endif
</body>
</html>
