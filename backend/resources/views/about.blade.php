{{-- Static "About Us" page — linked from the footer's Company column.
     Content is fixed copy (not Site Editor content), same as
     legal/privacy-policy.blade.php and legal/terms-of-service.blade.php.
     $stats reuses the franchise page's real trust figures (Site Editor →
     Franchise → Trust Stats) so this page never invents its own numbers. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BW Superbakeshop | About Us</title>
    <meta name="description" content="Freshly baked, made with love, ordered with ease — the story and values behind bw Superbakeshop.">
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
                    <a href="/menu" class="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block">
                        Order now
                    </a>
                </div>
            </div>
        </header>

        {{-- hero --}}
        <section class="relative overflow-hidden bg-navy-900">
            <img src="/images/bakery-interior.jpg" alt="" aria-hidden="true" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-navy-900/85 via-navy-900/80 to-navy-900/90"></div>
            <div class="relative mx-auto max-w-3xl px-4 py-20 text-center sm:px-6">
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400">Our story</span>
                <h1 class="mt-5 text-4xl font-bold leading-tight text-white sm:text-5xl">About <span class="font-script font-normal text-brand-400">bw Superbakeshop</span></h1>
                <p class="mx-auto mt-5 max-w-xl text-base text-navy-50/80">Freshly baked. Made with love. Ordered with ease. The same promise we've kept in every branch, every day.</p>
            </div>
        </section>

        {{-- stats — reuses the real franchise trust figures; hidden if none are set yet. --}}
        @if(count($stats))
        <section class="border-b border-slate-100 bg-navy-50/40 py-10">
            <div class="mx-auto flex max-w-3xl flex-wrap justify-center gap-x-10 gap-y-6 px-4 sm:px-6">
                @foreach($stats as $s)
                    @if(!empty($s['value']))
                        <div class="text-center">
                            <p class="text-2xl font-bold text-brand-600 sm:text-3xl">{{ $s['value'] }}</p>
                            <p class="mt-1 text-xs font-medium text-slate-500 sm:text-sm">{{ $s['label'] ?? '' }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
        @endif

        {{-- our story --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">How it started</span>
                    <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">From one neighborhood oven to a name you trust</h2>
                    <p class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                        What started as a small bakeshop with a simple promise — proper ingredients, honest recipes, and warm service — has grown into bw Superbakeshop: a trusted bakery brand with branches nationwide. Through the years, the ovens have gotten bigger and the menu has grown, but what goes into every cake, loaf, and pastry hasn't changed.
                    </p>
                    <p class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                        Today, every branch still bakes the same way we started: fresh, every day, for the communities we're part of — whether that's a birthday cake picked up on the way home, a loaf grabbed for breakfast, or a custom celebration cake made to order.
                    </p>
                </div>
                <div class="relative">
                    <img src="/images/cake.png" alt="" loading="lazy" decoding="async" class="mx-auto w-full max-w-sm">
                </div>
            </div>
        </section>

        {{-- values --}}
        <section class="bg-navy-50/60 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">What we stand for</span>
                    <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">The values behind every bake</h2>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        ['icon' => '🌾', 'title' => 'Quality Ingredients', 'text' => 'We use trusted, quality ingredients in every recipe — a good bake starts long before it goes in the oven.'],
                        ['icon' => '❤️', 'title' => 'Made With Love', 'text' => 'Every cake and loaf is prepared with the same care you\'d expect from a home kitchen, just at bakery scale.'],
                        ['icon' => '🏘️', 'title' => 'Community First', 'text' => 'We\'re proud to be part of the neighborhoods we serve — from everyday treats to once-in-a-lifetime celebrations.'],
                        ['icon' => '📦', 'title' => 'Ordered With Ease', 'text' => 'Visit a branch, order for delivery, or plan a custom cake — we\'ve made it simple to get what you\'re craving.'],
                    ] as $v)
                        <div class="h-full rounded-2xl border border-slate-100 bg-white p-6 shadow-sm transition hover:shadow-lg">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-2xl">{{ $v['icon'] }}</span>
                            <h3 class="mt-4 text-base font-semibold text-navy-800">{{ $v['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ $v['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="rounded-3xl bg-gradient-to-r from-navy-800 to-navy-900 px-8 py-12 text-center text-white shadow-xl sm:px-12">
                <h2 class="text-2xl font-bold sm:text-3xl">Come taste the difference</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-navy-50/80">Explore the full menu or find the bw Superbakeshop nearest you.</p>
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
</body>
</html>
