{{-- "Customize Your Cake" — public 3-step inquiry wizard (details → your info
     → review). One real <form> wraps every step; JS only shows/hides steps and
     validates before advancing, so the final submit posts everything at once.
     Submissions land in custom_cake_requests for the team to quote. --}}
@php
    $metaTitle = 'BW Superbakeshop | Customize Your Cake';
    $metaDescription = "Tell us your dream cake — flavor, size, design — and we'll bake it to perfection. Our team will follow up with a quote.";
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo-meta')
    @include('partials.favicon')
    @vite('resources/css/app.css')
    @vite('resources/js/cake-preview.js')
</head>
<body class="min-h-screen bg-white text-navy-800">

@include('partials.site-nav')

<main class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
    @if(session('cc_success'))
        {{-- success state (replaces the wizard after a submit) --}}
        <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
            </div>
            <h1 class="mt-5 font-brand text-2xl font-bold text-navy-800">Request sent! 🎂</h1>
            <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">Thank you — our team has your dream cake on file and will follow up with a quote at the email you provided.</p>
            <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('menu') }}" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">Browse the menu</a>
                <a href="/" class="rounded-full border border-slate-300 px-7 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">Back to home</a>
            </div>
        </div>
    @else
        @php $customCakeFormTypography = \App\Models\SiteContent::typographyStyle($form['typography'] ?? []); @endphp
        <div class="text-center">
            <span data-editable="customCakeForm.eyebrow" class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500" style="{{ $customCakeFormTypography }}">{{ $form['eyebrow'] }}</span>
            <h1 data-editable="customCakeForm.title" class="mt-2 font-brand text-4xl font-bold text-navy-800" style="{{ $customCakeFormTypography }}">{{ $form['title'] }}</h1>
            <p data-editable="customCakeForm.subtitle" data-editable-multiline class="mx-auto mt-3 max-w-md text-sm text-slate-500" style="{{ $customCakeFormTypography }}">{{ $form['subtitle'] }}</p>
        </div>

        {{-- progress --}}
        <ol class="mt-8 flex items-center justify-center gap-2 text-xs sm:gap-3 sm:text-sm">
            @foreach(['Cake Details', 'Your Info', 'Review'] as $i => $label)
                @if($i > 0)<span class="h-px w-6 shrink-0 bg-slate-200 sm:w-10"></span>@endif
                <li class="flex items-center gap-1.5" data-step-chip="{{ $i + 1 }}">
                    <span data-chip-num class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-500 transition">{{ $i + 1 }}</span>
                    <span data-chip-label class="font-semibold text-slate-400 transition">{{ $label }}</span>
                </li>
            @endforeach
        </ol>

        @if($errors->any())
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="cake-form" method="POST" action="{{ route('custom-cake.store') }}" enctype="multipart/form-data" novalidate class="mt-8">
            @csrf

            {{-- ---- step 1: cake details ---- --}}
            <section data-step="1">
                {{-- live preview --}}
                <div class="overflow-hidden rounded-2xl border border-amber-100 bg-amber-50/60">
                    <div class="flex items-start justify-between gap-3 px-5 pt-4">
                        <span class="text-xs font-semibold uppercase tracking-widest text-brand-600">Sample cake preview - not an actual cake</span>
                        <div class="flex shrink-0 flex-col gap-2">
                            <button id="cake-reset-options" type="button" class="rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-navy-800 focus-visible:ring-2 focus-visible:ring-brand-500">Reset cake</button>
                        </div>
                    </div>
                    <div id="cake-3d" class="relative h-80 w-full" role="group" aria-label="Interactive cake preview">
                        <svg id="cake-preview" viewBox="0 0 200 170" class="mx-auto h-full w-56" aria-hidden="true"></svg>
                    </div>
                    <p id="cake-preview-status" class="px-4 pb-4 text-center text-xs text-slate-500" aria-live="polite">Pick a size, frosting color and occasion to customize your cake.</p>
                </div>
                <p class="mt-2 text-center text-xs font-semibold text-slate-500">Sample only - not an actual cake. The final cake may look different.</p>
                <p class="mt-1 text-center text-xs text-slate-400">Our team will confirm the final design and details with your quote.</p>
                <p id="cake-decoration-note" class="mt-1 text-center text-xs text-slate-500" aria-live="polite">Choose a flavor and occasion to preview matching decorations.</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Occasion <span class="text-red-500">*</span></span>
                        <select name="occasion" id="occasion" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            <option value="">Select occasion…</option>
                            @foreach($form['occasions'] as $o)
                                <option value="{{ $o }}" @selected(old('occasion') === $o)>{{ $o }}</option>
                            @endforeach
                        </select>
                        <p data-error-for="occasion" class="mt-1 hidden text-xs text-red-600"></p>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Needed By <span class="text-red-500">*</span></span>
                        <input type="date" name="needed_by" required min="{{ now()->toDateString() }}" value="{{ old('needed_by') }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <p data-error-for="needed_by" class="mt-1 hidden text-xs text-red-600"></p>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Flavor <span class="text-red-500">*</span></span>
                        <select name="flavor" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            <option value="">Select flavor…</option>
                            @foreach($form['flavors'] as $f)
                                <option value="{{ $f }}" @selected(old('flavor') === $f)>{{ $f }}</option>
                            @endforeach
                        </select>
                        <p data-error-for="flavor" class="mt-1 hidden text-xs text-red-600"></p>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Size <span class="text-red-500">*</span></span>
                        <select name="size" id="size" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            <option value="">Select size…</option>
                            @foreach($form['sizes'] as $s)
                                <option value="{{ $s }}" @selected(old('size') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        <p data-error-for="size" class="mt-1 hidden text-xs text-red-600"></p>
                    </label>
                </div>

                <fieldset class="mt-4">
                    <legend class="mb-2 text-sm font-semibold text-navy-800">Cake Shape</legend>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['Round', 'Square', 'Rectangle'] as $shape)
                            <label class="cursor-pointer">
                                <input type="radio" name="shape" value="{{ $shape }}" class="peer sr-only" @checked(old('shape', 'Round') === $shape)>
                                <span class="block rounded-xl border border-slate-300 px-3 py-3 text-center text-sm font-semibold transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">{{ $shape }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Size labels are approximate; final dimensions and servings will be confirmed in your quote.</p>
                </fieldset>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                    <label for="cake-theme" class="mb-2 block text-sm font-semibold text-navy-800">Cake Theme</label>
                    <select id="cake-theme" name="theme" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        @foreach(['None' => 'Classic / No theme', 'Anime' => 'Anime', 'Cartoon' => 'Cartoon', 'Superhero' => 'Superhero', 'Princess' => 'Princess', 'Space' => 'Space', 'Naruto' => 'Anime - Naruto', 'Luffy' => 'Anime - Luffy', 'Pikachu' => 'Pokemon - Pikachu', 'Hello Kitty' => 'Cartoon - Hello Kitty', 'SpongeBob' => 'Cartoon - SpongeBob', 'Mermaid' => 'Kids - Mermaid', 'Cocomelon' => 'Kids - Cocomelon', 'Unicorn' => 'Kids - Unicorn', 'Dinosaur' => 'Kids - Dinosaur', 'Custom' => 'Custom theme'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('theme', 'None') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="cake-character-wrap" class="mt-3" @if(old('theme', 'None') === 'None') hidden @endif>
                        <label for="cake-character" class="mb-1 block text-sm font-semibold text-navy-800">Character or theme name <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="cake-character" name="character_name" maxlength="100" value="{{ old('character_name') }}" placeholder="e.g. Naruto, Luffy, Pikachu, Hello Kitty" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <p class="mt-2 text-xs text-slate-500">The preview shows a stylized sample topper. Tell us the exact character and upload a reference below for the final design.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="mb-1.5 block text-sm font-semibold text-navy-800">Frosting Color <span class="text-red-500">*</span></span>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach($form['colors'] as $c)
                            @php([$cName, $cHex] = [$c['name'], $c['hex']])
                            <label class="cursor-pointer" title="{{ $cName }}">
                                <input type="radio" name="frosting_color" value="{{ $cName }}" data-hex="{{ $cHex }}" class="peer sr-only" @checked(old('frosting_color') === $cName)>
                                <span class="block h-9 w-9 rounded-full border border-slate-200 shadow-sm transition peer-checked:ring-2 peer-checked:ring-brand-500 peer-checked:ring-offset-2" style="background: {{ $cHex }}"></span>
                            </label>
                        @endforeach
                    </div>
                    <p data-error-for="frosting_color" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div class="mt-4">
                    <label for="description" class="mb-1 block text-sm font-semibold text-navy-800">Describe Your Dream Cake <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="4" placeholder="Tell us about the theme, colors, design, message on the cake, dietary needs, etc."
                        class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">{{ old('description') }}</textarea>
                    <p data-error-for="description" class="mt-1 hidden text-xs text-red-600"></p>
                </div>

                <div class="mt-5">
                    <span class="block text-sm font-semibold text-navy-800">Reference / Inspiration <span class="font-normal text-slate-400">(optional)</span></span>
                    <p class="mt-0.5 text-xs text-slate-400">Upload a design peg and/or paste a link — both optional.</p>
                    <div id="upload-box" class="mt-2 cursor-pointer rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 p-6 text-center transition hover:border-brand-300">
                        <input type="file" name="reference_image" id="reference_image" accept="image/png,image/jpeg,image/webp" class="hidden">
                        <img id="upload-preview" src="" alt="" class="mx-auto mb-2 hidden max-h-32 rounded-lg object-contain">
                        <p class="text-2xl">🖼️</p>
                        <p id="upload-label" class="mt-1 text-sm font-medium text-navy-700">Click to upload an image</p>
                        <p class="mt-0.5 text-xs text-slate-400">PNG, JPG, or WEBP up to 10MB</p>
                    </div>
                    <input type="url" name="reference_link" value="{{ old('reference_link') }}" placeholder="Or paste a Pinterest, Google Drive, or image link"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>

                <button type="button" data-next class="mt-6 w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    Next: Your Info →
                </button>
            </section>

            {{-- ---- step 2: your info ---- --}}
            <section data-step="2" class="hidden">
                {{-- delivery / pickup — mirrors the checkout page's step 1 --}}
                <div class="mb-6">
                    <span class="mb-2 block text-sm font-semibold text-navy-800">Delivery or Pickup <span class="text-red-500">*</span></span>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" data-cc-mode="delivery" class="cc-mode-card flex flex-col items-start gap-1 rounded-xl border p-4 text-left transition">
                            <span class="text-2xl">🚚</span><span class="text-sm font-semibold text-navy-800">Delivery</span><span class="text-xs text-slate-500">We'll deliver to your door</span>
                        </button>
                        <button type="button" data-cc-mode="pickup" class="cc-mode-card flex flex-col items-start gap-1 rounded-xl border p-4 text-left transition">
                            <span class="text-2xl">🏪</span><span class="text-sm font-semibold text-navy-800">Pickup</span><span class="text-xs text-slate-500">Pick up at a BW branch</span>
                        </button>
                    </div>
                    <input type="hidden" name="delivery_type" id="cc-delivery-type" value="{{ old('delivery_type', 'delivery') }}">

                    <div id="cc-address-wrap" class="mt-4">
                        <label class="mb-1 block text-sm font-semibold text-navy-800">📍 Delivery address <span class="text-red-500">*</span></label>
                        @if(!empty($savedAddresses))
                            {{-- addresses from past orders/requests — picking one fills the box below --}}
                            <select id="cc-saved-address"
                                class="mb-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                <option value="">Saved addresses…</option>
                                @foreach($savedAddresses as $addr)
                                    <option value="{{ $addr }}">{{ $addr }}</option>
                                @endforeach
                                <option value="__new__">➕ Type a new address</option>
                            </select>
                        @endif
                        <textarea name="address" id="cc-address" rows="2" placeholder="House / unit no., street, barangay, city"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">{{ old('address') }}</textarea>
                        <p data-error-for="address" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>

                    {{-- pickup only — delivery just needs the address above --}}
                    <div class="mt-4" id="cc-branch-wrap">
                        <span class="mb-2 block text-sm font-semibold text-navy-800">📍 Choose a branch to pick up at <span class="text-red-500">*</span></span>
                        <button type="button" id="cc-find-nearest"
                            class="mb-2 inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-xs font-semibold text-brand-600 transition hover:bg-brand-100 disabled:opacity-60">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="7" /><circle cx="12" cy="12" r="2.5" /><line x1="12" y1="2" x2="12" y2="5" /><line x1="12" y1="19" x2="12" y2="22" /><line x1="2" y1="12" x2="5" y2="12" /><line x1="19" y1="12" x2="22" y2="12" />
                            </svg>
                            Find nearest store
                        </button>
                        <p id="cc-nearest-status" class="mb-2 hidden text-xs" role="status"></p>
                        <input type="text" id="cc-branch-search" placeholder="Search by branch name, area, or city"
                            class="mb-3 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <div id="cc-branch-list" class="grid max-h-64 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                            @foreach($stores as $s)
                                <label class="cc-branch-option cursor-pointer" data-search="{{ strtolower($s->name.' '.$s->region.' '.$s->address) }}"
                                    data-lat="{{ $s->latitude }}" data-lng="{{ $s->longitude }}">
                                    <input type="radio" name="fulfillment_branch" value="{{ $s->name }}" class="peer sr-only" @checked(old('fulfillment_branch') === $s->name)>
                                    <span class="flex h-full flex-col rounded-xl border border-slate-200 p-3 transition peer-checked:border-brand-400 peer-checked:bg-brand-50/60 peer-checked:ring-2 peer-checked:ring-brand-500/20">
                                        <span class="text-sm font-semibold text-navy-800">📍 {{ $s->name }}</span>
                                        <span class="text-xs text-slate-500">{{ $s->address }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p id="cc-branch-no-match" class="hidden py-3 text-center text-xs text-slate-400">No branches match your search.</p>
                        <p data-error-for="fulfillment_branch" class="mt-1 hidden text-xs text-red-600"></p>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Full Name <span class="text-red-500">*</span></span>
                        <input type="text" name="name" id="cc-name" value="{{ old('name', $user['name'] ?? '') }}" placeholder="Juan Dela Cruz"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <p data-error-for="name" class="mt-1 hidden text-xs text-red-600"></p>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Email Address <span class="text-red-500">*</span></span>
                        <input type="email" name="email" id="cc-email" value="{{ old('email', $user['email'] ?? '') }}" placeholder="you@email.com"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <p data-error-for="email" class="mt-1 hidden text-xs text-red-600"></p>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Mobile Number <span class="font-normal text-slate-400">(optional)</span></span>
                        <input type="tel" name="phone" id="cc-phone" value="{{ old('phone', $contactNumber ?? '') }}" placeholder="0917 123 4567" maxlength="40"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                    </label>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" data-back class="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">← Back</button>
                    <button type="button" data-next class="flex-1 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        Next: Review →
                    </button>
                </div>
            </section>

            {{-- ---- step 3: review ---- --}}
            <section data-step="3" class="hidden">
                <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-5">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-navy-800">Your request</h2>
                    <dl id="review-list" class="mt-3 space-y-2 text-sm"></dl>
                </div>
                <p class="mt-3 text-center text-xs text-slate-400">No payment yet — we'll email you a quote to confirm first.</p>
                <div class="mt-5 flex gap-3">
                    <button type="button" data-back class="rounded-full border border-slate-300 px-6 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">← Back</button>
                    <button type="submit" class="flex-1 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        Send my request 🎂
                    </button>
                </div>
            </section>
        </form>
    @endif
</main>

@unless(session('cc_success'))
<script>
(function () {
    const form = document.getElementById('cake-form');
    const steps = Array.from(form.querySelectorAll('[data-step]'));
    let current = 1;

    const themeSelect = document.getElementById('cake-theme');
    function updateThemeFields() {
        const active = themeSelect.value !== 'None';
        document.getElementById('cake-character-wrap').hidden = !active;
        document.getElementById('cake-character').disabled = !active;
    }
    themeSelect.addEventListener('change', updateThemeFields);
    updateThemeFields();

    /* ---- wizard navigation ---- */
    function paintChips() {
        document.querySelectorAll('[data-step-chip]').forEach((chip) => {
            const n = Number(chip.dataset.stepChip);
            const active = n === current;
            const num = chip.querySelector('[data-chip-num]');
            num.classList.toggle('bg-brand-500', active);
            num.classList.toggle('text-white', active);
            num.classList.toggle('bg-slate-200', !active);
            num.classList.toggle('text-slate-500', !active);
            const label = chip.querySelector('[data-chip-label]');
            label.classList.toggle('text-brand-600', active);
            label.classList.toggle('text-slate-400', !active);
        });
    }

    function goStep(n) {
        current = n;
        steps.forEach((s) => s.classList.toggle('hidden', Number(s.dataset.step) !== n));
        paintChips();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (n === 3) fillReview();
    }

    function setError(key, message) {
        const el = form.querySelector(`[data-error-for="${key}"]`);
        if (!el) return;
        el.textContent = message || '';
        el.classList.toggle('hidden', !message);
    }

    function validateStep(n) {
        if (n === 1) {
            let ok = true;
            let firstBad = null;
            [
                ['occasion', form.occasion, 'Please select an occasion.'],
                ['needed_by', form.needed_by, 'Please pick the date you need the cake by.'],
                ['flavor', form.flavor, 'Please select a flavor.'],
                ['size', form.size, 'Please select a size.'],
            ].forEach(([key, field, message]) => {
                if (!field.value.trim()) { setError(key, message); ok = false; firstBad = firstBad || field; }
                else setError(key, '');
            });
            if (!form.querySelector('input[name="frosting_color"]:checked')) { setError('frosting_color', 'Please pick a frosting color.'); ok = false; }
            else setError('frosting_color', '');
            const d = form.description.value.trim();
            if (d.length < 10) { setError('description', 'Please describe your dream cake (at least 10 characters).'); ok = false; firstBad = firstBad || form.description; }
            else setError('description', '');
            if (firstBad) firstBad.focus();
            return ok;
        }
        if (n === 2) {
            let ok = true;
            if (ccMode() === 'delivery' && !form.address.value.trim()) { setError('address', 'Please enter your delivery address.'); ok = false; }
            else setError('address', '');
            if (ccMode() === 'pickup' && !form.querySelector('input[name="fulfillment_branch"]:checked')) { setError('fulfillment_branch', 'Please choose the branch to pick up at.'); ok = false; }
            else setError('fulfillment_branch', '');
            const name = form.name.value.trim();
            if (name.length < 2) { setError('name', 'Please enter your full name.'); ok = false; } else setError('name', '');
            const email = form.email.value.trim();
            if (!/^\S+@\S+\.\S+$/.test(email)) { setError('email', 'Please enter a valid email address.'); ok = false; } else setError('email', '');
            if (!ok) form.querySelector('#cc-name').focus();
            return ok;
        }
        return true;
    }

    /* ---- delivery / pickup + branch picker (mirrors checkout's step 1) ---- */
    const ccMode = () => document.getElementById('cc-delivery-type').value;

    function paintCcMode() {
        const mode = ccMode();
        document.querySelectorAll('.cc-mode-card').forEach((btn) => {
            const on = btn.dataset.ccMode === mode;
            btn.classList.toggle('border-brand-400', on);
            btn.classList.toggle('bg-brand-50/60', on);
            btn.classList.toggle('ring-2', on);
            btn.classList.toggle('ring-brand-500/20', on);
            btn.classList.toggle('border-slate-200', !on);
        });
        document.getElementById('cc-address-wrap').classList.toggle('hidden', mode !== 'delivery');
        document.getElementById('cc-branch-wrap').classList.toggle('hidden', mode !== 'pickup');
    }

    document.querySelectorAll('.cc-mode-card').forEach((btn) => btn.addEventListener('click', () => {
        document.getElementById('cc-delivery-type').value = btn.dataset.ccMode;
        paintCcMode();
    }));
    paintCcMode();

    // Saved-address dropdown fills the textarea; "new" clears it for typing.
    const savedAddress = document.getElementById('cc-saved-address');
    if (savedAddress) savedAddress.addEventListener('change', () => {
        if (savedAddress.value === '__new__') {
            form.address.value = '';
            form.address.focus();
        } else if (savedAddress.value) {
            form.address.value = savedAddress.value;
            setError('address', '');
        }
    });

    document.getElementById('cc-branch-search').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        let shown = 0;
        document.querySelectorAll('.cc-branch-option').forEach((opt) => {
            const show = !q || opt.dataset.search.includes(q);
            opt.classList.toggle('hidden', !show);
            if (show) shown++;
        });
        document.getElementById('cc-branch-no-match').classList.toggle('hidden', shown !== 0);
    });

    /* ---- find nearest store (geolocation, same as checkout) ---- */
    const nearestBtn = document.getElementById('cc-find-nearest');
    const nearestStatus = document.getElementById('cc-nearest-status');

    function setNearestStatus(text, isError) {
        nearestStatus.textContent = text;
        nearestStatus.classList.toggle('hidden', !text);
        nearestStatus.classList.toggle('text-red-600', !!isError);
        nearestStatus.classList.toggle('text-slate-500', !isError);
    }

    function haversineKm(lat1, lng1, lat2, lng2) {
        const toRad = (d) => (d * Math.PI) / 180;
        const a = Math.sin(toRad(lat2 - lat1) / 2) ** 2
            + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(toRad(lng2 - lng1) / 2) ** 2;
        return 2 * 6371 * Math.asin(Math.sqrt(a));
    }

    nearestBtn.addEventListener('click', () => {
        if (!navigator.geolocation) { setNearestStatus('Location is not supported by this browser.', true); return; }
        nearestBtn.disabled = true;
        setNearestStatus('Locating you…');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                nearestBtn.disabled = false;
                const { latitude: lat, longitude: lng } = pos.coords;
                const pinned = Array.from(document.querySelectorAll('.cc-branch-option'))
                    .filter((opt) => Number.isFinite(Number(opt.dataset.lat)) && Number.isFinite(Number(opt.dataset.lng)));
                if (!pinned.length) { setNearestStatus('No branches have map pins yet.', true); return; }
                const dist = (opt) => haversineKm(lat, lng, Number(opt.dataset.lat), Number(opt.dataset.lng));
                const nearest = pinned.reduce((best, opt) => dist(opt) < dist(best) ? opt : best);
                // Select it, clear any search filter, and bring it into view.
                document.getElementById('cc-branch-search').value = '';
                document.querySelectorAll('.cc-branch-option').forEach((opt) => opt.classList.remove('hidden'));
                document.getElementById('cc-branch-no-match').classList.add('hidden');
                nearest.querySelector('input[type="radio"]').checked = true;
                nearest.scrollIntoView({ block: 'nearest' });
                setError('fulfillment_branch', '');
                const km = dist(nearest);
                const branchName = nearest.querySelector('input[type="radio"]').value;
                setNearestStatus(`Nearest store: ${branchName} — ${km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km'} away`);
            },
            (err) => {
                nearestBtn.disabled = false;
                setNearestStatus(err.code === err.PERMISSION_DENIED
                    ? 'Location permission denied — allow location access and try again.'
                    : 'Could not get your location. Please try again.', true);
            },
            { enableHighAccuracy: true, timeout: 10000 },
        );
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-next]')) { if (validateStep(current)) goStep(current + 1); }
        if (e.target.closest('[data-back]')) goStep(current - 1);
    });

    /* ---- review summary ---- */
    function fillReview() {
        const color = form.querySelector('input[name="frosting_color"]:checked');
        const file = form.reference_image.files[0];
        const branch = form.querySelector('input[name="fulfillment_branch"]:checked');
        const rows = [
            ['Occasion', form.occasion.value],
            ['Needed by', form.needed_by.value],
            ['Flavor', form.flavor.value.trim()],
            ['Size', form.size.value],
            ['Shape', form.shape.value],
            ['Theme', form.theme.value === 'None' ? '' : form.theme.value],
            ['Character', form.theme.value === 'None' ? '' : form.character_name.value.trim()],
            ['Frosting', color ? color.value : ''],
            ['Receive via', ccMode() === 'pickup' ? '🏪 Pickup' : '🚚 Delivery'],
            ['Address', ccMode() === 'delivery' ? form.address.value.trim() : ''],
            ['Branch', ccMode() === 'pickup' && branch ? branch.value : ''],
            ['Description', form.description.value.trim()],
            ['Reference', [file ? file.name : '', form.reference_link.value.trim()].filter(Boolean).join(' · ')],
            ['Name', form.name.value.trim()],
            ['Email', form.email.value.trim()],
            ['Mobile', form.phone.value.trim()],
        ].filter(([, v]) => v);
        document.getElementById('review-list').innerHTML = rows.map(([k, v]) => `
            <div class="flex gap-4">
                <dt class="w-24 shrink-0 text-slate-500">${k}</dt>
                <dd class="min-w-0 flex-1 break-words font-medium text-navy-800">${String(v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))}</dd>
            </div>`).join('');
    }

    /* ---- reference upload box ---- */
    const uploadBox = document.getElementById('upload-box');
    const fileInput = document.getElementById('reference_image');
    uploadBox.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        const label = document.getElementById('upload-label');
        const preview = document.getElementById('upload-preview');
        if (!file) { label.textContent = 'Click to upload an image'; preview.classList.add('hidden'); return; }
        label.textContent = file.name;
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
    });

    /* ---- live cake preview ---- */
    // Tiers by size, frosting hex from the checked swatch, topper by occasion.
    const TOPPERS = { Birthday: 'candles', Wedding: '💍', Anniversary: '💖', Graduation: '🎓', 'Baby Shower': '🍼', Other: '⭐' };
    // Sizes are editor-typed strings (Site Editor → Custom Cake Page), so
    // parse loosely: "N-Tier" stacks tiers, a leading inch number sets width.
    function tiersFor(size) {
        const m = size.match(/(\d+)\s*-?\s*tier/i);
        return m ? Math.min(3, Math.max(1, Number(m[1]))) : 1;
    }
    function widthFor(size) {
        const m = size.match(/(\d+)\s*(?:"|”|in\b|inch)/i) || size.match(/^(\d+)/);
        return m ? Math.min(140, Math.max(72, 60 + Number(m[1]) * 7)) : 106;
    }
    function renderCake() {
        const svg = document.getElementById('cake-preview');
        const size = form.size.value;
        const occasion = form.occasion.value;
        const checked = form.querySelector('input[name="frosting_color"]:checked');
        const hex = checked ? checked.dataset.hex : '#fbe3c4';
        const tiers = tiersFor(size);
        const shape = form.shape.value;
        const baseW = widthFor(size) * (shape === 'Rectangle' ? 1.15 : 1);
        const tierH = tiers === 1 ? 52 : (tiers === 2 ? 42 : 36);
        const cx = 100, bottom = 148;
        let parts = [`<ellipse cx="${cx}" cy="${bottom + 6}" rx="78" ry="10" fill="#e9dcc9"/>`];
        for (let t = 0; t < tiers; t++) {
            const w = baseW - t * 26;
            const y = bottom - tierH * (t + 1);
            parts.push(`<rect x="${cx - w / 2}" y="${y}" width="${w}" height="${tierH}" rx="10" fill="${hex}" stroke="rgba(0,0,0,0.08)"/>`);
            if (shape !== 'Round') {
                parts.push(`<rect x="${cx - w / 2}" y="${y - 5}" width="${w}" height="12" rx="2" fill="${hex}" stroke="rgba(0,0,0,0.08)"/>`);
                continue;
            }
            parts.push(`<ellipse cx="${cx}" cy="${y}" rx="${w / 2}" ry="7" fill="${hex}" stroke="rgba(0,0,0,0.08)"/>`);
            parts.push(`<ellipse cx="${cx}" cy="${y}" rx="${w / 2 - 6}" ry="4.5" fill="rgba(255,255,255,0.45)"/>`);
        }
        const topY = bottom - tierH * tiers;
        if ((TOPPERS[occasion] || '') === 'candles') {
            [-16, 0, 16].forEach((dx) => {
                parts.push(`<rect x="${cx + dx - 2}" y="${topY - 20}" width="4" height="18" rx="2" fill="#f59b3f"/>`);
                parts.push(`<circle cx="${cx + dx}" cy="${topY - 24}" r="3.5" fill="#ffd166"/>`);
            });
        } else if (occasion && TOPPERS[occasion]) {
            parts.push(`<text x="${cx}" y="${topY - 12}" font-size="20" text-anchor="middle">${TOPPERS[occasion]}</text>`);
        }
        svg.innerHTML = parts.join('');
    }
    form.addEventListener('change', (e) => {
        if (['size', 'shape', 'occasion'].includes(e.target.name) || e.target.name === 'frosting_color') renderCake();
    });
    renderCake();

    document.getElementById('cake-reset-options').addEventListener('click', () => {
        ['occasion', 'needed_by', 'flavor', 'size', 'character_name'].forEach((name) => {
            form.elements[name].value = '';
            setError(name, '');
        });
        form.querySelectorAll('[name="frosting_color"]').forEach((input) => { input.checked = false; });
        form.querySelectorAll('[name="shape"]').forEach((input) => { input.checked = input.value === 'Round'; });
        themeSelect.value = 'None';
        ['frosting_color', 'shape', 'theme'].forEach((name) => setError(name, ''));
        // Notify both the dependent character field and the 3D preview.
        themeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        renderCake();
        form.dispatchEvent(new Event('cake:reset'));
        document.getElementById('cake-decoration-note').textContent = 'Cake selections reset. Choose a flavor and occasion to start again.';
    });

    paintChips();
    // A failed server validation reloads with errors — reopen the right step.
    @if($errors->has('name') || $errors->has('email') || $errors->has('phone'))
        goStep(2);
    @endif
})();
</script>
@endunless
@if($editable ?? false)
    @include('partials._editor-bridge')
@endif
@include('partials.assistant-widget')
</body>
</html>
