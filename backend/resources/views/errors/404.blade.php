{{-- Branded 404 — replaces Laravel's plain default error page with the same
     bakery visual language as partials/maintenance-screen.blade.php (dark
     navy background, floating treats, animated logo), so a dead/mistyped
     link still feels like part of the site instead of a bare framework page. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found | BW Superbakeshop</title>
    <meta name="robots" content="noindex">
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
    <div class="animate-bg-pan relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-navy-900 px-6 text-center text-white">
        {{-- drifting glow blobs behind the content --}}
        <div aria-hidden="true" class="animate-drift pointer-events-none absolute -left-24 top-8 h-72 w-72 rounded-full bg-brand-500/20 blur-3xl"></div>
        <div aria-hidden="true" class="animate-drift-slow pointer-events-none absolute -right-20 bottom-8 h-80 w-80 rounded-full bg-brand-400/15 blur-3xl"></div>

        {{-- floating bakery treats --}}
        @foreach([
            ['emoji' => '🥐', 'cls' => 'left-[8%] top-[18%] text-5xl', 'delay' => '0s', 'dur' => '6s'],
            ['emoji' => '🧁', 'cls' => 'right-[10%] top-[22%] text-4xl', 'delay' => '1.2s', 'dur' => '7s'],
            ['emoji' => '🍞', 'cls' => 'left-[14%] bottom-[16%] text-5xl', 'delay' => '0.6s', 'dur' => '6.5s'],
            ['emoji' => '🎂', 'cls' => 'right-[14%] bottom-[20%] text-4xl', 'delay' => '2s', 'dur' => '8s'],
            ['emoji' => '🍩', 'cls' => 'left-[44%] top-[9%] text-3xl', 'delay' => '1.6s', 'dur' => '7.5s'],
            ['emoji' => '🥖', 'cls' => 'right-[6%] top-[55%] text-4xl', 'delay' => '0.3s', 'dur' => '6.8s'],
        ] as $t)
            <span aria-hidden="true" style="animation-delay: {{ $t['delay'] }}; animation-duration: {{ $t['dur'] }}"
                class="animate-bakery-float pointer-events-none absolute select-none opacity-20 {{ $t['cls'] }}">{{ $t['emoji'] }}</span>
        @endforeach

        <div class="relative z-10 flex flex-col items-center">
            <div class="animate-pop-in">
                <div class="animate-float relative">
                    <img src="/images/smoke.webp" alt="" aria-hidden="true" width="260" height="390"
                        class="pointer-events-none absolute bottom-[50%] left-1/2 w-44 -translate-x-1/2 select-none sm:w-52">
                    <img src="/images/logo (1).png" alt="bw Superbakeshop" width="225" height="225" class="animate-bake relative h-44 w-auto sm:h-56">
                </div>
            </div>
            <span class="animate-wiggle mt-10 inline-block text-6xl" role="img" aria-label="Crumbs">🍪</span>
            <p class="mt-6 font-brand text-sm font-bold uppercase tracking-[0.3em] text-brand-400">404</p>
            <h1 class="mt-2 font-brand text-4xl font-bold sm:text-5xl">Oops, this page got eaten.</h1>
            <p class="mt-4 max-w-md text-base leading-relaxed text-navy-50/70">
                The page you're looking for doesn't exist or may have moved. Let's get you back to something delicious.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="/" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    Back to home
                </a>
                <a href="/menu" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-7 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                    Browse the menu
                </a>
            </div>
        </div>
    </div>
</body>
</html>
