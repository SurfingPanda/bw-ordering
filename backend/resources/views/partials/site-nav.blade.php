{{-- Shared public-page header — the sticky navy bar with the circular logo
     badge that overflows the bottom edge, matching the landing page's nav.
     Used on every secondary public page (About, Contact, Stores, Franchise,
     Customize, legal) so the chrome is identical everywhere.

     Optional slot for a right-hand call-to-action pill:
       @include('partials.site-nav', ['navCtaHref' => $href, 'navCtaLabel' => 'Inquire now'])
     Omit both to render just the "Back to home" link. --}}
@php
    $navCtaHref = $navCtaHref ?? null;
    $navCtaLabel = $navCtaLabel ?? null;
@endphp
<header class="sticky top-0 z-50" style="background-color: {{ $nav['color'] ?? '#083caa' }};">
    <nav class="relative mx-auto flex h-20 max-w-6xl items-center justify-between px-4 sm:px-6">
        {{-- Reserves layout width in the flex row; the actual circular badge is
             absolutely positioned within it so it can spill past the header's
             bottom edge without affecting the rest of the nav's flex layout. --}}
        <a href="/" class="group relative z-10 h-full w-20 shrink-0 sm:w-24">
            {{-- Soft glow behind the badge, fades in on hover — sits earlier in
                 the DOM (and so behind) the badge span below, no z-index needed. --}}
            <span aria-hidden="true" class="pointer-events-none absolute -bottom-12 left-0 h-24 w-24 rounded-full bg-brand-400/50 opacity-0 blur-md transition-opacity duration-300 group-hover:opacity-100 sm:-bottom-14 sm:h-28 sm:w-28"></span>
            <span class="absolute -bottom-12 left-0 flex h-24 w-24 items-center justify-center rounded-full bg-white p-2 shadow-lg transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:scale-110 sm:-bottom-14 sm:h-28 sm:w-28">
                <img src="{{ $nav['logo'] ?? '/images/logo (1).png' }}" alt="bw Superbakeshop" width="225" height="225" class="h-full w-full object-contain">
            </span>
        </a>

        <div class="flex items-center gap-4">
            <a href="/" class="text-sm font-semibold text-white/90 transition hover:text-white">&larr; Back to home</a>
            @if($navCtaHref && $navCtaLabel)
                <a href="{{ $navCtaHref }}" class="hidden rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 sm:block">
                    {{ $navCtaLabel }}
                </a>
            @endif
        </div>
    </nav>
</header>
