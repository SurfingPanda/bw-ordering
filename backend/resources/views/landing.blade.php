@php
    // Editor-set destinations may be an internal route (/menu) or a full URL —
    // mirrors frontend/src/pages/Landing.jsx's isExternal().
    $isExternal = fn (?string $href) => (bool) preg_match('#^https?://#i', (string) $href);
    // 3-state CTA lookup (on/disabled/off) — see App\Models\SiteContent::buttonState().
    $btn = fn (string $key) => \App\Models\SiteContent::buttonState($content['buttons'] ?? [], $key);
    $siteUrl = 'https://www.bwsuperbakeshop.com';
    $metaTitle = 'BW Superbakeshop';
    $metaDescription = 'Order freshly baked cakes, breads, and pastries from bw Superbakeshop. Nationwide branches, custom cakes, and delivery.';
    $ogImage = $siteUrl.'/images/promo-cake.png';
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => ['Bakery', 'LocalBusiness'],
                '@id' => "$siteUrl/#bakery",
                'name' => 'bw Superbakeshop',
                'url' => $siteUrl,
                'logo' => "$siteUrl/favicon-192x192.png",
                'image' => $ogImage,
                'description' => $metaDescription,
                'servesCuisine' => 'Bakery',
                'priceRange' => '₱₱',
                'sameAs' => ['https://www.facebook.com/bwsuperbakeshop'],
            ],
            [
                '@type' => 'WebSite',
                '@id' => "$siteUrl/#website",
                'url' => $siteUrl,
                'name' => 'bw Superbakeshop',
                'publisher' => ['@id' => "$siteUrl/#bakery"],
            ],
            [
                '@type' => 'WebPage',
                '@id' => "$siteUrl#webpage",
                'url' => $siteUrl,
                'name' => $metaTitle,
                'description' => $metaDescription,
                'isPartOf' => ['@id' => "$siteUrl/#website"],
                'about' => ['@id' => "$siteUrl/#bakery"],
            ],
        ],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $siteUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="bw Superbakeshop">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $siteUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES) !!}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Pacifico&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
@if($content['maintenance']['enabled'] ?? false)
    @php
        $m = $content['maintenance'];
        $social = $content['social'] ?? [];
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
    {{-- Maintenance mode: the entire page is replaced by this screen — no
         flash-of-real-content risk here since (unlike the old client-rendered
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
                    <img src="/images/smoke.webp" alt="" aria-hidden="true"
                        class="pointer-events-none absolute bottom-[50%] left-1/2 w-44 -translate-x-1/2 select-none sm:w-52">
                    <img src="/images/logo (1).png" alt="bw Superbakeshop" class="animate-bake relative h-44 w-auto sm:h-56">
                </div>
            </div>
            <span class="animate-wiggle mt-10 inline-block text-6xl" role="img" aria-label="Under construction">🚧</span>
            <h1 class="mt-8 font-brand text-4xl font-bold sm:text-5xl">{{ $m['title'] }}</h1>
            <p class="mt-4 max-w-md text-base leading-relaxed text-navy-50/70">{{ $m['message'] }}</p>
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
@else
    <div class="min-h-screen bg-white text-navy-800">
        {{-- Announcement bar (Site Editor toggle; absent = shown) --}}
        @if($content['announcementVisible'] ?? true)
            <div class="bg-navy-900 text-center text-xs font-medium tracking-wide text-white">
                <p class="px-4 py-2">{{ $content['announcement'] }}</p>
            </div>
        @endif

        {{-- Nav --}}
        <header class="sticky top-0 z-50 border-b border-slate-100 bg-white">
            @php $orderState = $btn('navOrder'); $signInState = $btn('navSignIn'); @endphp
            <nav class="mx-auto flex h-20 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="/" class="flex min-w-0 items-center gap-2">
                    <img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24">
                </a>

                <ul class="hidden items-center gap-7 text-sm font-medium text-navy-700 lg:flex">
                    <li><a href="/menu" class="transition hover:text-brand-600">Menu</a></li>
                    <li><a href="/stores" class="transition hover:text-brand-600">Store</a></li>
                    <li><a href="/franchise" class="transition hover:text-brand-600">Partner with us</a></li>
                </ul>

                <div class="hidden items-center gap-3 lg:flex">
                    @if($user)
                        <a href="{{ $accountRoute }}" class="text-sm font-semibold text-navy-700 transition hover:text-brand-600">
                            Hi, {{ explode(' ', trim($user['name'] ?? ''))[0] ?: 'Account' }}
                        </a>
                    @elseif($signInState !== 'off')
                        <a href="{{ route('login') }}" @if($signInState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="text-sm font-semibold text-navy-700 transition hover:text-brand-600 {{ $signInState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">Sign In</a>
                    @endif
                    @if($orderState !== 'off')
                        <a href="/menu" @if($orderState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 {{ $orderState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">
                            Order Now
                        </a>
                    @endif
                </div>

                <button type="button" onclick="document.getElementById('mobile-nav').classList.toggle('hidden')" aria-label="Toggle menu" class="ml-2 shrink-0 text-navy-800 lg:hidden">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <line x1="4" y1="7" x2="20" y2="7" />
                        <line x1="4" y1="12" x2="20" y2="12" />
                        <line x1="4" y1="17" x2="20" y2="17" />
                    </svg>
                </button>
            </nav>

            <div id="mobile-nav" class="hidden border-t border-slate-100 bg-white px-4 py-3 lg:hidden">
                <ul class="flex flex-col gap-1 text-sm font-medium text-navy-700">
                    <li><a href="/menu" class="block rounded-lg px-3 py-2 transition hover:bg-navy-50 hover:text-brand-600">Menu</a></li>
                    <li><a href="/stores" class="block rounded-lg px-3 py-2 transition hover:bg-navy-50 hover:text-brand-600">Store</a></li>
                    <li><a href="/franchise" class="block rounded-lg px-3 py-2 transition hover:bg-navy-50 hover:text-brand-600">Partner with us</a></li>
                </ul>
                <div class="mt-3 flex gap-3">
                    @if($user)
                        <a href="{{ $accountRoute }}" class="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-navy-700">
                            Hi, {{ explode(' ', trim($user['name'] ?? ''))[0] ?: 'Account' }}
                        </a>
                    @elseif($signInState !== 'off')
                        <a href="{{ route('login') }}" @if($signInState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="flex-1 rounded-full border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-navy-700 {{ $signInState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">Sign In</a>
                    @endif
                    @if($orderState !== 'off')
                        <a href="/menu" @if($orderState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="flex-1 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white {{ $orderState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">
                            Order Now
                        </a>
                    @endif
                </div>
            </div>
        </header>

        {{-- Hero (Site Editor toggle; absent = shown) --}}
        @if($content['bannersVisible'] ?? true)
        <section id="home" class="bg-navy-900">
            <div id="hero-carousel" class="group relative">
                {{-- skip banners the editor hasn't given an image yet — an empty
                     src renders as a giant broken-image slide --}}
                @php $heroSlides = array_values(array_filter($content['banners'], fn ($b) => !empty($b['img']))); @endphp
                @foreach($heroSlides as $i => $slide)
                    <div class="hero-slide transition-opacity duration-700 ease-out {{ $i === 0 ? 'opacity-100' : 'pointer-events-none absolute inset-0 opacity-0' }}">
                        <a href="/menu" class="block">
                            <img src="{{ $slide['img'] }}" alt="{{ $slide['alt'] ?? '' }}" class="aspect-[12/5] max-h-[800px] w-full object-cover object-top">
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- What's New — products with status "new" (hidden when there are
             none, or via the Site Editor toggle) --}}
        @if(($content['whatsNew']['visible'] ?? true) && !empty($whatsNewProducts))
            <section id="whats-new" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">{{ $content['whatsNew']['eyebrow'] ?? '' }}</span>
                    <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">{{ $content['whatsNew']['title'] ?? '' }}</h2>
                    @if(!empty($content['whatsNew']['subtitle']))
                        <p class="mt-3 text-sm text-slate-500">{{ $content['whatsNew']['subtitle'] }}</p>
                    @endif
                </div>
                <div class="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
                    @foreach($whatsNewProducts as $p)
                        <div data-reveal data-reveal-delay="{{ ($loop->index % 4) * 80 }}">
                            @include('partials.product-card', ['product' => $p])
                        </div>
                    @endforeach
                </div>
                <div class="mt-10 text-center" data-reveal>
                    <a href="/menu?category={{ urlencode("What's New") }}"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        See What's New <span aria-hidden="true">→</span>
                    </a>
                </div>
            </section>
        @endif

        {{-- Best Sellers: live products flagged status=best_seller (see LandingController) --}}
        <section id="best-sellers" class="bg-navy-50/60 py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center" data-reveal>
                    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Crowd favorites</span>
                    <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">Our Best Sellers</h2>
                    <p class="mt-3 text-sm text-slate-500">Tried, tested, and loved — the treats our customers can't get enough of.</p>
                </div>
                <div class="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
                    @foreach($bestSellers as $p)
                        <div data-reveal data-reveal-delay="{{ ($loop->index % 4) * 80 }}">
                            @include('partials.product-card', ['product' => $p])
                        </div>
                    @endforeach
                </div>
                @php $bsState = $btn('bestSellersMenu'); @endphp
                @if($bsState !== 'off')
                    <div class="mt-10 text-center">
                        <a href="/menu?category={{ urlencode('Best Sellers') }}"
                            @if($bsState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="inline-block rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900 {{ $bsState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">
                            See Best Sellers
                        </a>
                    </div>
                @endif
            </div>
        </section>

        {{-- Categories: distinct product categories (see LandingController;
             Site Editor toggle saved by the Menu Categories tab) --}}
        @if($content['categoriesVisible'] ?? true)
        <section id="categories" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="mx-auto max-w-2xl text-center" data-reveal>
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Shop by category</span>
                <h2 class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">What are you craving today?</h2>
                <p class="mt-3 text-sm text-slate-500">Browse our full range of freshly baked goodies for every occasion.</p>
            </div>
            <div class="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-3">
                @foreach(array_slice($categories, 0, 6) as $c)
                    <div data-reveal data-reveal-delay="{{ $loop->index * 80 }}">
                    <a href="/menu?category={{ urlencode($c['name']) }}" class="group flex h-full flex-col items-center gap-4 rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-sm transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg">
                        <span class="h-28 w-28 overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-100 transition group-hover:ring-brand-200">
                            @if(!empty($c['img']))
                                <img src="{{ $c['img'] }}" alt="{{ $c['name'] }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-300 group-hover:scale-110">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-xs font-medium text-slate-400">no image</span>
                            @endif
                        </span>
                        <span class="text-lg font-semibold text-navy-700">{{ $c['name'] }}</span>
                    </a>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 text-center">
                <a href="/menu" class="inline-block rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900">
                    See all category
                </a>
            </div>
        </section>
        @endif

        {{-- Custom cake promo banner --}}
        @php
            $cc = $content['customCake'];
            $bannerLink = $cc['bannerLink'] ?: '/menu';
            $bannerJs = $isExternal($bannerLink)
                ? "window.open(".json_encode($bannerLink).", '_blank', 'noopener')"
                : 'window.location.href='.json_encode($bannerLink);
            $promoState = $btn('promoOrder');
        @endphp
        @if($cc['visible'] ?? true)
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6" data-reveal>
            <div id="custom-cake" role="button" tabindex="0"
                onclick="{{ $bannerJs }}"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();{{ $bannerJs }};}"
                aria-label="Order custom cakes"
                class="relative min-h-[300px] cursor-pointer rounded-3xl bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-12 text-white shadow-xl outline-none transition hover:shadow-2xl focus-visible:ring-2 focus-visible:ring-white/70 sm:px-12">
                @if(!empty($cc['image']))
                    <img src="{{ $cc['image'] }}" alt="{{ $cc['alt'] ?? '' }}" loading="lazy" decoding="async"
                        class="pointer-events-none absolute bottom-0 right-0 hidden w-[58%] max-w-[680px] drop-shadow-2xl sm:block">
                @endif
                <div class="relative z-10 max-w-md">
                    @if(!empty($cc['eyebrow']))
                        <p class="font-script text-2xl text-white/90">{{ $cc['eyebrow'] }}</p>
                    @endif
                    <h2 class="mt-2 text-3xl font-bold sm:text-4xl">{{ $cc['title'] }}</h2>
                    @if(!empty($cc['subtitle']))
                        <p class="mt-3 text-sm text-white/90">{{ $cc['subtitle'] }}</p>
                    @endif
                    @if($promoState !== 'off')
                        @php
                            $promoHref = $cc['buttonLink'] ?: '/custom-cake';
                            $promoDisabled = $promoState === 'disabled';
                        @endphp
                        <a href="{{ $promoHref }}" @if($isExternal($promoHref)) target="_blank" rel="noreferrer" @endif
                            onclick="event.stopPropagation();{{ $promoDisabled ? 'event.preventDefault();' : '' }}"
                            @if($promoDisabled) aria-disabled="true" tabindex="-1" @endif
                            class="mt-6 inline-block rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-600 shadow-md transition hover:bg-navy-50 {{ $promoDisabled ? 'cursor-not-allowed opacity-60' : '' }}">
                            {{ $cc['buttonLabel'] ?: 'Order a custom cake' }}
                        </a>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- Store locator teaser (Site Editor → Store Locator; the real
             MapLibre locator lives on /stores) --}}
        @php $sl = $content['storeLocator']; $storeState = $btn('storeLocatorFind'); @endphp
        @if($sl['visible'] ?? true)
        <section id="stores" class="bg-navy-900 py-16">
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6" data-reveal>
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20">
                    <svg class="h-7 w-7 text-brand-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 9l1.5-5h15L21 9" />
                        <path d="M4 9v11h16V9" />
                        <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
                        <path d="M9 20v-5h6v5" />
                    </svg>
                </span>
                <h2 class="mt-5 text-3xl font-bold text-white sm:text-4xl">{{ $sl['title'] ?? '' }}</h2>
                @if(!empty($sl['subtitle']))
                    <p class="mt-3 text-sm text-navy-50/80">{{ $sl['subtitle'] }}</p>
                @endif
                @if($storeState !== 'off')
                    @php $storeOff = $storeState === 'disabled'; @endphp
                    <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row {{ $storeOff ? 'cursor-not-allowed opacity-60' : '' }}">
                        <input type="text" placeholder="{{ $sl['placeholder'] ?? 'Enter your city or area' }}" @if($storeOff) disabled @endif
                            class="w-full rounded-full border border-white/20 bg-white/5 px-5 py-3 text-sm text-white placeholder:text-navy-50/50 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-400/30 disabled:cursor-not-allowed sm:w-72">
                        <a href="/stores" @if($storeOff) aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                            Find a store
                        </a>
                    </div>
                @endif
            </div>
        </section>
        @endif

        {{-- Newsletter — a no-op form in the original SPA too (preventDefault only, no submission) --}}
        @php $n = $content['newsletter']; $newsState = $btn('newsletterSubscribe'); @endphp
        @if($n['visible'] ?? true)
        <section id="newsletter" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="rounded-3xl border border-brand-100 bg-brand-50 px-8 py-12 text-center sm:px-12" data-reveal>
                <h2 class="text-2xl font-bold text-navy-800 sm:text-3xl">{{ $n['title'] }}</h2>
                @if(!empty($n['subtitle']))
                    <p class="mt-2 text-sm text-slate-600">{{ $n['subtitle'] }}</p>
                @endif
                @if($newsState !== 'off')
                    @php $newsOff = $newsState === 'disabled'; @endphp
                    <form onsubmit="event.preventDefault(); return false;" class="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row {{ $newsOff ? 'cursor-not-allowed opacity-60' : '' }}">
                        <input type="email" required @if($newsOff) disabled @endif placeholder="{{ $n['placeholder'] }}"
                            class="w-full rounded-full border border-slate-300 px-5 py-3 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed">
                        <button type="submit" @if($newsOff) disabled @endif
                            class="rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900 disabled:cursor-not-allowed">
                            {{ $n['buttonLabel'] }}
                        </button>
                    </form>
                @endif
            </div>
        </section>
        @endif

        {{-- Footer --}}
        @include('partials.site-footer', ['f' => $content['footer'], 'social' => $content['social'] ?? []])
    </div>

    {{-- Shared product detail modal, populated from whichever card was clicked --}}
    @include('partials.product-modal')

    <script>
    (function () {
        // Hero carousel: auto cross-fade, pause on hover, honors reduced motion
        // (mirrors the old dependency-free Carousel component, arrows/dots off).
        var carousel = document.getElementById('hero-carousel');
        if (carousel) {
            var slides = carousel.querySelectorAll('.hero-slide');
            var index = 0;
            var paused = false;
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var show = function (i) {
                slides.forEach(function (s, si) {
                    if (si === i) {
                        s.classList.remove('pointer-events-none', 'absolute', 'inset-0', 'opacity-0');
                        s.classList.add('opacity-100');
                    } else {
                        s.classList.remove('opacity-100');
                        s.classList.add('pointer-events-none', 'absolute', 'inset-0', 'opacity-0');
                    }
                });
            };
            if (slides.length > 1 && !reduce) {
                setInterval(function () {
                    if (paused) return;
                    index = (index + 1) % slides.length;
                    show(index);
                }, 5000);
                carousel.addEventListener('mouseenter', function () { paused = true; });
                carousel.addEventListener('mouseleave', function () { paused = false; });
            }
        }

        // Shared product detail modal (partials/product-modal.blade.php),
        // populated from whichever card's data-* attrs was clicked. Only the
        // price + qty-stepper + "Order now" link footer is landing-specific
        // (it just deep-links into /menu — the real cart lives there); the
        // modal shell itself is shared with menu.blade.php's version.
        var pmModal = window.ProductModal.init({ closeOnBackdrop: true });

        var buildOrderFooter = function (footer, d) {
            var qty = 1;
            footer.innerHTML = `
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <span class="text-3xl font-extrabold text-brand-600">${d.price || ''}</span>
                    <div class="flex shrink-0 items-center gap-3 rounded-full bg-slate-100 px-2 py-1.5">
                        <button type="button" data-qty-minus aria-label="Decrease quantity" class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-navy-800 shadow-sm transition hover:bg-slate-50">&minus;</button>
                        <span data-qty class="w-5 text-center text-sm font-bold text-navy-800">1</span>
                        <button type="button" data-qty-plus aria-label="Increase quantity" class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-navy-800 shadow-sm transition hover:bg-slate-50">+</button>
                    </div>
                </div>
                <a data-order href="#" class="mt-5 flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    <span aria-hidden="true">🛍️</span> Order now
                </a>`;
            var qtyEl = footer.querySelector('[data-qty]');
            var orderEl = footer.querySelector('[data-order]');
            var updateHref = function () { orderEl.href = '/menu?add=' + encodeURIComponent(d.name || '') + '&qty=' + qty; };
            var setQty = function (q) { qty = Math.max(1, q); qtyEl.textContent = qty; updateHref(); };
            footer.querySelector('[data-qty-minus]').addEventListener('click', function () { setQty(qty - 1); });
            footer.querySelector('[data-qty-plus]').addEventListener('click', function () { setQty(qty + 1); });
            setQty(1);
        };

        document.querySelectorAll('.product-card-trigger').forEach(function (el) {
            var open = function () {
                pmModal.open({
                    img: el.dataset.img,
                    name: el.dataset.name,
                    badge: el.dataset.tag,
                    desc: el.dataset.desc,
                    calories: el.dataset.calories,
                    allergens: el.dataset.allergens,
                    price: el.dataset.price,
                }, buildOrderFooter);
            };
            el.addEventListener('click', open);
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    open();
                }
            });
        });

        // Reveal-on-scroll (port of the SPA's Reveal.jsx): fade + slide
        // sections in when they enter the viewport and back out when they
        // leave, so it re-animates on scroll up *and* down. Content starts
        // visible (crawlers and no-JS see everything); honors
        // prefers-reduced-motion; renders statically inside the Site Editor's
        // scaled-down preview iframe (?preview=1), where the observer would
        // otherwise keep everything hidden.
        var reveals = document.querySelectorAll('[data-reveal]');
        var staticReveal = new URLSearchParams(window.location.search).has('preview')
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches
            || !('IntersectionObserver' in window);
        if (reveals.length && !staticReveal) {
            reveals.forEach(function (el) {
                el.classList.add('transition-all', 'duration-700', 'ease-out');
                el.style.transitionDelay = (el.dataset.revealDelay || 0) + 'ms';
            });
            var revealObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    entry.target.classList.toggle('opacity-100', entry.isIntersecting);
                    entry.target.classList.toggle('translate-y-0', entry.isIntersecting);
                    entry.target.classList.toggle('opacity-0', !entry.isIntersecting);
                    entry.target.classList.toggle('translate-y-8', !entry.isIntersecting);
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -10% 0px' });
            reveals.forEach(function (el) { revealObserver.observe(el); });
        }
    })();
    </script>
@endif
</body>
</html>
