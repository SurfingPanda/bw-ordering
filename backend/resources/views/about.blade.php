{{-- "About Us" page — linked from the footer's Company column. Content comes
     from the CMS blob's `about` key (Site Editor → Other Pages → About Page),
     editable inline the same way franchise.blade.php is. --}}
@php
    $hero = $ab['hero'];
    $story = $ab['story'];
    $values = $ab['values'];
    $cta = $ab['cta'];
    // Highlight the brand name within the (free-text, admin-editable) hero
    // title — same escape-then-wrap-known-substring approach franchise.blade.php
    // uses, falling back to the plain escaped title untouched if the phrase
    // isn't in there verbatim.
    $heroTitleHtml = preg_replace(
        '/bw superbakeshop/i',
        '<span class="font-script font-normal text-brand-400">$0</span>',
        e($hero['title'] ?? ''),
    );
    // Site Editor per-section toggles (about.visible.*); absent = shown.
    $abVisible = (array) ($ab['visible'] ?? []);
    $showSection = fn (string $key): bool => (bool) ($abVisible[$key] ?? true);
    $metaTitle = 'BW Superbakeshop | About Us';
    $metaDescription = 'Freshly baked, made with love, ordered with ease — the story and values behind bw Superbakeshop.';
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
        @include('partials.site-nav')

        {{-- hero --}}
        @if($showSection('hero'))
        <section class="relative overflow-hidden bg-navy-900">
            <img src="/images/bakery-interior.jpg" alt="" aria-hidden="true" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-navy-900/85 via-navy-900/80 to-navy-900/90"></div>
            <div class="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
                <span data-editable="about.hero.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400">{{ $hero['eyebrow'] ?? '' }}</span>
                <h1 data-editable="about.hero.title" class="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl">{!! $heroTitleHtml !!}</h1>
                <p data-editable="about.hero.subtitle" data-editable-multiline class="mx-auto mt-5 max-w-xl text-base text-navy-50/80">{{ $hero['subtitle'] ?? '' }}</p>
            </div>
        </section>
        @endif

        {{-- our story --}}
        @if($showSection('story'))
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span data-editable="about.story.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">{{ $story['eyebrow'] ?? '' }}</span>
                    <h2 data-editable="about.story.heading" class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">{{ $story['heading'] ?? '' }}</h2>
                    <p data-editable="about.story.paragraph1" data-editable-multiline class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                        {{ $story['paragraph1'] ?? '' }}
                    </p>
                    <p data-editable="about.story.paragraph2" data-editable-multiline class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                        {{ $story['paragraph2'] ?? '' }}
                    </p>
                </div>
                <div class="relative">
                    <img data-editable="about.story.image" src="{{ $story['image'] ?? '/images/mascot-chef.png' }}" alt="bw Superbakeshop chef mascot" loading="lazy" decoding="async" width="1100" height="977" class="mx-auto w-full max-w-lg">
                </div>
            </div>
        </section>
        @endif

        {{-- values --}}
        @if($showSection('values'))
        <section class="bg-navy-50/60 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span data-editable="about.values.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">{{ $values['eyebrow'] ?? '' }}</span>
                    <h2 data-editable="about.values.heading" class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">{{ $values['heading'] ?? '' }}</h2>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($values['items'] ?? [] as $i => $v)
                        <div class="h-full rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:shadow-lg">
                            <span data-editable="about.values.items.{{ $i }}.icon" class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-2xl">{{ $v['icon'] ?? '' }}</span>
                            <h3 data-editable="about.values.items.{{ $i }}.title" class="mt-4 text-base font-semibold text-navy-800">{{ $v['title'] ?? '' }}</h3>
                            <p data-editable="about.values.items.{{ $i }}.text" data-editable-multiline class="mt-2 text-sm text-slate-500">{{ $v['text'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        {{-- CTA --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="rounded-3xl px-8 py-12 text-center text-white shadow-xl sm:px-12" style="background-color: {{ $cta['backgroundColor'] ?? '#083caa' }};">
                <h2 data-editable="about.cta.heading" class="text-2xl font-bold sm:text-3xl">{{ $cta['heading'] ?? '' }}</h2>
                <p data-editable="about.cta.subtitle" data-editable-multiline class="mx-auto mt-2 max-w-md text-sm text-navy-50/80">{{ $cta['subtitle'] ?? '' }}</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="/menu" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        Explore the menu
                    </a>
                    <a href="/stores" class="rounded-full border border-white/30 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        Find a store
                    </a>
                </div>
            </div>
        </section>

        {{-- footer --}}
        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
    @if($editable ?? false)
        @include('partials._editor-bridge')
    @endif
    @include('partials.assistant-widget')
</body>
</html>
