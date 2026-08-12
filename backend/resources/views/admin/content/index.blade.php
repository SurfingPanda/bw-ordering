@extends('layouts.site-editor')

@section('title', 'Site Editor')

@section('header-actions')
    {{-- Save submits the single big form below (form= attribute — the buttons
         live outside it in the shell header). Every managed section's inputs
         are always in the DOM (tabs only show/hide), so one save persists
         everything, same as the old editor's single "Save changes" button.
         Save/Reset stay hidden until something is actually edited. --}}
    @include('admin.content._save-reset', ['formId' => 'content-form'])
@endsection

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20';
    $panel = 'hidden rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6';
    $addBtn = 'mt-4 w-full rounded-xl border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600';

    // On a failed save, Laravel flashes the submitted section values back via
    // old() — merge them over $content (a shallow, top-level merge; each key
    // here is one whole section's worth of fields, submitted as one blob, so
    // this restores exactly what was resubmitted). Without this, a bad value
    // in ONE section used to wipe every edit across all 14 sections, since
    // every @php extraction below reads from $content. old() is empty on a
    // normal page load, so this is a no-op then.
    if ($old = old()) {
        $content = array_merge($content, $old);
    }
@endphp

@section('editor-nav')
    {{-- activeSection null: this page's tab JS drives the highlight. --}}
    @include('admin.content._editor-nav', ['activeSection' => null])
@endsection

@section('preview-label', 'Live preview — updates as you edit')
@section('preview')
    @include('admin.content._preview', ['url' => '/'])
@endsection

@section('content')
    <form id="content-form" method="POST" action="{{ route('admin.content.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="section" id="content-section-field" value="">

        @if($errors->any())
            {{-- Shown regardless of which tab is active — the offending
                 field(s) may be in a different, currently-hidden panel; the
                 script at the bottom switches to it and highlights the field. --}}
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="mb-1 font-semibold">Couldn't save — fix the highlighted field{{ $errors->count() > 1 ? 's' : '' }} below and save again.</p>
                <ul class="list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            {{-- Field names with errors (e.g. "franchise.email"), for the
                 script at the bottom to switch to the right tab and
                 highlight the field. --}}
            <script id="content-form-error-fields" type="application/json">{!! json_encode($errors->keys()) !!}</script>
        @endif

        {{-- ============ Navigation Bar ============ --}}
        <section data-panel="nav" class="{{ $panel }}">
            @php($navContent = (array) ($content['nav'] ?? []))
            @php($navLinks = (array) ($navContent['links'] ?? []))
            <h2 class="text-lg font-bold text-navy-800">Navigation Bar</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The logo and nav bar background color, shared across every page's header. The three link destinations below only apply to the landing page's full nav (Menu / Store / Partner with us) — leave a field blank to use its default page.</p>
            <div class="space-y-3">
                @include('admin.content._image-field', ['name' => 'nav[logo]', 'value' => $navContent['logo'] ?? '', 'fieldLabel' => 'Logo'])
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Nav bar color</span>
                    <div data-typography-color class="flex items-center gap-2">
                        <input type="color" data-color-swatch value="{{ $navContent['color'] ?? '#083caa' }}" title="Pick the nav bar's background color" class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-0.5">
                        <input type="text" name="nav[color]" data-color-hex value="{{ $navContent['color'] ?? '#083caa' }}" maxlength="7" placeholder="#083caa" class="w-24 shrink-0 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-mono uppercase outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                    </div>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">"Menu" link</span>
                    <input type="text" name="nav[links][menu]" value="{{ $navLinks['menu'] ?? '' }}" placeholder="/menu?category=What's New" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">"Store" link</span>
                    <input type="text" name="nav[links][store]" value="{{ $navLinks['store'] ?? '' }}" placeholder="/stores" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">"Partner with us" link</span>
                    <input type="text" name="nav[links][franchise]" value="{{ $navLinks['franchise'] ?? '' }}" placeholder="/franchise" class="{{ $input }}">
                </label>
            </div>
        </section>

        {{-- ============ Announcement ============ --}}
        <section data-panel="announcement" class="{{ $panel }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy-800">Announcement Bar</h2>
                    <p class="mb-5 mt-0.5 text-sm text-slate-500">The thin orange strip shown at the very top of every page.</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                    <span class="text-xs font-medium text-slate-500">Show on page</span>
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                        <input type="hidden" name="announcementVisible" value="0">
                        <input type="checkbox" name="announcementVisible" value="1" class="peer sr-only" @checked($content['announcementVisible'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>
            <textarea name="announcement" id="announcement-input" rows="2"
                class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">{{ $content['announcement'] ?? '' }}</textarea>
            <p class="mb-1 mt-3 text-xs font-medium text-slate-500">Live preview</p>
            <div id="announcement-preview" class="overflow-hidden rounded-lg bg-navy-900 px-4 py-2 text-center text-xs font-medium text-white">
                {{ ($content['announcement'] ?? '') !== '' ? $content['announcement'] : '—' }}
            </div>
            @include('admin.content._typography-panel', ['name' => 'announcementTypography', 'value' => $content['announcementTypography'] ?? []])
        </section>

        {{-- ============ Promo Banners ============ --}}
        <section data-panel="banners" class="{{ $panel }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy-800">Promotional Banners</h2>
                    <p class="mb-5 mt-0.5 text-sm text-slate-500">The big rotating carousel. Best image size: 1920 × 800 px (2.4:1).</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                    <span class="text-xs font-medium text-slate-500">Show on page</span>
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                        <input type="hidden" name="bannersVisible" value="0">
                        <input type="checkbox" name="bannersVisible" value="1" class="peer sr-only" @checked($content['bannersVisible'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>
            <div data-repeater>
                <div data-rows class="grid gap-4 xl:grid-cols-2">
                    @foreach(array_values((array) ($content['banners'] ?? [])) as $i => $item)
                        @include('admin.content._banner-row')
                    @endforeach
                </div>
                <template>@include('admin.content._banner-row', ['i' => '__IDX__', 'item' => []])</template>
                <button type="button" data-add class="{{ $addBtn }}">+ Add banner</button>
            </div>
        </section>

        {{-- ============ What's New ============ --}}
        <section data-panel="whatsNew" class="{{ $panel }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy-800">What's New</h2>
                    <p class="mb-5 mt-0.5 text-sm text-slate-500">Heading of the “What’s New?” section. The product cards come from the live catalogue automatically — set a product’s Status to “New” in the Products section to feature it here.</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                    <span class="text-xs font-medium text-slate-500">Show on page</span>
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                        <input type="hidden" name="whatsNew[visible]" value="0">
                        <input type="checkbox" name="whatsNew[visible]" value="1" class="peer sr-only" @checked($content['whatsNew']['visible'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                    <input type="text" name="whatsNew[eyebrow]" value="{{ $content['whatsNew']['eyebrow'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                    <input type="text" name="whatsNew[title]" value="{{ $content['whatsNew']['title'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                    <textarea name="whatsNew[subtitle]" rows="3" class="{{ $input }}">{{ $content['whatsNew']['subtitle'] ?? '' }}</textarea>
                </label>
            </div>
            @include('admin.content._typography-panel', ['name' => 'whatsNew[typography]', 'value' => $content['whatsNew']['typography'] ?? []])
        </section>

        {{-- ============ Best Sellers ============ --}}
        <section data-panel="bestSellersSection" class="{{ $panel }}">
            @php($bs = (array) ($content['bestSellersSection'] ?? []))
            <h2 class="text-lg font-bold text-navy-800">Best Sellers</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The "Our Best Sellers" section on the landing page. The product cards come from the live catalogue automatically — set a product's Status to "Best seller" in the Products section to feature it here. Section is hidden entirely when no product is flagged.</p>
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                    <input type="text" name="bestSellersSection[eyebrow]" value="{{ $bs['eyebrow'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                    <input type="text" name="bestSellersSection[title]" value="{{ $bs['title'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                    <textarea name="bestSellersSection[subtitle]" rows="3" class="{{ $input }}">{{ $bs['subtitle'] ?? '' }}</textarea>
                </label>
                @include('admin.content._image-field', ['name' => 'bestSellersSection[backgroundImage]', 'value' => $bs['backgroundImage'] ?? '', 'fieldLabel' => 'Section background image (shown as uploaded, no dimming)', 'wide' => true])
            </div>
        </section>

        {{-- ============ Categories Heading ============ --}}
        <section data-panel="categoriesSection" class="{{ $panel }}">
            @php($cs = (array) ($content['categoriesSection'] ?? []))
            <h2 class="text-lg font-bold text-navy-800">Categories Heading</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The "What are you craving today?" heading on the landing page, above the category tiles. The tiles themselves (images, which categories exist) are managed in the Menu Categories section.</p>
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                    <input type="text" name="categoriesSection[eyebrow]" value="{{ $cs['eyebrow'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                    <input type="text" name="categoriesSection[title]" value="{{ $cs['title'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                    <textarea name="categoriesSection[subtitle]" rows="3" class="{{ $input }}">{{ $cs['subtitle'] ?? '' }}</textarea>
                </label>
            </div>
        </section>

        {{-- ============ Custom Cake ============ --}}
        <section data-panel="customCake" class="{{ $panel }}">
            @php($cc = (array) ($content['customCake'] ?? []))
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy-800">Custom Cake Banner</h2>
                    <p class="mb-5 mt-0.5 text-sm text-slate-500">The big orange promo banner on the landing page. Clicking the banner opens the menu; the “Order a custom cake” button is shown/hidden in the Buttons section (promoOrder). Cake image: a transparent PNG works best, around 1200 × 900 px.</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                    <span class="text-xs font-medium text-slate-500">Show on page</span>
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                        <input type="hidden" name="customCake[visible]" value="0">
                        <input type="checkbox" name="customCake[visible]" value="1" class="peer sr-only" @checked($cc['visible'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>
            <div class="space-y-3">
                @include('admin.content._image-field', ['name' => 'customCake[backgroundImage]', 'value' => $cc['backgroundImage'] ?? '', 'fieldLabel' => 'Section background image (behind Menu Categories above and this banner)', 'wide' => true])
                @php($ccBgOpacity = ($cc['backgroundOpacity'] ?? '') !== '' ? (int) $cc['backgroundOpacity'] : 100)
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Background image opacity (<span data-opacity-readout>{{ $ccBgOpacity }}</span>%)</span>
                    <input type="range" min="0" max="100" name="customCake[backgroundOpacity]" value="{{ $ccBgOpacity }}" data-opacity-range class="w-full accent-brand-500">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Card color</span>
                    <div data-typography-color class="flex items-center gap-2">
                        <input type="color" data-color-swatch value="{{ $cc['backgroundColor'] ?? '#ef7d1a' }}" title="Pick the card's background color" class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-0.5">
                        <input type="text" name="customCake[backgroundColor]" data-color-hex value="{{ $cc['backgroundColor'] ?? '#ef7d1a' }}" maxlength="7" placeholder="#ef7d1a" class="w-24 shrink-0 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-mono uppercase outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <span class="text-xs text-slate-400">The banner card itself — the image above is the backdrop behind the whole section.</span>
                    </div>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                    <input type="text" name="customCake[eyebrow]" value="{{ $cc['eyebrow'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                    <input type="text" name="customCake[title]" value="{{ $cc['title'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                    <textarea name="customCake[subtitle]" rows="3" class="{{ $input }}">{{ $cc['subtitle'] ?? '' }}</textarea>
                </label>
                @include('admin.content._image-field', ['name' => 'customCake[image]', 'value' => $cc['image'] ?? '', 'fieldLabel' => 'Cake image', 'wide' => true])
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Image alt text</span>
                    <input type="text" name="customCake[alt]" value="{{ $cc['alt'] ?? '' }}" class="{{ $input }}">
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Button label</span>
                        <input type="text" name="customCake[buttonLabel]" value="{{ $cc['buttonLabel'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Button link</span>
                        <input type="text" name="customCake[buttonLink]" value="{{ $cc['buttonLink'] ?? '' }}" placeholder="/custom-cake" class="{{ $input }}">
                    </label>
                </div>
            </div>
            @include('admin.content._typography-panel', ['name' => 'customCake[typography]', 'value' => $cc['typography'] ?? []])
        </section>

        {{-- ============ Custom Cake Page (the /custom-cake wizard) ============ --}}
        <section data-panel="customCakeForm" class="{{ $panel }}">
            <h2 class="text-lg font-bold text-navy-800">Custom Cake Page</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The “Customize Your Cake” inquiry wizard at /custom-cake — its heading and every dropdown/swatch option. List fields are one option per line.</p>
            @php($ccf = (array) ($content['customCakeForm'] ?? []))
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                    <input type="text" name="customCakeForm[eyebrow]" value="{{ $ccf['eyebrow'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                    <input type="text" name="customCakeForm[title]" value="{{ $ccf['title'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                    <textarea name="customCakeForm[subtitle]" rows="3" class="{{ $input }}">{{ $ccf['subtitle'] ?? '' }}</textarea>
                </label>
                @include('admin.content._typography-panel', ['name' => 'customCakeForm[typography]', 'value' => $ccf['typography'] ?? []])
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <span class="mb-1 block text-xs font-medium text-slate-500">Occasions</span>
                        <div data-repeater>
                            <div data-rows class="space-y-2">
                                @foreach((array) ($ccf['occasions'] ?? []) as $i => $occ)
                                    <div data-row class="flex items-center gap-1.5">
                                        <input type="text" name="customCakeForm[occasions][{{ $i }}]" value="{{ $occ }}" class="{{ $input }}">
                                        <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
                                        <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
                                        <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                                    </div>
                                @endforeach
                            </div>
                            <template>
                                <div data-row class="flex items-center gap-1.5">
                                    <input type="text" name="customCakeForm[occasions][__IDX__]" value="" placeholder="e.g. Christening" class="{{ $input }}">
                                    <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
                                    <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
                                    <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                                </div>
                            </template>
                            <button type="button" data-add class="mt-2 w-full rounded-xl border-2 border-dashed border-slate-300 py-2 text-xs font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">+ Add occasion</button>
                        </div>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs font-medium text-slate-500">Sizes</span>
                        <div data-repeater>
                            <div data-rows class="space-y-2">
                                @foreach((array) ($ccf['sizes'] ?? []) as $i => $sz)
                                    <div data-row class="flex items-center gap-1.5">
                                        <input type="text" name="customCakeForm[sizes][{{ $i }}]" value="{{ $sz }}" class="{{ $input }}">
                                        <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
                                        <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
                                        <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                                    </div>
                                @endforeach
                            </div>
                            <template>
                                <div data-row class="flex items-center gap-1.5">
                                    <input type="text" name="customCakeForm[sizes][__IDX__]" value="" placeholder="e.g. 12&quot; Round (serves 30)" class="{{ $input }}">
                                    <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
                                    <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
                                    <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                                </div>
                            </template>
                            <button type="button" data-add class="mt-2 w-full rounded-xl border-2 border-dashed border-slate-300 py-2 text-xs font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">+ Add size</button>
                        </div>
                        <span class="mt-1 block text-xs text-slate-400">Tip: “2-Tier” / “3-Tier” stack tiers in the page's cake preview; a leading inch number (e.g. 8") sets its width.</span>
                    </div>
                    <div>
                        <span class="mb-1 block text-xs font-medium text-slate-500">Flavors</span>
                        <div data-repeater>
                            <div data-rows class="space-y-2">
                                @foreach((array) ($ccf['flavors'] ?? []) as $i => $fl)
                                    <div data-row class="flex items-center gap-1.5">
                                        <input type="text" name="customCakeForm[flavors][{{ $i }}]" value="{{ $fl }}" class="{{ $input }}">
                                        <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
                                        <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
                                        <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                                    </div>
                                @endforeach
                            </div>
                            <template>
                                <div data-row class="flex items-center gap-1.5">
                                    <input type="text" name="customCakeForm[flavors][__IDX__]" value="" placeholder="e.g. Salted Caramel" class="{{ $input }}">
                                    <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
                                    <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
                                    <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                                </div>
                            </template>
                            <button type="button" data-add class="mt-2 w-full rounded-xl border-2 border-dashed border-slate-300 py-2 text-xs font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">+ Add flavor</button>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="mb-1 block text-xs font-medium text-slate-500">Frosting colors</span>
                    <div data-repeater>
                        <div data-rows class="grid gap-2 sm:grid-cols-2">
                            @foreach((array) ($ccf['colors'] ?? []) as $i => $c)
                                @php($c = (array) $c)
                                <div data-row class="flex items-center gap-1.5">
                                    <input type="color" name="customCakeForm[colors][{{ $i }}][hex]" value="{{ $c['hex'] ?? '#fbe3c4' }}" title="Pick the swatch color" class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-0.5">
                                    <input type="text" name="customCakeForm[colors][{{ $i }}][name]" value="{{ $c['name'] ?? '' }}" placeholder="Color name" class="{{ $input }}">
                                    <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <template>
                            <div data-row class="flex items-center gap-1.5">
                                <input type="color" name="customCakeForm[colors][__IDX__][hex]" value="#fbe3c4" title="Pick the swatch color" class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-0.5">
                                <input type="text" name="customCakeForm[colors][__IDX__][name]" value="" placeholder="Color name" class="{{ $input }}">
                                <button type="button" data-remove aria-label="Remove" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-sm font-semibold text-red-600 transition hover:bg-red-50">✕</button>
                            </div>
                        </template>
                        <button type="button" data-add class="mt-2 w-full rounded-xl border-2 border-dashed border-slate-300 py-2 text-xs font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">+ Add color</button>
                    </div>
                    <span class="mt-1 block text-xs text-slate-400">The color paints the swatch and the preview cake on /custom-cake.</span>
                </div>
            </div>
        </section>

        {{-- ============ Store Locator ============ --}}
        <section data-panel="storeLocator" class="{{ $panel }}">
            @php($sl = (array) ($content['storeLocator'] ?? []))
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy-800">Store Locator</h2>
                    <p class="mb-5 mt-0.5 text-sm text-slate-500">The “stores near you” section on the landing page — this copy and search box sit next to a live map of your actual branches (pulled from Find a Store, not editable here). The “Find a store” button itself is shown/hidden in the Buttons section (storeLocatorFind).</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                    <span class="text-xs font-medium text-slate-500">Show on page</span>
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                        <input type="hidden" name="storeLocator[visible]" value="0">
                        <input type="checkbox" name="storeLocator[visible]" value="1" class="peer sr-only" @checked($sl['visible'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                    <input type="text" name="storeLocator[title]" value="{{ $sl['title'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                    <textarea name="storeLocator[subtitle]" rows="3" class="{{ $input }}">{{ $sl['subtitle'] ?? '' }}</textarea>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Search placeholder</span>
                    <input type="text" name="storeLocator[placeholder]" value="{{ $sl['placeholder'] ?? '' }}" class="{{ $input }}">
                </label>
            </div>
            @include('admin.content._typography-panel', ['name' => 'storeLocator[typography]', 'value' => $sl['typography'] ?? []])
        </section>

        {{-- ============ Sweet Deals (newsletter) ============ --}}
        <section data-panel="newsletter" class="{{ $panel }}">
            @php($nl = (array) ($content['newsletter'] ?? []))
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy-800">Sweet Deals (Newsletter)</h2>
                    <p class="mb-5 mt-0.5 text-sm text-slate-500">The “Get sweet deals in your inbox” newsletter section near the bottom of the landing page. The Subscribe button is shown/hidden in the Buttons section (newsletterSubscribe).</p>
                </div>
                <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                    <span class="text-xs font-medium text-slate-500">Show on page</span>
                    <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                        <input type="hidden" name="newsletter[visible]" value="0">
                        <input type="checkbox" name="newsletter[visible]" value="1" class="peer sr-only" @checked($nl['visible'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                    <input type="text" name="newsletter[title]" value="{{ $nl['title'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                    <input type="text" name="newsletter[subtitle]" value="{{ $nl['subtitle'] ?? '' }}" class="{{ $input }}">
                </label>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Input placeholder</span>
                        <input type="text" name="newsletter[placeholder]" value="{{ $nl['placeholder'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Button label</span>
                        <input type="text" name="newsletter[buttonLabel]" value="{{ $nl['buttonLabel'] ?? '' }}" class="{{ $input }}">
                    </label>
                </div>
            </div>
            @include('admin.content._typography-panel', ['name' => 'newsletter[typography]', 'value' => $nl['typography'] ?? []])
        </section>

        {{-- ============ Franchise ============ --}}
        <section data-panel="franchise" class="hidden space-y-5">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                @php($fr = (array) ($content['franchise'] ?? []))
                @php($frHero = (array) ($fr['hero'] ?? []))
                @php($frVis = (array) ($fr['visible'] ?? []))
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">Franchise — Hero</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">The top of the /franchise page.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="franchise[visible][hero]" value="0">
                            <input type="checkbox" name="franchise[visible][hero]" value="1" class="peer sr-only" @checked($frVis['hero'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                        <input type="text" name="franchise[hero][eyebrow]" value="{{ $frHero['eyebrow'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                        <input type="text" name="franchise[hero][title]" value="{{ $frHero['title'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                        <textarea name="franchise[hero][subtitle]" rows="3" class="{{ $input }}">{{ $frHero['subtitle'] ?? '' }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Inquiry email</span>
                        <input type="email" name="franchise[email]" value="{{ $fr['email'] ?? '' }}" class="{{ $input }}">
                    </label>
                    @include('admin.content._typography-panel', ['name' => 'franchise[hero][typography]', 'value' => $frHero['typography'] ?? []])
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">Franchise — Trust Stats</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">A numbers strip under the hero (e.g. branch count, years in business, payback period). Only shows once at least one stat is added — use real figures only.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="franchise[visible][stats]" value="0">
                            <input type="checkbox" name="franchise[visible][stats]" value="1" class="peer sr-only" @checked($frVis['stats'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div data-repeater>
                    <div data-rows class="space-y-2">
                        @foreach(array_values((array) ($fr['stats'] ?? [])) as $i => $item)
                            @include('admin.content._stat-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._stat-row', ['i' => '__IDX__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add stat</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">Franchise — Perks</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">The “Why franchise with us” cards.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="franchise[visible][perks]" value="0">
                            <input type="checkbox" name="franchise[visible][perks]" value="1" class="peer sr-only" @checked($frVis['perks'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div data-repeater>
                    <div data-rows class="space-y-2">
                        @foreach(array_values((array) ($fr['perks'] ?? [])) as $i => $item)
                            @include('admin.content._perk-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._perk-row', ['i' => '__IDX__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add perk</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">Franchise — Testimonials</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">Quotes from real franchisees. Only shows once at least one testimonial is added — don't invent quotes or names.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="franchise[visible][testimonials]" value="0">
                            <input type="checkbox" name="franchise[visible][testimonials]" value="1" class="peer sr-only" @checked($frVis['testimonials'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div data-repeater>
                    <div data-rows class="space-y-2">
                        @foreach(array_values((array) ($fr['testimonials'] ?? [])) as $i => $item)
                            @include('admin.content._testimonial-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._testimonial-row', ['i' => '__IDX__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add testimonial</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">Franchise — Steps</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">The “How it works” path.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="franchise[visible][steps]" value="0">
                            <input type="checkbox" name="franchise[visible][steps]" value="1" class="peer sr-only" @checked($frVis['steps'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div data-repeater>
                    <div data-rows class="space-y-2">
                        @foreach(array_values((array) ($fr['steps'] ?? [])) as $i => $item)
                            @include('admin.content._step-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._step-row', ['i' => '__IDX__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add step</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy-800">Franchise — Packages</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">The franchise package cards.</p>
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-navy-800">Show packages section</p>
                        <p class="mt-0.5 text-xs text-slate-500">When off, the packages section (and the hero's “View packages” button) is hidden on the /franchise page.</p>
                    </div>
                    <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                        <input type="checkbox" name="franchise[packagesEnabled]" value="1" class="peer sr-only" @checked(! empty($fr['packagesEnabled'] ?? true))>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
                <div data-repeater>
                    <div data-rows class="space-y-2">
                        @foreach(array_values((array) ($fr['packages'] ?? [])) as $i => $item)
                            @include('admin.content._package-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._package-row', ['i' => '__IDX__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add package</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">Franchise — FAQs</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">Common questions and answers, shown above the closing CTA. Only shows once at least one FAQ is added.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="franchise[visible][faqs]" value="0">
                            <input type="checkbox" name="franchise[visible][faqs]" value="1" class="peer sr-only" @checked($frVis['faqs'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div data-repeater>
                    <div data-rows class="space-y-2">
                        @foreach(array_values((array) ($fr['faqs'] ?? [])) as $i => $item)
                            @include('admin.content._faq-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._faq-row', ['i' => '__IDX__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add FAQ</button>
                </div>
            </div>
        </section>

        {{-- ============ About Page ============ --}}
        <section data-panel="about" class="hidden space-y-5">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                @php($ab = (array) ($content['about'] ?? []))
                @php($abHero = (array) ($ab['hero'] ?? []))
                @php($abStory = (array) ($ab['story'] ?? []))
                @php($abValues = (array) ($ab['values'] ?? []))
                @php($abVis = (array) ($ab['visible'] ?? []))
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">About — Hero</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">The top of the /about page.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="about[visible][hero]" value="0">
                            <input type="checkbox" name="about[visible][hero]" value="1" class="peer sr-only" @checked($abVis['hero'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                        <input type="text" name="about[hero][eyebrow]" value="{{ $abHero['eyebrow'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Title</span>
                        <input type="text" name="about[hero][title]" value="{{ $abHero['title'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                        <textarea name="about[hero][subtitle]" rows="3" class="{{ $input }}">{{ $abHero['subtitle'] ?? '' }}</textarea>
                    </label>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">About — Our Story</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">The two-column story section with the mascot photo.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="about[visible][story]" value="0">
                            <input type="checkbox" name="about[visible][story]" value="1" class="peer sr-only" @checked($abVis['story'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                        <input type="text" name="about[story][eyebrow]" value="{{ $abStory['eyebrow'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Heading</span>
                        <input type="text" name="about[story][heading]" value="{{ $abStory['heading'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Paragraph 1</span>
                        <textarea name="about[story][paragraph1]" rows="4" class="{{ $input }}">{{ $abStory['paragraph1'] ?? '' }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Paragraph 2</span>
                        <textarea name="about[story][paragraph2]" rows="4" class="{{ $input }}">{{ $abStory['paragraph2'] ?? '' }}</textarea>
                    </label>
                    @include('admin.content._image-field', ['name' => 'about[story][image]', 'value' => $abStory['image'] ?? '', 'fieldLabel' => 'Photo'])
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-800">About — Values</h2>
                        <p class="mb-5 mt-0.5 text-sm text-slate-500">The "values behind every bake" cards.</p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                        <span class="text-xs font-medium text-slate-500">Show on page</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                            <input type="hidden" name="about[visible][values]" value="0">
                            <input type="checkbox" name="about[visible][values]" value="1" class="peer sr-only" @checked($abVis['values'] ?? true)>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <div class="mb-5 space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Eyebrow</span>
                        <input type="text" name="about[values][eyebrow]" value="{{ $abValues['eyebrow'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Heading</span>
                        <input type="text" name="about[values][heading]" value="{{ $abValues['heading'] ?? '' }}" class="{{ $input }}">
                    </label>
                </div>
                <div data-repeater>
                    <div data-rows class="space-y-2">
                        @foreach(array_values((array) ($abValues['items'] ?? [])) as $i => $item)
                            @include('admin.content._about-value-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._about-value-row', ['i' => '__IDX__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add value</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                @php($abCta = (array) ($ab['cta'] ?? []))
                <h2 class="text-lg font-bold text-navy-800">About — Closing Banner</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">The "Come taste the difference" card at the bottom of the page.</p>
                <div class="space-y-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Heading</span>
                        <input type="text" name="about[cta][heading]" value="{{ $abCta['heading'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Subtitle</span>
                        <textarea name="about[cta][subtitle]" rows="2" class="{{ $input }}">{{ $abCta['subtitle'] ?? '' }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Card color</span>
                        <div data-typography-color class="flex items-center gap-2">
                            <input type="color" data-color-swatch value="{{ $abCta['backgroundColor'] ?? '#083caa' }}" title="Pick the card's background color" class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-0.5">
                            <input type="text" name="about[cta][backgroundColor]" data-color-hex value="{{ $abCta['backgroundColor'] ?? '#083caa' }}" maxlength="7" placeholder="#083caa" class="w-24 shrink-0 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-mono uppercase outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </div>
                    </label>
                </div>
            </div>
        </section>

        {{-- ============ Footer ============ --}}
        <section data-panel="footer" class="hidden space-y-5">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy-800">Footer</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">The site-wide footer shown at the bottom of every public page — brand, tagline, link columns, and copyright. Social icons live in the Social Links section.</p>
                @php($fo = (array) ($content['footer'] ?? []))
                <div class="space-y-4">
                    @include('admin.content._image-field', ['name' => 'footer[logo]', 'value' => $fo['logo'] ?? '', 'fieldLabel' => 'Logo'])
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
                        <textarea name="footer[description]" rows="3" class="{{ $input }}">{{ $fo['description'] ?? '' }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Copyright line</span>
                        <input type="text" name="footer[copyright]" value="{{ $fo['copyright'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Footer color</span>
                        <div data-typography-color class="flex items-center gap-2">
                            <input type="color" data-color-swatch value="{{ $fo['backgroundColor'] ?? '#083caa' }}" title="Pick the footer's background color" class="h-9 w-11 shrink-0 cursor-pointer rounded-lg border border-slate-300 bg-white p-0.5">
                            <input type="text" name="footer[backgroundColor]" data-color-hex value="{{ $fo['backgroundColor'] ?? '#083caa' }}" maxlength="7" placeholder="#083caa" class="w-24 shrink-0 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm font-mono uppercase outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </div>
                    </label>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy-800">Link Columns</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">The columns of links beside the brand (Shop, Company, Support…). Each link’s URL may be an internal path (/menu) or a full external link; leave blank for an inert placeholder.</p>
                <div data-repeater data-token="__COL__">
                    <div data-rows class="space-y-4">
                        @foreach(array_values((array) ($fo['columns'] ?? [])) as $i => $item)
                            @include('admin.content._footer-column-row')
                        @endforeach
                    </div>
                    <template>@include('admin.content._footer-column-row', ['i' => '__COL__', 'item' => []])</template>
                    <button type="button" data-add class="{{ $addBtn }}">+ Add column</button>
                </div>
            </div>
        </section>

        {{-- ============ Legal Pages ============ --}}
        <section data-panel="legal" class="hidden space-y-5">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy-800">Legal Pages</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">Edit the public Privacy Policy, Terms of Service, and Data Deletion pages linked in the site footer. Keep the copy accurate to your actual data and order-handling practices.</p>
                @php($legalPages = ['privacy' => 'Privacy Policy', 'terms' => 'Terms of Service', 'dataDeletion' => 'Data Deletion'])
                @foreach($legalPages as $key => $label)
                    @php($legal = (array) ($content['legal'][$key] ?? []))
                    <fieldset class="{{ $loop->first ? '' : 'mt-8 border-t border-slate-100 pt-8' }}">
                        <legend class="text-base font-semibold text-navy-800">{{ $label }}</legend>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-slate-500">Page title</span>
                                <input type="text" name="legal[{{ $key }}][title]" value="{{ $legal['title'] ?? '' }}" class="{{ $input }}">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-slate-500">Last updated</span>
                                <input type="text" name="legal[{{ $key }}][lastUpdated]" value="{{ $legal['lastUpdated'] ?? '' }}" placeholder="August 6, 2026" class="{{ $input }}">
                            </label>
                        </div>
                        <label class="mt-4 block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Search description</span>
                            <input type="text" name="legal[{{ $key }}][description]" value="{{ $legal['description'] ?? '' }}" class="{{ $input }}">
                        </label>
                        <label class="mt-4 block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Page content</span>
                            <textarea name="legal[{{ $key }}][body]" rows="14" class="{{ $input }}">{{ $legal['body'] ?? '' }}</textarea>
                            <span class="mt-1 block text-xs text-slate-400">Use a blank line between sections. The first line of each section is shown as its heading.</span>
                        </label>
                    </fieldset>
                @endforeach
            </div>
        </section>

        {{-- ============ Menu Promo ============ --}}
        <section data-panel="menuPromo" class="{{ $panel }}">
            <h2 class="text-lg font-bold text-navy-800">Menu Promo Banner</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The promotional banner on the menu’s “What’s New” tab. Add one or more slides — the banner rotates through them. A slide can be fully custom, or link products to sell as one bundle: the button then adds them to the cart as a single bundle line named after the slide's title.</p>
            @php($mp = (array) ($content['menuPromo'] ?? []))
            {{-- Catalogue for the slides' bundle picker (rendered once; each
                 picker's dropdown is built from this by JS). --}}
            <script type="application/json" id="bundle-products-data">{!! json_encode(($bundleProducts ?? collect())->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'category' => $p->category,
                'code' => $p->product_id,
            ])->values()) !!}</script>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-navy-800">Show promo banner</p>
                    <p class="mt-0.5 text-xs text-slate-500">When off, the banner is hidden on the menu’s “What’s New” tab.</p>
                </div>
                <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                    <input type="checkbox" name="menuPromo[enabled]" value="1" class="peer sr-only" @checked(! empty($mp['enabled']))>
                    <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                    <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                </label>
            </div>
            <div data-repeater>
                <div data-rows class="space-y-2">
                    @foreach(array_values((array) ($mp['slides'] ?? [])) as $i => $item)
                        @include('admin.content._slide-row')
                    @endforeach
                </div>
                <template>@include('admin.content._slide-row', ['i' => '__IDX__', 'item' => []])</template>
                <button type="button" data-add class="{{ $addBtn }}">+ Add slide</button>
            </div>
        </section>

        {{-- ============ Payment QR ============ --}}
        <section data-panel="payment" class="{{ $panel }}">
            <h2 class="text-lg font-bold text-navy-800">Payment QR (QR Ph)</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">Upload your merchant’s QR code image, or paste the raw QR Ph data string. An uploaded image is shown at checkout as-is; the payload string lets checkout regenerate the QR per order with the exact amount baked in. With neither set, a generated demo QR is used.</p>
            <div class="space-y-4">
                @include('admin.content._image-field', ['name' => 'payment[qrImage]', 'value' => $content['payment']['qrImage'] ?? '', 'fieldLabel' => 'QR image (shown as-is — overrides the payload below)'])
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">QR Ph payload</span>
                    <textarea name="payment[qrPayload]" rows="4" placeholder="0002010102…" class="{{ $input }} font-mono text-xs">{{ $content['payment']['qrPayload'] ?? '' }}</textarea>
                </label>
            </div>
            <div class="mx-auto mt-5 flex max-w-xs flex-col items-center gap-3 rounded-xl border border-slate-200 p-5 text-center">
                <img src="{{ route('checkout.qr', ['amount' => 100]) }}" alt="QR Ph preview" loading="lazy" decoding="async" class="h-48 w-48 rounded-lg object-contain">
                <p class="text-sm font-semibold text-navy-800">Scan to pay ₱100.00</p>
                <p class="text-xs text-slate-500">Preview shows the last <em>saved</em> QR image or payload (sample ₱100.00) — with a payload, the real order amount is injected at checkout.</p>
            </div>
        </section>

        {{-- ============ Login Page ============ --}}
        <section data-panel="authPanel" class="{{ $panel }}">
            <h2 class="text-lg font-bold text-navy-800">Login Page</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The branded left panel shown on the Login and Register pages. Use transparent PNGs — logo ~400 px tall, image ~600 px wide.</p>
            @php($ap = (array) ($content['authPanel'] ?? []))
            <div class="space-y-4">
                @include('admin.content._image-field', ['name' => 'authPanel[logo]', 'value' => $ap['logo'] ?? '', 'fieldLabel' => 'Logo'])
                @include('admin.content._image-field', ['name' => 'authPanel[image]', 'value' => $ap['image'] ?? '', 'fieldLabel' => 'Panel image', 'wide' => true])
                @include('admin.content._image-field', ['name' => 'authPanel[backgroundImage]', 'value' => $ap['backgroundImage'] ?? '', 'fieldLabel' => 'Page background (behind the card — shown faded/darkened)', 'wide' => true])
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Tagline</span>
                    <input type="text" name="authPanel[tagline]" value="{{ $ap['tagline'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Script line</span>
                    <input type="text" name="authPanel[script]" value="{{ $ap['script'] ?? '' }}" class="{{ $input }}">
                </label>
                @include('admin.content._typography-panel', ['name' => 'authPanel[typography]', 'value' => $ap['typography'] ?? []])

                {{-- Social sign-in buttons on Login/Register — hide either (or
                     both, which also hides the "or" divider). --}}
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-navy-800">Show “Continue with Google”</p>
                        <p class="mt-0.5 text-xs text-slate-500">The Google sign-in button on the Login and Register pages.</p>
                    </div>
                    <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                        <input type="checkbox" name="authPanel[showGoogle]" value="1" class="peer sr-only" @checked($ap['showGoogle'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-navy-800">Show “Continue with Facebook”</p>
                        <p class="mt-0.5 text-xs text-slate-500">The Facebook sign-in button on the Login and Register pages.</p>
                    </div>
                    <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                        <input type="checkbox" name="authPanel[showFacebook]" value="1" class="peer sr-only" @checked($ap['showFacebook'] ?? true)>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
            </div>
        </section>

        {{-- ============ Social Links ============ --}}
        <section data-panel="social" class="{{ $panel }}">
            <h2 class="text-lg font-bold text-navy-800">Social Links</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The Facebook, TikTok, and X (Twitter) icons in the footer. Paste each profile’s full URL (https://…). All three icons always show; a field left empty just won’t link anywhere.</p>
            @php($so = (array) ($content['social'] ?? []))
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Facebook URL</span>
                    <input type="text" name="social[facebook]" value="{{ $so['facebook'] ?? '' }}" placeholder="https://www.facebook.com/…" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">TikTok URL</span>
                    <input type="text" name="social[tiktok]" value="{{ $so['tiktok'] ?? '' }}" class="{{ $input }}">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-500">X (Twitter) URL</span>
                    <input type="text" name="social[x]" value="{{ $so['x'] ?? '' }}" class="{{ $input }}">
                </label>
            </div>
        </section>

        {{-- ============ Buttons (+ maintenance mode) ============ --}}
        <section data-panel="buttons" class="hidden space-y-5">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy-800">Landing Page</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">Turn the public landing page on or off. When off, visitors to the home page see an “under construction” screen instead. The Site Editor and other pages stay reachable.</p>
                @php($mt = (array) ($content['maintenance'] ?? []))
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-navy-800">Show “Under Construction” page</p>
                        <p class="mt-0.5 text-xs text-slate-500">When on, the landing page is disabled for visitors.</p>
                    </div>
                    <label class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                        <input type="checkbox" name="maintenance[enabled]" id="maintenance-toggle" value="1" class="peer sr-only" @checked(! empty($mt['enabled']))>
                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                        <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </label>
                </div>
                <div id="maintenance-fields" class="mt-4 space-y-3 {{ empty($mt['enabled']) ? 'hidden' : '' }}">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Headline</span>
                        <input type="text" name="maintenance[title]" value="{{ $mt['title'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Message</span>
                        <textarea name="maintenance[message]" rows="3" class="{{ $input }}">{{ $mt['message'] ?? '' }}</textarea>
                    </label>
                    @include('admin.content._typography-panel', ['name' => 'maintenance[typography]', 'value' => $mt['typography'] ?? []])
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-navy-800">Buttons & Calls-to-Action</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">Control each action button across your site. Visible = shown and working, Disabled = shown but clicking does nothing, Hidden = removed from the live site.</p>
                @php($buttons = (array) ($content['buttons'] ?? []))
                <div class="space-y-6">
                    @foreach($buttonGroups as $group => $items)
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $group }}</p>
                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                @foreach($items as $b)
                                    @php($state = \App\Models\SiteContent::buttonState($buttons, $b['key']))
                                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 {{ $loop->first ? '' : 'border-t border-slate-100' }}">
                                        <span class="text-sm font-medium text-navy-800">{{ $b['label'] }}</span>
                                        <div class="inline-flex shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                            @foreach([['on', 'Visible', 'peer-checked:bg-brand-500'], ['disabled', 'Disabled', 'peer-checked:bg-amber-500'], ['off', 'Hidden', 'peer-checked:bg-slate-500']] as [$val, $lab, $activeCls])
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="buttons[{{ $b['key'] }}]" value="{{ $val }}" class="peer sr-only" @checked($state === $val)>
                                                    <span class="block px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 {{ $activeCls }} peer-checked:text-white">{{ $lab }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </form>

    {{-- ============ Menu Categories (own forms — products table + blob) ============ --}}
    <section data-panel="menuCategories" class="{{ $panel }}">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-navy-800">Menu Categories</h2>
                <p class="mb-5 mt-0.5 text-sm text-slate-500">Categories are derived from products. Rename to merge two categories into one, or delete a category and move its products elsewhere — those apply immediately. Images, newly added categories, and the visibility toggle are saved with the “Save changes” button above.</p>
            </div>
            {{-- Hides the landing "Shop by category" grid; saved via the header Save changes. --}}
            <label class="flex shrink-0 cursor-pointer items-center gap-2 pt-1">
                <span class="text-xs font-medium text-slate-500">Show on landing</span>
                <span class="relative inline-flex h-6 w-11 shrink-0 items-center">
                    <input type="hidden" name="categoriesVisible" value="0" form="categories-form">
                    <input type="checkbox" name="categoriesVisible" value="1" form="categories-form" class="peer sr-only" @checked($content['categoriesVisible'] ?? true)>
                    <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                    <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                </span>
            </label>
        </div>

        <datalist id="bw-categories">
            @foreach($categories as $cat)
                <option value="{{ $cat }}"></option>
            @endforeach
        </datalist>

        <form id="categories-form" method="POST" action="{{ route('admin.content.categories') }}">
            @csrf
            @foreach($categories as $cat)
                @if(in_array($cat, (array) ($content['menuCategories'] ?? []), true))
                    <input type="hidden" name="menuCategories[]" value="{{ $cat }}">
                @endif
            @endforeach

            <div class="mb-5 flex items-end gap-2 rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-4">
                <label class="min-w-0 flex-1">
                    <span class="mb-1 block text-xs font-medium text-slate-500">Add a new category</span>
                    <input type="text" name="menuCategories[]" placeholder="e.g. Sandwiches" class="{{ $input }}">
                </label>
                <button type="submit" class="shrink-0 rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    + Add
                </button>
            </div>

            @if(count($categories) === 0)
                <p class="text-sm text-slate-500">No categories yet — add one above.</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach($categories as $cat)
                        @php($catId = 'cat-'.md5($cat))
                        @php($count = $categoryCounts[$cat] ?? 0)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-navy-800">{{ $cat }}</p>
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                                    {{ $count }} product{{ $count === 1 ? '' : 's' }}
                                </span>
                            </div>

                            <div class="mt-3">
                                @include('admin.content._image-field', ['name' => "menuCategoryImages[$cat]", 'value' => $categoryImages[$cat] ?? '', 'fieldLabel' => 'Category image', 'crop' => 'circle'])
                                <p class="mt-1 text-[0.7rem] text-slate-400">
                                    Used two places: a small circle icon on the /menu sidebar, and the full card photo on the landing page's category grid — this is the only source; without one the category shows "no image". The drag/zoom step after choosing an image is just to pick what's centered.
                                </p>
                            </div>

                            <div class="mt-3 flex items-end gap-2">
                                <label class="min-w-0 flex-1">
                                    <span class="mb-1 block text-xs font-medium text-slate-500">Rename / merge to</span>
                                    <input type="text" list="bw-categories" name="rename_to[{{ $cat }}]" value="{{ $cat }}" form="{{ $catId }}-rename" class="{{ $input }}">
                                </label>
                                <button type="submit" form="{{ $catId }}-rename"
                                    class="shrink-0 rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">
                                    Apply
                                </button>
                            </div>

                            <div class="mt-2 flex items-end gap-2">
                                <label class="min-w-0 flex-1">
                                    <span class="mb-1 block text-xs font-medium text-slate-500">Delete & move products to</span>
                                    <select name="delete_to[{{ $cat }}]" form="{{ $catId }}-delete" class="{{ $input }} bg-white">
                                        @foreach($categories as $other)
                                            @if($other !== $cat)
                                                <option value="{{ $other }}">{{ $other }}</option>
                                            @endif
                                        @endforeach
                                        <option value="">Other</option>
                                    </select>
                                </label>
                                <button type="submit" form="{{ $catId }}-delete" onclick="return confirm('Delete “{{ $cat }}”? Its products move to the selected category.')"
                                    class="shrink-0 rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Saved via the header "Save changes" button — editing anything in
                 this form points the header pair at #categories-form (see the
                 categories-form dirty watcher in the scripts section). --}}
        </form>

        {{-- Per-category rename/delete targets (controls above attach via form=""). --}}
        @foreach($categories as $cat)
            @php($catId = 'cat-'.md5($cat))
            <form id="{{ $catId }}-rename" method="POST" action="{{ route('admin.content.categories.rename', $cat) }}">@csrf</form>
            <form id="{{ $catId }}-delete" method="POST" action="{{ route('admin.content.categories.delete', $cat) }}">@csrf</form>
        @endforeach
    </section>

@endsection

@section('scripts')
    <script>
        // ---- section tabs ---------------------------------------------------
        // Client-side show/hide over one always-complete form; ?section= keeps
        // deep links, refreshes, and the post-save redirect on the same tab.
        // Sidebar tabs are real ?section= links (from _editor-nav) intercepted
        // below, so Stores/Vouchers/Products items — plain links to their own
        // pages — just navigate.
        const PREVIEW_URLS = { menuPromo: '/menu', menuCategories: '/menu', payment: '/menu', authPanel: '/login', franchise: '/franchise', about: '/about', customCakeForm: '/custom-cake' }
        // Sections whose previewed page ships partials/_editor-bridge — only
        // these get pointer-events enabled in the preview iframe (see
        // swapPreview's `editable` param and Controller::isEditablePreview).
        // This is page-scoped, not tab-scoped: every tab below that PREVIEW_URLS
        // doesn't redirect elsewhere renders on the SAME landing page, so they
        // all have to be listed together even though not every one of them has
        // data-editable-tagged fields yet — untagged fields are simply inert
        // (the bridge only reacts to elements actually carrying the attribute),
        // so listing a tab here ahead of its fields being tagged is harmless.
        const EDITABLE_PREVIEW_SECTIONS = new Set([
            'franchise', 'about', 'authPanel', 'customCakeForm',
            'announcement', 'banners', 'whatsNew', 'categoriesSection', 'bestSellersSection', 'customCake',
            'storeLocator', 'newsletter', 'social', 'footer', 'legal', 'buttons',
            // All three map to the same /menu preview (see PREVIEW_URLS) — must
            // be listed together so pointer-events stays consistent across tabs
            // on that one page, not toggling on/off as the editor switches tabs.
            'menuPromo', 'menuCategories', 'payment',
        ])

        // ---- Menu Promo bundle picker ----------------------------------------
        // Each slide has a searchable combobox ([data-bundle-search]): typing
        // filters the catalogue (#bundle-products-data) by name, code, or
        // category; picking an option appends a chip holding a hidden
        // products[] input; ✕ unlinks it. Delegated so repeater-added rows
        // work too. Options/chips are built via DOM APIs (not innerHTML) so
        // product names can't inject markup. The search box is data-no-dirty —
        // only actually linking/unlinking marks the form dirty.
        const BUNDLE_PRODUCTS = JSON.parse(document.getElementById('bundle-products-data')?.textContent || '[]')
        const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

        function bundleLinkedIds(picker) {
            const list = picker.closest('[data-bundle]').querySelector('[data-bundle-list]')
            return new Set(Array.from(list.querySelectorAll('input')).map((inp) => inp.value))
        }

        function renderBundleMenu(picker) {
            const menu = picker.querySelector('[data-bundle-menu]')
            const q = picker.querySelector('[data-bundle-search]').value.trim().toLowerCase()
            const linked = bundleLinkedIds(picker)
            const matches = BUNDLE_PRODUCTS
                .filter((p) => !linked.has(p.id))
                .filter((p) => !q
                    || p.name.toLowerCase().includes(q)
                    || (p.code || '').toLowerCase().includes(q)
                    || (p.category || '').toLowerCase().includes(q))
                .slice(0, 30)
            menu.innerHTML = ''
            if (!matches.length) {
                const empty = document.createElement('p')
                empty.className = 'px-3 py-2.5 text-sm text-slate-400'
                empty.textContent = 'No products match.'
                menu.appendChild(empty)
            }
            matches.forEach((p) => {
                const opt = document.createElement('button')
                opt.type = 'button'
                opt.setAttribute('data-bundle-option', p.id)
                opt.className = 'flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition hover:bg-brand-50'
                const name = document.createElement('span')
                name.className = 'min-w-0 flex-1 truncate font-medium text-navy-800'
                name.textContent = p.name
                const meta = document.createElement('span')
                meta.className = 'shrink-0 text-xs text-slate-400'
                meta.textContent = (p.category ? p.category + ' · ' : '') + peso(p.price)
                opt.append(name, meta)
                menu.appendChild(opt)
            })
            menu.classList.remove('hidden')
        }

        function closeBundleMenus() {
            document.querySelectorAll('[data-bundle-menu]').forEach((m) => m.classList.add('hidden'))
        }

        document.addEventListener('input', (e) => {
            const search = e.target.closest('[data-bundle-search]')
            if (search) renderBundleMenu(search.closest('[data-bundle-picker]'))
        })
        document.addEventListener('focusin', (e) => {
            const search = e.target.closest('[data-bundle-search]')
            if (search) renderBundleMenu(search.closest('[data-bundle-picker]'))
        })
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // With a picker dropdown open, Escape closes just the dropdown —
                // not the slide popup underneath (whose own Escape handler is
                // registered later, so stopping immediate propagation skips it).
                if (document.querySelector('[data-bundle-menu]:not(.hidden)')) {
                    closeBundleMenus()
                    e.stopImmediatePropagation()
                }
                return
            }
            // Enter links the top match instead of submitting the whole form.
            if (e.key === 'Enter' && e.target.closest('[data-bundle-search]')) {
                e.preventDefault()
                const picker = e.target.closest('[data-bundle-picker]')
                picker.querySelector('[data-bundle-option]')?.click()
            }
        })
        document.addEventListener('click', (e) => {
            const option = e.target.closest('[data-bundle-option]')
            if (option) {
                const picker = option.closest('[data-bundle-picker]')
                const search = picker.querySelector('[data-bundle-search]')
                const p = BUNDLE_PRODUCTS.find((x) => x.id === option.dataset.bundleOption)
                const list = picker.closest('[data-bundle]').querySelector('[data-bundle-list]')
                if (p && !bundleLinkedIds(picker).has(p.id)) {
                    const chip = document.createElement('span')
                    chip.setAttribute('data-bundle-chip', '')
                    chip.className = 'inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-2.5 py-1 text-xs font-medium text-navy-700'
                    const hidden = document.createElement('input')
                    hidden.type = 'hidden'
                    hidden.name = search.dataset.name
                    hidden.value = p.id
                    const remove = document.createElement('button')
                    remove.type = 'button'
                    remove.setAttribute('data-bundle-remove', '')
                    remove.setAttribute('aria-label', 'Unlink ' + p.name)
                    remove.className = 'text-slate-400 transition hover:text-red-600'
                    remove.textContent = '✕'
                    chip.append(hidden, document.createTextNode(p.name), remove)
                    list.appendChild(chip)
                    // Chips don't fire input/change on their own — poke the
                    // form so the Save/Reset buttons appear.
                    list.dispatchEvent(new Event('input', { bubbles: true }))
                }
                search.value = ''
                renderBundleMenu(picker) // stay open so several can be linked in a row
                search.focus()
                return
            }
            const unlink = e.target.closest('[data-bundle-remove]')
            if (unlink) {
                const list = unlink.closest('[data-bundle-list]')
                unlink.closest('[data-bundle-chip]').remove()
                list.dispatchEvent(new Event('input', { bubbles: true }))
                return
            }
            if (!e.target.closest('[data-bundle-picker]')) closeBundleMenus()
        })

        const panels = Array.from(document.querySelectorAll('[data-panel]'))
        const tabs = Array.from(document.querySelectorAll('[data-tab]'))
        const SECTION_LABELS = {}
        tabs.forEach((t) => { SECTION_LABELS[t.dataset.tab] = t.querySelector('span').textContent.trim() })
        const sectionField = document.getElementById('content-section-field')
        const editorTitle = document.getElementById('editor-title')
        const contentForm = document.getElementById('content-form')
        let currentPreviewPath = '/'
        let currentPreviewEditable = false

        // Live preview: stage the current unsaved form as a draft (server keeps
        // it in this editor's session only), then swap in the preview reloaded
        // with ?preview=1 so the public page renders the draft over saved
        // content. swapPreview (from _preview) double-buffers so the visible
        // pane never blanks while the new render loads.
        //
        // If the editor is actively typing inline inside the preview iframe
        // itself (see the click-to-edit bridge below), a reload here would
        // replace that iframe out from under them and lose their cursor mid-
        // keystroke — so the draft is still staged (keeps it correct for
        // anything else that reads it) but the actual swap is skipped. The
        // 'field-blur' message handler calls refreshPreview() again once
        // they're done, which naturally proceeds since nothing's editing by
        // then.
        async function refreshPreview() {
            if (!window.swapPreview) return
            const fd = new FormData(contentForm)
            fd.delete('_method') // don't let Laravel method-spoof this POST into a PUT
            try {
                await fetch('{{ route('admin.content.preview') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                    body: fd,
                })
            } catch { /* preview is best-effort */ }
            const frame = document.getElementById('preview-frame')
            let activelyEditing = false
            try { activelyEditing = !!frame?.contentDocument?.activeElement?.isContentEditable } catch { /* cross-origin, shouldn't happen (same-origin preview) */ }
            if (activelyEditing) return
            const sep = currentPreviewPath.includes('?') ? '&' : '?'
            swapPreview(currentPreviewPath + sep + 'preview=1&_=' + Date.now(), currentPreviewEditable)
        }

        let previewTimer
        const schedulePreview = () => { clearTimeout(previewTimer); previewTimer = setTimeout(refreshPreview, 500) }

        // Nav renders two variants of each [data-tab] link (desktop dropdown
        // on white, mobile panel on navy) — they need opposite text colors,
        // so active/inactive classes are picked per element, not shared.
        const PILL_ACTIVE = ['bg-gradient-to-r', 'from-brand-500', 'to-brand-600', 'text-white', 'shadow-lg', 'shadow-brand-500/30']
        const PILL_INACTIVE_MOBILE = ['text-navy-50/70']
        const PILL_INACTIVE_DESKTOP = ['text-navy-700']
        const BADGE_ACTIVE = ['bg-white/25', 'text-white']
        const BADGE_INACTIVE_MOBILE = ['bg-white/10', 'text-navy-50/70']
        const BADGE_INACTIVE_DESKTOP = ['bg-slate-100', 'text-slate-500']

        function showSection(key) {
            if (!panels.some((p) => p.dataset.panel === key)) key = 'announcement'
            panels.forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== key))
            tabs.forEach((t) => {
                const on = t.dataset.tab === key
                const isDesktop = !!t.closest('[data-dropdown-panel]')
                const pillInactive = isDesktop ? PILL_INACTIVE_DESKTOP : PILL_INACTIVE_MOBILE
                PILL_ACTIVE.forEach((c) => t.classList.toggle(c, on))
                pillInactive.forEach((c) => t.classList.toggle(c, !on))
                const badge = t.querySelector('[data-tab-badge]')
                if (badge) {
                    const badgeInactive = isDesktop ? BADGE_INACTIVE_DESKTOP : BADGE_INACTIVE_MOBILE
                    BADGE_ACTIVE.forEach((c) => badge.classList.toggle(c, on))
                    badgeInactive.forEach((c) => badge.classList.toggle(c, !on))
                }
            })
            document.querySelectorAll('[data-nav-group]').forEach((g) => {
                const holds = !!g.querySelector(`[data-tab="${key}"]`)
                const label = g.querySelector('[data-accordion-toggle]')
                label.classList.toggle('text-brand-400', holds)
                label.classList.toggle('text-navy-50/50', !holds)
            })
            document.querySelectorAll('[data-dropdown]').forEach((d) => {
                const holds = !!d.querySelector(`[data-tab="${key}"]`)
                const toggle = d.querySelector('[data-dropdown-toggle]')
                toggle.classList.toggle('bg-white/10', holds)
                toggle.classList.toggle('text-white', holds)
                toggle.classList.toggle('text-navy-50/70', !holds)
            })
            editorTitle.textContent = SECTION_LABELS[key] || 'Site Editor'
            sectionField.value = key
            currentPreviewPath = PREVIEW_URLS[key] || '/'
            currentPreviewEditable = EDITABLE_PREVIEW_SECTIONS.has(key)
            refreshPreview()
            history.replaceState(null, '', '?section=' + encodeURIComponent(key))
        }

        tabs.forEach((t) => t.addEventListener('click', (e) => {
            e.preventDefault()
            showSection(t.dataset.tab)
            if (typeof window.closeMobileNav === 'function') window.closeMobileNav()
        }))
        showSection(new URLSearchParams(location.search).get('section') || 'announcement')

        // Dotted CMS field path ("franchise.hero.title") → this form's actual
        // input name ("franchise[hero][title]") — used by both validation-error
        // locating and the click-to-edit bridge below.
        function dotPathToName(path) {
            return path.split('.').map((part, i) => (i === 0 ? part : `[${part}]`)).join('')
        }

        document.addEventListener('input', (e) => {
            if (e.target.id === 'announcement-input') {
                document.getElementById('announcement-preview').textContent = e.target.value || '—'
            }
        })

        // Debounced live preview — any edit re-stages the draft and reloads the
        // iframe (~0.5s after you stop). Repeater add/remove/move are clicks.
        contentForm.addEventListener('input', schedulePreview)
        contentForm.addEventListener('change', schedulePreview)
        contentForm.addEventListener('click', (e) => {
            if (e.target.closest('[data-add],[data-remove],[data-move]')) schedulePreview()
        })

        // Maintenance headline/message only matter while maintenance mode is on.
        document.getElementById('maintenance-toggle').addEventListener('change', (e) => {
            document.getElementById('maintenance-fields').classList.toggle('hidden', !e.target.checked)
        })

        // ---- Menu Categories saves via the header pair too -------------------
        // The categories tab is its own form (images/added names/visibility →
        // POST admin.content.categories). Editing it reveals the same header
        // Save/Reset used by the main form and points Save at #categories-form;
        // editing the main form points it back. Last-touched form wins, which
        // matches "the save button saves what I'm editing".
        document.addEventListener('DOMContentLoaded', () => {
            const save = document.querySelector('[data-save-button]')
            const reset = document.querySelector('[data-reset-button]')
            const categoriesForm = document.getElementById('categories-form')
            if (!save || !categoriesForm) return

            const categoriesDirty = (e) => {
                if (e && e.target.closest('[data-no-dirty]')) return
                save.setAttribute('form', 'categories-form')
                save.classList.remove('hidden')
                reset.classList.remove('hidden')
            }
            categoriesForm.addEventListener('input', categoriesDirty)
            categoriesForm.addEventListener('change', categoriesDirty)
            // Controls attached from outside the form (the tab-header
            // "Show on landing" toggle uses form="categories-form").
            document.querySelectorAll('[form="categories-form"]').forEach((el) => {
                if (el.type === 'checkbox' || el.tagName === 'INPUT') el.addEventListener('change', categoriesDirty)
            })

            const mainDirty = () => save.setAttribute('form', 'content-form')
            contentForm.addEventListener('input', mainDirty)
            contentForm.addEventListener('change', mainDirty)
        })
    </script>
    @include('admin.content._form-scripts')
    <script>
        // ---- list rows + edit popups (franchise perks / steps / packages) ----
        // x-list-row rows are compact summary lines; their fields live in a
        // [data-modal] popup inside the row — still inside the form, so Save
        // changes submits everything and popup edits keep feeding the live
        // preview. Registered after _form-scripts so on "+ Add" clicks the new
        // row already exists.
        function itemSummary(row) {
            const values = (sel) => Array.from(row.querySelectorAll(sel)).map((el) => el.value.trim()).filter(Boolean)
            row.querySelector('[data-item-title]').textContent = values('[data-summary-title]').join(' ') || row.dataset.emptyLabel
            row.querySelector('[data-item-meta]').textContent = values('[data-summary-meta]').join(' · ')
        }

        const listRowOf = (el) => el.closest('[data-row][data-empty-label]')
        contentForm.querySelectorAll('[data-row][data-empty-label]').forEach(itemSummary)

        function openItemModal(row) {
            row.querySelector('[data-modal]').classList.remove('hidden')
            document.body.classList.add('overflow-hidden')
        }

        function closeItemModal(modal) {
            modal.classList.add('hidden')
            document.body.classList.remove('overflow-hidden')
            // A freshly added row closed while still completely empty is
            // discarded, so + Add → close doesn't pile up blank items.
            const row = modal.closest('[data-row]')
            // Bundle chips count as content too — a new slide that only links
            // products must survive the discard-if-empty check.
            const empty = Array.from(row.querySelectorAll('input[type="text"], textarea')).every((el) => el.value.trim() === '')
                && !row.querySelector('[data-bundle-chip]')
            if (row.hasAttribute('data-new') && empty) row.querySelector('[data-remove]').click()
            else row.removeAttribute('data-new')
        }

        document.addEventListener('click', (e) => {
            const edit = e.target.closest('[data-edit]')
            if (edit) { openItemModal(listRowOf(edit)); return }
            const close = e.target.closest('[data-modal-close]')
            if (close) { closeItemModal(close.closest('[data-modal]')); return }
            const add = e.target.closest('[data-add]')
            if (add) {
                const row = add.closest('[data-repeater]').querySelector(':scope > [data-rows]').lastElementChild
                if (row && row.matches('[data-row][data-empty-label]')) {
                    row.setAttribute('data-new', '')
                    itemSummary(row)
                    openItemModal(row)
                }
            }
        })

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return
            contentForm.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeItemModal)
        })

        contentForm.addEventListener('input', (e) => { const row = listRowOf(e.target); if (row) itemSummary(row) })
        contentForm.addEventListener('change', (e) => { const row = listRowOf(e.target); if (row) itemSummary(row) })

        // ---- locating a field (shared by validation errors + click-to-edit) --
        // Switches to the field's tab, opens its list-row popup if it's inside
        // one (franchise perks/steps/packages etc.), then scrolls to and
        // focuses it — the existing focus:ring on every input is highlight
        // enough on its own.
        function revealField(field) {
            syncActiveSection(field)
            const row = listRowOf(field)
            if (row) openItemModal(row)
            field.scrollIntoView({ block: 'center' })
            field.focus()
        }

        // Lighter half of revealField: just keeps the sidebar's active tab
        // honest (so e.g. a post-Save redirect lands on the right one) — no
        // scroll/focus/modal-open. Used when the editor is already focused
        // *inside the preview iframe* (see the click-to-edit bridge below),
        // where stealing DOM focus back to a sidebar field, or popping a
        // repeater row open over the preview, would be a bigger interruption
        // than the tab indicator being briefly stale is worth.
        function syncActiveSection(field) {
            const panel = field.closest('[data-panel]')
            if (panel) showSection(panel.dataset.panel)
        }

        // ---- validation-error locating -----------------------------------
        // On a failed save, the server flashes which fields were invalid
        // ("franchise.email", "menuPromo.slides.0.bundlePrice" — see the JSON
        // blob near the top of the form). With 14 always-in-DOM-but-hidden
        // tabs (and repeater fields hidden inside popups on top of that), the
        // failing one could be anywhere.
        const contentErrorFieldsEl = document.getElementById('content-form-error-fields')
        if (contentErrorFieldsEl) {
            const errorFields = JSON.parse(contentErrorFieldsEl.textContent || '[]')
                .map((key) => contentForm.querySelector(`[name="${CSS.escape(dotPathToName(key))}"]`))
                .filter(Boolean)
            // Every invalid field gets a persistent red ring (revealField's
            // focus-ring only shows on whichever one is currently focused).
            errorFields.forEach((field) => field.classList.add('!border-red-400', 'ring-2', 'ring-red-500/30'))
            if (errorFields[0]) revealField(errorFields[0])
        }

        // ---- click-to-edit bridge ------------------------------------------
        // partials/_editor-bridge (loaded inside the preview iframe, only for
        // sections in EDITABLE_PREVIEW_SECTIONS) makes tagged elements on the
        // actual rendered page directly editable, and posts messages back as
        // the editor interacts with them — see the bridge file for the full
        // message-shape reference. Each case mirrors the interaction onto
        // this form's real field so every bit of existing machinery (dirty
        // state, Save/Reset, repeater-row titles, autosave-preview-refresh)
        // keeps working unchanged; this listener is the only integration
        // point.
        window.addEventListener('message', (e) => {
            if (e.origin !== window.location.origin) return
            if (!e.data || e.data.source !== 'bw-editor-bridge') return
            const field = contentForm.querySelector(`[name="${CSS.escape(dotPathToName(e.data.path))}"]`)
            if (!field) return
            switch (e.data.type) {
                case 'field-focus':
                    syncActiveSection(field)
                    break
                case 'field-input': {
                    // Enter is already intercepted in the bridge for anything
                    // that isn't data-editable-multiline, but collapse stray
                    // newlines here too as defense-in-depth for a plain
                    // <input> target.
                    let value = e.data.value ?? ''
                    if (field.tagName !== 'TEXTAREA') value = value.replace(/\r?\n/g, ' ')
                    if (field.value !== value) {
                        field.value = value
                        field.dispatchEvent(new Event('input', { bubbles: true }))
                    }
                    break
                }
                case 'field-blur':
                    refreshPreview()
                    break
                case 'image': {
                    const imageField = field.closest('[data-image-field]')
                    if (!imageField) break
                    // Must switch to this field's panel (and so un-hide it)
                    // BEFORE clicking the file input below — a hidden
                    // ancestor silently blocks the native picker from
                    // opening.
                    syncActiveSection(field)
                    imageField.querySelector('[data-image-file]')?.click()
                    break
                }
                default:
                    // 'jump', and backward-compat for a bridge that only
                    // ever sent {path} with no type.
                    revealField(field)
            }
        })
    </script>
@endsection
