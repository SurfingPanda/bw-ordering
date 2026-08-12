@php
    // Editor-set destinations may be an internal route (/menu) or a full URL —
    // mirrors frontend/src/pages/Landing.jsx's isExternal().
    $isExternal = fn (?string $href) => (bool) preg_match('#^https?://#i', (string) $href);
    // 3-state CTA lookup (on/disabled/off) — see App\Models\SiteContent::buttonState().
    $btn = fn (string $key) => \App\Models\SiteContent::buttonState($content['buttons'] ?? [], $key);
    $siteUrl = rtrim(config('app.url'), '/');
    $metaTitle = 'BW Superbakeshop';
    $metaDescription = 'Order freshly baked cakes, breads, and pastries from bw Superbakeshop. Nationwide branches, custom cakes, and delivery.';
    $ogImage = $siteUrl.'/images/promo-cake.png';
    $canonical = $siteUrl;
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => ['Organization', 'Bakery'],
                '@id' => "$siteUrl/#bakery",
                'name' => 'bw Superbakeshop',
                'url' => $siteUrl,
                'logo' => "$siteUrl/favicon-192x192.png",
                'image' => $ogImage,
                'description' => $metaDescription,
                'servesCuisine' => 'Bakery',
                'priceRange' => '₱₱',
                'areaServed' => 'Philippines',
                'sameAs' => ['https://www.facebook.com/bwsuperbakeshop'],
                // Chains don't have one address — link out to the real,
                // fully-addressed per-branch LocalBusiness nodes emitted on
                // /stores instead of inventing a fake single location here.
                'department' => $mapStores->map(fn ($s) => ['@id' => "$siteUrl/stores#store-{$s['id']}"])->values()->all(),
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
    @include('partials.seo-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Pacifico&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
@if($content['maintenance']['enabled'] ?? false)
    @include('partials.maintenance-screen', ['m' => $content['maintenance'], 'social' => $content['social'] ?? []])
@else
    <div class="min-h-screen bg-brand-50 text-navy-800">
        {{-- Always-rendered (unlike the hero carousel, which is a Site Editor
             toggle and pure images with no text) so the page has exactly one
             real <h1> regardless of CMS state. --}}
        <h1 class="sr-only">bw Superbakeshop — Fresh Cakes, Bread &amp; Pastries, Order Online for Pickup or Delivery Nationwide</h1>
        {{-- Announcement bar (Site Editor toggle; absent = shown) --}}
        @if($content['announcementVisible'] ?? true)
            <div class="bg-navy-900 text-center text-xs font-medium tracking-wide text-white">
                <p data-editable="announcement" data-editable-multiline class="px-4 py-2" style="{{ \App\Models\SiteContent::typographyStyle($content['announcementTypography'] ?? []) }}">{{ $content['announcement'] }}</p>
            </div>
        @endif

        {{-- Nav --}}
        <header class="sticky top-0 z-50" style="background-color: {{ $nav['color'] ?? '#083caa' }};">
            @php $orderState = $btn('navOrder'); $signInState = $btn('navSignIn'); @endphp
            {{-- Nav "Menu"/"Order Now" open straight to What's New (falls back to
                 "All" on /menu itself if there's nothing new to show — see the
                 requestedTab handling in menu.blade.php) so first-time visitors
                 see the newest products instead of the full unsorted catalogue,
                 unless the Site Editor's Navigation Bar section overrides it. --}}
            @php
                $menuHref = $nav['links']['menu'] ?: ('/menu?category=' . urlencode("What's New"));
                $storeHref = $nav['links']['store'] ?: '/stores';
                $franchiseHref = $nav['links']['franchise'] ?: '/franchise';
            @endphp
            <nav class="relative mx-auto flex h-20 max-w-6xl items-center justify-between px-4 sm:px-6">
                {{-- Reserves layout width in the flex row; the actual circular
                     badge is absolutely positioned within it so it can spill
                     past the header's bottom edge without affecting the rest
                     of the nav's flex layout. --}}
                <a href="/" class="group relative z-10 h-full w-20 shrink-0 sm:w-24">
                    {{-- Soft glow behind the badge, fades in on hover — sits
                         earlier in the DOM (and so behind) the badge span
                         below, no z-index needed. --}}
                    <span aria-hidden="true" class="pointer-events-none absolute -bottom-12 left-0 h-24 w-24 rounded-full bg-brand-400/50 opacity-0 blur-md transition-opacity duration-300 group-hover:opacity-100 sm:-bottom-14 sm:h-28 sm:w-28"></span>
                    <span class="absolute -bottom-12 left-0 flex h-24 w-24 items-center justify-center rounded-full bg-white p-2 shadow-lg transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] group-hover:scale-110 sm:-bottom-14 sm:h-28 sm:w-28">
                        <img src="{{ $nav['logo'] ?? '/images/logo (1).png' }}" alt="bw Superbakeshop" width="225" height="225" class="h-full w-full object-contain">
                    </span>
                </a>

                @php
                    // Animated underline on hover: a rounded bar that grows
                    // from the left, matching the pill/soft-edge language
                    // used everywhere else on this page (buttons, badges).
                    $navLink = 'relative py-1 transition hover:text-white after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-0 after:rounded-full after:bg-brand-500 after:transition-all after:duration-300 after:ease-out after:content-[\'\'] hover:after:w-full';
                @endphp
                <ul class="hidden items-center gap-7 text-sm font-medium text-white/90 lg:flex">
                    <li><a href="{{ $menuHref }}" class="{{ $navLink }}">Menu</a></li>
                    <li><a href="{{ $storeHref }}" class="{{ $navLink }}">Store</a></li>
                    <li><a href="{{ $franchiseHref }}" class="{{ $navLink }}">Partner with us</a></li>
                </ul>

                <div class="hidden items-center gap-3 lg:flex">
                    @if($user)
                        <a href="{{ $accountRoute }}" class="text-sm font-semibold text-white/90 transition hover:text-white">
                            Hi, {{ explode(' ', trim($user['name'] ?? ''))[0] ?: 'Account' }}
                        </a>
                    @elseif($signInState !== 'off')
                        <a href="{{ route('login') }}" @if($signInState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="text-sm font-semibold text-white/90 transition hover:text-white {{ $signInState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">Sign In</a>
                    @endif
                    @if($orderState !== 'off')
                        <a href="{{ $menuHref }}" @if($orderState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 {{ $orderState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /><path d="M16 10v-4" /><path d="M14 8h4" /></svg>
                            Order Now
                        </a>
                    @endif
                </div>

                <button type="button" onclick="document.getElementById('mobile-nav').classList.toggle('hidden')" aria-label="Toggle menu" class="ml-2 shrink-0 text-white lg:hidden">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <line x1="4" y1="7" x2="20" y2="7" />
                        <line x1="4" y1="12" x2="20" y2="12" />
                        <line x1="4" y1="17" x2="20" y2="17" />
                    </svg>
                </button>
            </nav>

            <div id="mobile-nav" class="hidden border-t border-navy-900/10 px-4 py-3 lg:hidden" style="background-color: {{ $nav['color'] ?? '#083caa' }};">
                {{-- Centered (not left-aligned) so these links clear the
                     circular logo badge, which overflows past the header's
                     bottom edge on the left and would otherwise sit right on
                     top of the first item here. --}}
                <ul class="flex flex-col items-center gap-1 text-center text-sm font-medium text-white/90">
                    <li><a href="{{ $menuHref }}" class="block rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">Menu</a></li>
                    <li><a href="{{ $storeHref }}" class="block rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">Store</a></li>
                    <li><a href="{{ $franchiseHref }}" class="block rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">Partner with us</a></li>
                </ul>
                <div class="mt-3 flex gap-3">
                    @if($user)
                        <a href="{{ $accountRoute }}" class="flex-1 rounded-full border border-white/30 px-4 py-2.5 text-center text-sm font-semibold text-white">
                            Hi, {{ explode(' ', trim($user['name'] ?? ''))[0] ?: 'Account' }}
                        </a>
                    @elseif($signInState !== 'off')
                        <a href="{{ route('login') }}" @if($signInState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="flex-1 rounded-full border border-white/30 px-4 py-2.5 text-center text-sm font-semibold text-white {{ $signInState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">Sign In</a>
                    @endif
                    @if($orderState !== 'off')
                        <a href="{{ $menuHref }}" @if($orderState === 'disabled') aria-disabled="true" tabindex="-1" onclick="event.preventDefault()" @endif
                            class="flex flex-1 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white {{ $orderState === 'disabled' ? 'cursor-not-allowed opacity-60' : '' }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /><path d="M16 10v-4" /><path d="M14 8h4" /></svg>
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
                     src renders as a giant broken-image slide. Deliberately
                     NOT array_values()'d — $i needs to stay the real index
                     into $content['banners'] (it drives data-editable's path
                     below, which must match the sidebar's actual
                     banners[$i][img] field), so "is this the first slide"
                     uses Blade's own $loop->first instead of assuming $i
                     starts at 0. --}}
                @php $heroSlides = array_filter($content['banners'], fn ($b) => !empty($b['img'])); @endphp
                @foreach($heroSlides as $i => $slide)
                    <div class="hero-slide transition-opacity duration-700 ease-out {{ $loop->first ? 'opacity-100' : 'pointer-events-none absolute inset-0 opacity-0' }}">
                        <a href="/menu" class="block">
                            {{-- Only the first slide is visible on load, so later
                                 slides are marked lazy on principle; note browsers
                                 still fetch them near-immediately in practice since
                                 they're stacked in the same above-the-fold box
                                 (opacity, not position, is what hides them). --}}
                            {{-- width/height set the intrinsic ratio (matches the CMS's
                                 documented recommended banner size, 1920x800 = 12:5) as a
                                 fallback for the aspect-[12/5] class — actual uploaded
                                 banners are cropped to this box via object-cover
                                 regardless of their real dimensions. --}}
                            <img data-editable="banners.{{ $i }}.img" src="{{ $slide['img'] }}" alt="{{ $slide['alt'] ?? '' }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async" width="1920" height="800" class="aspect-[12/5] max-h-[800px] w-full object-cover object-top">
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- What's New — products with status "new" (hidden when there are
             none, or via the Site Editor toggle) --}}
        @if(($content['whatsNew']['visible'] ?? true) && !empty($whatsNewProducts))
            <section id="whats-new" class="bg-white py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6">
                    @php $whatsNewTypography = \App\Models\SiteContent::typographyStyle($content['whatsNew']['typography'] ?? []); @endphp
                    <div class="mx-auto max-w-2xl text-center" data-reveal>
                        <span data-editable="whatsNew.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500" style="{{ $whatsNewTypography }}">{{ $content['whatsNew']['eyebrow'] ?? '' }}</span>
                        <h2 data-editable="whatsNew.title" class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl" style="{{ $whatsNewTypography }}">{{ $content['whatsNew']['title'] ?? '' }}</h2>
                        @if(!empty($content['whatsNew']['subtitle']))
                            <p data-editable="whatsNew.subtitle" data-editable-multiline class="mt-3 text-sm text-slate-500" style="{{ $whatsNewTypography }}">{{ $content['whatsNew']['subtitle'] }}</p>
                        @endif
                    </div>
                    <div class="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
                        @foreach($whatsNewProducts as $p)
                            <div class="product-card-wrap" data-reveal data-reveal-delay="{{ ($loop->index % 4) * 80 }}">
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
                </div>
            </section>
        @endif

        {{-- Best Sellers: live products flagged status=best_seller (see LandingController).
             Hidden entirely when nothing is flagged yet, same as What's New below —
             otherwise this renders as an empty heading + empty grid.

             Optional editor-uploaded background image (Site Editor → Best
             Sellers) shown exactly as uploaded — no dark overlay/tint, same
             rule as the Categories/Custom Cake backdrop above. The heading
             sits on its own white card when a background is set — a white
             card with dark text reads on either a light or dark photo,
             without ever touching/dimming the photo itself. --}}
        @php $bestSellersBg = $content['bestSellersSection']['backgroundImage'] ?? null; @endphp
        @if(!empty($bestSellers))
        <section id="best-sellers" class="bg-cover bg-center py-16 {{ $bestSellersBg ? '' : 'bg-navy-50/60' }}" @if($bestSellersBg) style="background-image: url('{{ $bestSellersBg }}')" @endif>
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center {{ $bestSellersBg ? 'rounded-3xl bg-white/95 px-6 py-8 shadow-lg' : '' }}" data-reveal>
                    <span data-editable="bestSellersSection.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">{{ $content['bestSellersSection']['eyebrow'] ?? '' }}</span>
                    <h2 data-editable="bestSellersSection.title" class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">{{ $content['bestSellersSection']['title'] ?? '' }}</h2>
                    <p data-editable="bestSellersSection.subtitle" data-editable-multiline class="mt-3 text-sm text-slate-500">{{ $content['bestSellersSection']['subtitle'] ?? '' }}</p>
                </div>
                <div class="mt-10 grid grid-cols-2 gap-5 md:grid-cols-4">
                    @foreach($bestSellers as $p)
                        <div class="product-card-wrap" data-reveal data-reveal-delay="{{ ($loop->index % 4) * 80 }}">
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
        @endif

        {{-- Categories + Custom Cake promo share one backdrop: an
             editor-uploaded background image (Site Editor → Custom Cake
             Banner → "Section background image") sits behind both, shown
             exactly as uploaded — no dark overlay/tint of any kind, so the
             photo never looks darkened by default. An editor-adjustable
             opacity slider is the one exception, and it's applied only to
             this dedicated background layer (never the content on top of
             it), so fading the photo back never fades the heading/cards
             with it. Legibility instead comes from putting the Categories
             heading on its own solid white card (works over a light OR dark
             photo, unlike white text which only reads on a dark one).
             Falls back to the plain page background (no card, original text
             colors) when nothing's uploaded. --}}
        @php
            $cc = $content['customCake'];
            $promoState = $btn('promoOrder');
            $categoriesOn = $content['categoriesVisible'] ?? true;
            $customCakeOn = $cc['visible'] ?? true;
            $sectionsBg = $cc['backgroundImage'] ?? null;
            $sectionsBgOpacity = ($cc['backgroundOpacity'] ?? '') !== '' ? (int) $cc['backgroundOpacity'] : 100;
        @endphp
        @if($categoriesOn || $customCakeOn)
        <div class="relative isolate">
            @if($sectionsBg)
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $sectionsBg }}'); opacity: {{ $sectionsBgOpacity / 100 }};"></div>
            @endif
            <div class="relative">
                {{-- Categories: distinct product categories (see LandingController;
                     Site Editor toggle saved by the Menu Categories tab) --}}
                @if($categoriesOn)
                <section id="categories" class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
                    <div class="mx-auto max-w-2xl text-center" data-reveal>
                        <span data-editable="categoriesSection.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">{{ $content['categoriesSection']['eyebrow'] ?? '' }}</span>
                        <h2 data-editable="categoriesSection.title" class="mt-3 text-3xl font-bold text-navy-800 sm:text-4xl">{{ $content['categoriesSection']['title'] ?? '' }}</h2>
                        <p data-editable="categoriesSection.subtitle" data-editable-multiline class="mt-3 mb-8 text-sm text-slate-500">{{ $content['categoriesSection']['subtitle'] ?? '' }}</p>
                    </div>
                    {{-- Same placeholder image + "no image" onerror fallback the
                         product cards use (partials/product-card.blade.php), so a
                         category with no photo shows the same thing a product does
                         instead of a bare grey "no image" tile. The uploaded photo
                         fills the whole card (not just a small circle badge) with
                         the category name overlaid at the bottom on a gradient
                         scrim, so the image reads at full size. --}}
                    @php $fallbackImg = 'https://xhy0hjgguaqll6zn.public.blob.vercel-storage.com/custom-cake-refs/1781654816384-p23ferfqoq.png'; @endphp
                    <div class="mt-12 grid grid-cols-2 gap-6 sm:grid-cols-3">
                        @foreach(array_slice($categories, 0, 6) as $c)
                            <div data-reveal data-reveal-delay="{{ $loop->index * 80 }}">
                            <a href="/menu?category={{ urlencode($c['name']) }}" class="group relative block h-48 overflow-hidden rounded-3xl shadow-sm transition hover:-translate-y-1 hover:shadow-lg sm:h-56">
                                <img src="{{ $c['img'] ?: $fallbackImg }}" alt="{{ $c['name'] }}" loading="lazy" decoding="async" width="400" height="300"
                                    class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    onerror="this.nextElementSibling.classList.replace('hidden', 'flex'); this.remove();">
                                <span class="hidden absolute inset-0 items-center justify-center bg-slate-100 text-xs font-medium text-slate-400">no image</span>
                                <span class="absolute inset-0 bg-gradient-to-t from-navy-900/80 via-navy-900/10 to-transparent"></span>
                                <span class="absolute inset-x-0 bottom-0 p-4 text-left text-lg font-semibold text-white">{{ $c['name'] }}</span>
                            </a>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-12 text-center">
                        <a href="/menu" class="inline-block rounded-full bg-gradient-to-r from-navy-700 to-navy-800 px-8 py-3 text-sm font-semibold text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900">
                            See all categories
                        </a>
                    </div>
                </section>
                @endif

                {{-- Custom cake promo banner --}}
                @if($customCakeOn)
                <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6" data-reveal>
                    <div id="custom-cake"
                        class="relative min-h-[300px] rounded-3xl px-8 py-12 text-white shadow-xl sm:px-12"
                        style="background-color: {{ $cc['backgroundColor'] ?? '#ef7d1a' }};">
                        @if(!empty($cc['image']))
                            {{-- 480x720 matches the default custom-cake-tower.svg's own
                                 viewBox (2:3) — same "intrinsic ratio hint, not the
                                 literal file size" approach as the hero banner above,
                                 since an editor-uploaded replacement can be any ratio. --}}
                            <img data-editable="customCake.image" src="{{ $cc['image'] }}" alt="{{ $cc['alt'] ?? '' }}" loading="lazy" decoding="async" width="480" height="720"
                                class="pointer-events-none absolute bottom-0 right-6 hidden h-[300px] w-auto max-w-none drop-shadow-2xl lg:block lg:right-16 lg:h-[500px]">
                        @endif
                        @php $customCakeTypography = \App\Models\SiteContent::typographyStyle($cc['typography'] ?? []); @endphp
                        <div class="relative z-10 max-w-md">
                            @if(!empty($cc['eyebrow']))
                                <p data-editable="customCake.eyebrow" class="font-script text-2xl text-white/90" style="{{ $customCakeTypography }}">{{ $cc['eyebrow'] }}</p>
                            @endif
                            <h2 data-editable="customCake.title" class="mt-2 text-3xl font-bold sm:text-4xl" style="{{ $customCakeTypography }}">{{ $cc['title'] }}</h2>
                            @if(!empty($cc['subtitle']))
                                <p data-editable="customCake.subtitle" data-editable-multiline class="mt-3 text-sm text-white/90" style="{{ $customCakeTypography }}">{{ $cc['subtitle'] }}</p>
                            @endif
                            @if($promoState !== 'off')
                                @php
                                    $promoHref = $cc['buttonLink'] ?: '/custom-cake';
                                    $promoDisabled = $promoState === 'disabled';
                                @endphp
                                <a data-editable="customCake.buttonLabel" href="{{ $promoHref }}" @if($isExternal($promoHref)) target="_blank" rel="noreferrer" @endif
                                    @if($promoDisabled) onclick="event.preventDefault();" aria-disabled="true" tabindex="-1" @endif
                                    class="mt-6 inline-block rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-600 shadow-md transition hover:bg-navy-50 {{ $promoDisabled ? 'cursor-not-allowed opacity-60' : '' }}">
                                    {{ $cc['buttonLabel'] ?: 'Order a custom cake' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </section>
                @endif
            </div>
        </div>
        @endif

        {{-- Store locator teaser (Site Editor → Store Locator) — two-panel
             layout: copy + search on the left, a real pannable/zoomable 3D
             map (lazy-loaded, see the script at the bottom of this file)
             previewing actual branch pins on the right, with a floating card
             for whichever pin is selected. The full search/region-filter/
             find-nearest experience stays on /stores. --}}
        @php
            $sl = $content['storeLocator'];
            $storeState = $btn('storeLocatorFind');
            $dirHref = fn ($address) => 'https://www.google.com/maps/dir/?api=1&destination='.urlencode($address);
            $firstStore = $mapStores->first();
        @endphp
        @if($sl['visible'] ?? true)
        <section id="stores" class="relative overflow-hidden bg-navbar py-16">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-5 lg:items-center lg:gap-12">
                <div class="lg:col-span-2" data-reveal>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-brand-600 shadow-sm">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 9l1.5-5h15L21 9" /><path d="M4 9v11h16V9" /><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" /><path d="M9 20v-5h6v5" />
                        </svg>
                        Store Locator
                    </span>
                    @php $storeLocatorTypography = \App\Models\SiteContent::typographyStyle($sl['typography'] ?? []); @endphp
                    <h2 data-editable="storeLocator.title" class="mt-5 text-3xl font-bold text-white sm:text-4xl" style="{{ $storeLocatorTypography }}">{{ $sl['title'] ?? '' }}</h2>
                    @if(!empty($sl['subtitle']))
                        <p data-editable="storeLocator.subtitle" data-editable-multiline class="mt-3 text-sm text-white/80" style="{{ $storeLocatorTypography }}">{{ $sl['subtitle'] }}</p>
                    @endif
                    @if($storeState !== 'off')
                        @php $storeOff = $storeState === 'disabled'; @endphp
                        {{-- A real GET form to /stores?q=... — stores.blade.php's JS
                             (resources/js/stores.js) reads `q` on load and applies it
                             to the same search box the /stores page itself uses, so
                             typing here and pressing Enter (or clicking the button)
                             actually filters, instead of always landing on an
                             unfiltered list regardless of what was typed. --}}
                        <form action="/stores" method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row {{ $storeOff ? 'cursor-not-allowed opacity-60' : '' }}">
                            <div class="relative w-full">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" /><circle cx="12" cy="10" r="3" /></svg>
                                <input type="text" name="q" placeholder="{{ $sl['placeholder'] ?? 'Enter your city or area' }}" @if($storeOff) disabled @endif
                                    class="w-full rounded-full border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-navy-800 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-400/20 disabled:cursor-not-allowed">
                            </div>
                            <button type="submit" @if($storeOff) disabled @endif
                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed">
                                Find a store <span aria-hidden="true">→</span>
                            </button>
                        </form>
                    @endif

                    <div class="mt-7 grid grid-cols-3 gap-3 text-center">
                        @foreach([
                            ['icon' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0Z" /><circle cx="12" cy="10" r="3" />', 'label' => 'Nationwide Branches'],
                            ['icon' => '<circle cx="18.5" cy="17.5" r="2.5" /><circle cx="5.5" cy="17.5" r="2.5" /><path d="M15 6a1 1 0 0 1 1 1v8.5M15 6H9.5L6 11h9M5.5 17.5H3V13l2-4" /><path d="M13 11h4.5l2.5 3v3.5" />', 'label' => 'Fast Delivery & Pickup'],
                            ['icon' => '<rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />', 'label' => 'Easy & Secure Ordering'],
                        ] as $f)
                            <div>
                                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $f['icon'] !!}</svg>
                                </span>
                                <p class="mt-2 text-xs font-medium leading-snug text-white/90">{{ $f['label'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-5 py-4">
                        <div>
                            <p class="text-sm font-bold text-navy-800">Can't find your area?</p>
                            <p class="text-xs text-slate-500">View all stores and get detailed directions.</p>
                        </div>
                        <a href="/stores" class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-navy-700 transition hover:border-brand-200 hover:text-brand-600">
                            View all stores <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-3" data-reveal>
                    @if($mapStores->isNotEmpty())
                        <script id="landing-stores-data" type="application/json">{!! json_encode($mapStores, JSON_HEX_TAG) !!}</script>
                        <div class="relative overflow-hidden rounded-2xl border border-slate-200 shadow-xl">
                            <div id="landing-store-map" data-map-src="{{ $mapJsSrc }}" data-map-css="{{ $mapCssHref }}"
                                class="flex h-80 w-full items-center justify-center bg-slate-100 text-sm text-slate-400 sm:h-[28rem]">
                                Loading map…
                            </div>

                            {{-- Selected-store card, floating over the map (bottom-right,
                                 matching /stores' own "here's the currently selected
                                 branch" panel). Pre-filled server-side with the first
                                 store so it's visible before the lazy map script even
                                 loads; landing-map.js updates it when another pin is
                                 clicked. --}}
                            <div id="landing-store-card" class="absolute bottom-4 right-4 z-10 max-w-[15rem] rounded-xl bg-white p-4 shadow-lg sm:max-w-xs">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l1.5-5h15L21 9" /><path d="M4 9v11h16V9" /><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" /><path d="M9 20v-5h6v5" /></svg>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p id="landing-store-name" class="truncate text-sm font-bold text-navy-800">{{ $firstStore['name'] ?? '' }}</p>
                                        <p id="landing-store-address" class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $firstStore['address'] ?? '' }}</p>
                                        {{-- always rendered (even if empty) so JS can toggle it
                                             per-store instead of the element not existing at all --}}
                                        <p id="landing-store-hours" class="mt-1 text-xs text-slate-400 {{ empty($firstStore['hours']) ? 'hidden' : '' }}">{{ $firstStore['hours'] ?? '' }}</p>
                                    </div>
                                    <a id="landing-store-directions" href="{{ $firstStore ? $dirHref($firstStore['address']) : '#' }}" target="_blank" rel="noreferrer" aria-label="Get directions"
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-navy-50 text-navy-700 transition hover:bg-brand-500 hover:text-white">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6" /></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- Newsletter — a no-op form in the original SPA too (preventDefault only, no submission) --}}
        @php $n = $content['newsletter']; $newsState = $btn('newsletterSubscribe'); @endphp
        @if($n['visible'] ?? true)
        <section id="newsletter" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="rounded-3xl border border-brand-100 bg-white px-8 py-12 text-center shadow-sm sm:px-12" data-reveal>
                @php $newsletterTypography = \App\Models\SiteContent::typographyStyle($n['typography'] ?? []); @endphp
                <h2 data-editable="newsletter.title" class="text-2xl font-bold text-navy-800 sm:text-3xl" style="{{ $newsletterTypography }}">{{ $n['title'] }}</h2>
                @if(!empty($n['subtitle']))
                    <p data-editable="newsletter.subtitle" class="mt-2 text-sm text-slate-600" style="{{ $newsletterTypography }}">{{ $n['subtitle'] }}</p>
                @endif
                @if($newsState !== 'off')
                    @php $newsOff = $newsState === 'disabled'; @endphp
                    <form onsubmit="event.preventDefault(); return false;" class="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row {{ $newsOff ? 'cursor-not-allowed opacity-60' : '' }}">
                        <input type="email" required @if($newsOff) disabled @endif placeholder="{{ $n['placeholder'] }}"
                            class="w-full rounded-full border border-slate-300 px-5 py-3 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed">
                        <button type="submit" data-editable="newsletter.buttonLabel" @if($newsOff) disabled @endif
                            style="{{ $newsletterTypography }}"
                            class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed">
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

    {{-- Floating cart button — reachable from anywhere on the page without
         scrolling back up, same fixed-FAB pattern as /menu's mobile cart
         button, but shown at every breakpoint here since landing (unlike
         /menu) has no persistent cart panel to fall back on for desktop. --}}
    <button type="button" id="cart-fab" data-open-cart aria-label="Open cart"
        class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/40 transition hover:scale-105">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
        </svg>
        <span class="mini-cart-badge absolute -right-1 -top-1 hidden h-6 min-w-6 items-center justify-center rounded-full bg-white px-1.5 text-xs font-bold text-brand-600 ring-2 ring-brand-500">0</span>
    </button>

    {{-- Landing's quick-add cart drawer + the peso/totals formula it previews with --}}
    @include('partials.mini-cart-drawer')
    @include('partials.order-pricing')

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

        // ---- mini cart (Add to Cart) ----
        // Reads/writes the exact same bw_cart localStorage /menu's cart uses,
        // so an add here shows up there and vice versa. Landing only ever
        // renders a handful of products server-side (whatsNewProducts /
        // bestSellers), so unlike /menu there's no full catalog to resolve
        // cart lines against — the full product list is fetched once, lazily,
        // the first time the drawer actually needs to render lines.
        var FALLBACK_IMG = 'https://xhy0hjgguaqll6zn.public.blob.vercel-storage.com/custom-cake-refs/1781654816384-p23ferfqoq.png';
        var peso = window.OrderPricing.peso;
        var computeTotals = window.OrderPricing.computeTotals;
        var renderTotalsHTML = window.OrderPricing.renderTotalsHTML;

        function readCart() { try { return JSON.parse(localStorage.getItem('bw_cart') || '{}') || {}; } catch (e) { return {}; } }
        function writeCart(c) { try { localStorage.setItem('bw_cart', JSON.stringify(c)); } catch (e) {} }
        var cart = readCart();

        var allProducts = null;
        var productsPromise = null;
        function ensureProducts() {
            if (allProducts) return Promise.resolve(allProducts);
            if (productsPromise) return productsPromise;
            productsPromise = fetch('/api/products').then(function (r) { return r.json(); })
                .then(function (list) { allProducts = Array.isArray(list) ? list : []; return allProducts; })
                .catch(function () { return []; });
            return productsPromise;
        }

        function cartCount() {
            return Object.keys(cart).reduce(function (s, id) { return s + cart[id]; }, 0);
        }

        // A browser can retain cart IDs after an item is removed from the
        // catalogue. Remove those stale (or malformed) entries once the live
        // catalogue is available so the badge and drawer always agree.
        function reconcileCart(products) {
            var next = {};
            Object.keys(cart).forEach(function (id) {
                var qty = Number(cart[id]);
                var exists = products.some(function (p) { return String(p.id) === id; });
                if (exists && Number.isInteger(qty) && qty > 0) next[id] = qty;
            });
            if (JSON.stringify(next) !== JSON.stringify(cart)) {
                cart = next;
                writeCart(cart);
            }
        }

        function updateBadge() {
            var n = cartCount();
            document.querySelectorAll('.mini-cart-badge, .mini-cart-count').forEach(function (el) {
                el.textContent = n;
                el.classList.toggle('hidden', n === 0);
                el.classList.toggle('flex', n > 0);
            });
        }

        function add(id, qty) {
            if (!id) return;
            cart[id] = (cart[id] || 0) + (qty || 1);
            writeCart(cart);
            updateBadge();
        }
        function dec(id) {
            if ((cart[id] || 0) <= 1) delete cart[id]; else cart[id] -= 1;
            writeCart(cart);
            updateBadge();
            renderDrawer();
            renderCardControls();
        }
        function removeLine(id) {
            delete cart[id];
            writeCart(cart);
            updateBadge();
            renderDrawer();
            renderCardControls();
        }

        function renderDrawer() {
            ensureProducts().then(function (products) {
                reconcileCart(products);
                updateBadge();
                var lines = Object.keys(cart).map(function (id) {
                    return { product: products.find(function (p) { return String(p.id) === id; }), qty: cart[id] };
                }).filter(function (l) { return l.product; });
                var subtotal = lines.reduce(function (s, l) { return s + Number(l.product.price) * l.qty; }, 0);

                document.querySelectorAll('.mini-cart-footer').forEach(function (el) { el.classList.toggle('hidden', !lines.length); });

                document.querySelectorAll('.mini-cart-list').forEach(function (list) {
                    if (!lines.length) {
                        list.innerHTML = '<div class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center"><div class="text-5xl">🛒</div><p class="mt-3 text-sm text-slate-500">Your cart is empty.<br>Add some treats to get started!</p></div>';
                        return;
                    }
                    list.innerHTML = lines.map(function (l) {
                        var p = l.product;
                        return `
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100"><img src="${p.image_path || FALLBACK_IMG}" alt="" class="h-full w-full object-cover" onerror="this.remove()"></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-navy-800">${p.name}</p>
                                <p class="text-xs text-slate-500">${peso(p.price)} each</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button type="button" data-dec="${p.id}" aria-label="Decrease quantity" class="flex h-7 w-7 items-center justify-center rounded-full bg-navy-100 text-base font-bold text-navy-800 hover:bg-brand-500 hover:text-white">&minus;</button>
                                <span class="w-4 text-center text-sm font-semibold">${l.qty}</span>
                                <button type="button" data-add="${p.id}" aria-label="Increase quantity" class="flex h-7 w-7 items-center justify-center rounded-full bg-navy-100 text-base font-bold text-navy-800 hover:bg-brand-500 hover:text-white">+</button>
                            </div>
                            <button type="button" data-remove="${p.id}" aria-label="Remove item" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            </button>
                        </li>`;
                    }).join('');
                    list.querySelectorAll('[data-add]').forEach(function (b) { b.addEventListener('click', function () { add(b.dataset.add); renderDrawer(); renderCardControls(); }); });
                    list.querySelectorAll('[data-dec]').forEach(function (b) { b.addEventListener('click', function () { dec(b.dataset.dec); }); });
                    list.querySelectorAll('[data-remove]').forEach(function (b) { b.addEventListener('click', function () { removeLine(b.dataset.remove); }); });
                });

                var t = computeTotals({ subtotal: subtotal });
                document.querySelectorAll('.mini-cart-totals').forEach(function (el) { el.innerHTML = renderTotalsHTML(t, { showFreeDeliveryHint: true }); });
            });
        }

        function openDrawer() { document.getElementById('mini-cart-drawer').classList.remove('hidden'); renderDrawer(); }

        // Product-card "+" buttons (What's New / Best Sellers grids) — same
        // "In cart · N" pill treatment as /menu's qtyControls(), so clicking
        // + gives the same "yes, that's in your cart now" confirmation
        // instead of silently doing nothing visible on the card itself.
        // Re-run after every add/remove so every card on the page (not just
        // the one just clicked) stays in sync with the shared cart.
        function renderCardControls() {
            document.querySelectorAll('[data-cart-control]').forEach(function (el) {
                var id = el.dataset.cartControl;
                var qty = cart[id] || 0;
                el.innerHTML = qty === 0
                    ? `<button type="button" data-add-to-cart="${id}" aria-label="Add to cart" class="flex h-9 w-9 items-center justify-center rounded-full bg-navy-800 text-white transition hover:bg-brand-600"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg></button>`
                    : `<div class="flex items-center gap-1.5">
                        <span class="flex items-center gap-1 whitespace-nowrap rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-600">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                            In cart · ${qty}
                        </span>
                        <button type="button" data-add-to-cart="${id}" aria-label="Add another" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-navy-100 text-base font-bold text-navy-800 hover:bg-brand-500 hover:text-white">+</button>
                       </div>`;
                var btn = el.querySelector('[data-add-to-cart]');
                if (btn) btn.addEventListener('click', function () { add(id, 1); renderCardControls(); openDrawer(); });
            });
        }

        document.querySelectorAll('[data-open-cart]').forEach(function (btn) { btn.addEventListener('click', openDrawer); });

        // The FAB is fixed to the viewport, not the page, so once the visitor
        // scrolls all the way down it has nowhere left to "avoid" — it just
        // sits on top of the footer's copyright line forever. Fade it out
        // whenever the footer is actually on screen instead.
        var cartFab = document.getElementById('cart-fab');
        var footerEl = document.getElementById('site-footer');
        if (cartFab && footerEl && 'IntersectionObserver' in window) {
            var fabObserver = new IntersectionObserver(function (entries) {
                var overFooter = entries.some(function (e) { return e.isIntersecting; });
                cartFab.classList.toggle('opacity-0', overFooter);
                cartFab.classList.toggle('pointer-events-none', overFooter);
            });
            fabObserver.observe(footerEl);
        }
        // Proceeding to checkout hands off via bw_checkout, same as /menu's
        // .checkout-btn — only meaningful once there's a real session to
        // place the order under (see mini-cart-drawer.blade.php's $user gate).
        document.querySelectorAll('.mini-checkout-btn').forEach(function (a) {
            a.addEventListener('click', function () {
                ensureProducts().then(function (products) {
                    var items = Object.keys(cart).map(function (id) {
                        var p = products.find(function (x) { return String(x.id) === id; });
                        return p ? { product_id: p.id, name: p.name, qty: cart[id], img: p.image_path, price: p.price } : null;
                    }).filter(Boolean);
                    try { localStorage.setItem('bw_checkout', JSON.stringify({ items: items, voucher: null })); } catch (e) {}
                });
            });
        });

        // Resolve the full catalogue in the background as well, so a stale
        // badge is corrected even if the customer never opens the drawer.
        ensureProducts().then(function (products) {
            reconcileCart(products);
            updateBadge();
            renderCardControls();
        });
        updateBadge();
        renderCardControls();

        // Shared product detail modal (partials/product-modal.blade.php),
        // populated from whichever card's data-* attrs was clicked. Only the
        // price + qty-stepper + "Add to cart" footer is landing-specific; the
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
                <button type="button" data-add-to-cart-modal class="mt-5 flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-8 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /><path d="M16 10v-4" /><path d="M14 8h4" /></svg> Add to cart
                </button>`;
            var qtyEl = footer.querySelector('[data-qty]');
            var setQty = function (q) { qty = Math.max(1, q); qtyEl.textContent = qty; };
            footer.querySelector('[data-qty-minus]').addEventListener('click', function () { setQty(qty - 1); });
            footer.querySelector('[data-qty-plus]').addEventListener('click', function () { setQty(qty + 1); });
            footer.querySelector('[data-add-to-cart-modal]').addEventListener('click', function () {
                add(d.id, qty);
                renderCardControls();
                pmModal.close();
                openDrawer();
            });
            setQty(1);
        };

        document.querySelectorAll('.product-card-trigger').forEach(function (el) {
            var open = function () {
                pmModal.open({
                    id: el.dataset.id,
                    img: el.dataset.img,
                    name: el.dataset.name,
                    badge: el.dataset.tag,
                    desc: el.dataset.desc,
                    calorieText: el.dataset.calorieText,
                    allergens: el.dataset.allergens,
                    netWeight: el.dataset.netWeight,
                    storageCondition: el.dataset.storageCondition,
                    servingNote: el.dataset.servingNote,
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

        // Store locator map: loaded lazily. maplibre-gl is a ~290KB (gzipped)
        // dependency — fetching it on every landing page load regardless of
        // whether anyone scrolls this far would undercut the whole point of
        // a fast landing page, so the actual module is only import()'d once
        // the section is about to enter the viewport (see LandingController's
        // mapJsSrc/mapCssHref — resolved by hand since Blade's Vite directive
        // always emits its tags eagerly, which is exactly what this avoids).
        var mapEl = document.getElementById('landing-store-map');
        if (mapEl && mapEl.dataset.mapSrc && 'IntersectionObserver' in window) {
            var loadMap = function () {
                if (mapEl.dataset.mapCss) {
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = mapEl.dataset.mapCss;
                    document.head.appendChild(link);
                }
                mapEl.textContent = '';
                import(mapEl.dataset.mapSrc);
            };
            var mapObserver = new IntersectionObserver(function (entries) {
                if (entries.some(function (e) { return e.isIntersecting; })) {
                    mapObserver.disconnect();
                    loadMap();
                }
            }, { rootMargin: '400px 0px' });
            mapObserver.observe(mapEl);
        }
    })();
    </script>
@endif
@if($editable ?? false)
    @include('partials._editor-bridge')
@endif
</body>
</html>
