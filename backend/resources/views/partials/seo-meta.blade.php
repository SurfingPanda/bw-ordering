{{-- Shared SEO tag block: title, description, canonical, Open Graph, Twitter
     card, and (optional) JSON-LD structured data. Include right after the
     viewport meta tag. Each page's own top-of-file PHP block sets
     $metaTitle/$metaDescription (required); everything else has a safe
     fallback so a page that forgets a value still gets a correct tag
     instead of none. --}}
@php
    $seoSiteUrl = rtrim(config('app.url'), '/');
    $canonical = $canonical ?? $seoSiteUrl.request()->getPathInfo();
    $ogImage = $ogImage ?? $seoSiteUrl.'/images/promo-cake.png';
    $ogType = $ogType ?? 'website';
@endphp
<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="bw Superbakeshop">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:locale" content="en_PH">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@isset($jsonLd)
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endisset
