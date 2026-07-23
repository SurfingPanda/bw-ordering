{{-- Full-bleed "under construction" screen — shown by LandingController on
     "/" and by CheckMaintenanceMode middleware on every other public route
     while maintenance mode is on. Vars: $m (content.maintenance, defaults
     already merged in by the caller), $social (content.social). --}}
@php
    $isExternal = fn (?string $href) => (bool) preg_match('#^https?://#i', (string) $href);
    $socialMeta = [
        ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'f'],
        ['key' => 'tiktok', 'label' => 'TikTok', 'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4"><path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07Z"/></svg>'],
        ['key' => 'x', 'label' => 'X (Twitter)', 'icon' => '𝕏'],
    ];
    $activeSocials = array_values(array_filter(array_map(function ($s) use ($social) {
        $s['href'] = trim($social[$s['key']] ?? '');

        return $s;
    }, $socialMeta), fn ($s) => $s['href'] !== ''));
@endphp
{{-- No flash-of-real-content risk here since (unlike the old client-rendered
     SPA) the server already knows the CMS state before sending any HTML. --}}
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
                {{-- steam rising off the logo as it bakes (transparent animated
                     webp — the gif's black backdrop was keyed out into real alpha) --}}
                <img src="/images/smoke.webp" alt="" aria-hidden="true" width="260" height="390"
                    class="pointer-events-none absolute bottom-[50%] left-1/2 w-44 -translate-x-1/2 select-none sm:w-52">
                <img src="/images/logo (1).png" alt="bw Superbakeshop" width="225" height="225" class="animate-bake relative h-44 w-auto sm:h-56">
            </div>
        </div>
        <span class="animate-wiggle mt-10 inline-block text-6xl" role="img" aria-label="Under construction">🚧</span>
        <h1 class="mt-8 font-brand text-4xl font-bold sm:text-5xl" style="{{ \App\Models\SiteContent::typographyStyle($m['typography'] ?? []) }}">{{ $m['title'] }}</h1>
        <p class="mt-4 max-w-md text-base leading-relaxed text-navy-50/70" style="{{ \App\Models\SiteContent::typographyStyle($m['typography'] ?? []) }}">{{ $m['message'] }}</p>
        @if(count($activeSocials))
            <div class="mt-8 flex gap-3">
                @foreach($activeSocials as $s)
                    <a href="{{ $s['href'] }}" @if($isExternal($s['href'])) target="_blank" rel="noopener noreferrer" @endif
                        aria-label="{{ $s['label'] }}"
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-base font-semibold text-white transition hover:scale-110 hover:bg-brand-600">
                        {!! $s['icon'] !!}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
