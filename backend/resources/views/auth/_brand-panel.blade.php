{{-- Left brand panel shared by the auth pages (port of BrandPanel.jsx).
     Var: $authPanel — the CMS content.authPanel blob (may be empty/missing);
     defaults mirror Admin\SiteContentController::defaults()['authPanel']. --}}
@php
    $ap = array_merge([
        'logo' => '/images/logo (1).png',
        'tagline' => '',
        'script' => '',
        'image' => '/images/Full Moymoy 2.png',
    ], $authPanel ?? []);
@endphp
<div class="relative hidden flex-col overflow-hidden bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 lg:flex">
    <div class="pointer-events-none absolute inset-0 select-none text-5xl leading-[3.5rem] opacity-[0.06]">
        <div class="absolute left-6 top-10">🥐</div>
        <div class="absolute right-10 top-16">🧁</div>
        <div class="absolute left-16 top-40">🍞</div>
        <div class="absolute right-6 top-44">🥖</div>
        <div class="absolute left-8 top-72">🥨</div>
        <div class="absolute right-16 top-80">🍰</div>
    </div>
    <div class="relative z-10 flex flex-col items-center px-10 pb-10 pt-12 text-center text-white">
        <img src="{{ $ap['logo'] }}" alt="BW Superbakeshop" class="h-48 w-auto drop-shadow-lg">
        @php $authPanelTypography = \App\Models\SiteContent::typographyStyle($ap['typography'] ?? []); @endphp
        @if($ap['tagline'])
            <p class="mt-6 text-sm font-medium text-navy-50/90" style="{{ $authPanelTypography }}">{{ $ap['tagline'] }}</p>
        @endif
        @if($ap['script'])
            <p class="font-script text-xl text-brand-400" style="{{ $authPanelTypography }}">{{ $ap['script'] }}</p>
        @endif
    </div>
    @if($ap['image'])
        <img src="{{ $ap['image'] }}" alt="" loading="lazy" decoding="async" class="relative z-10 mt-auto w-full max-w-md self-center px-6 pb-6 drop-shadow-2xl">
    @endif
    <svg class="absolute right-[-1px] top-0 h-full w-10 text-white" viewBox="0 0 40 600" preserveAspectRatio="none" fill="currentColor">
        <path d="M40 0 C40 130 8 200 8 300 C8 400 40 470 40 600 Z" />
    </svg>
</div>
