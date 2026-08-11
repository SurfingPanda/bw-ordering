{{-- Public "Contact Us" page — a single message form (guests may send one
     too, no login required). Submissions land in contact_messages for staff
     to review at /admin/contact-messages (see Admin\ContactController). --}}
@php
    $metaTitle = 'BW Superbakeshop | Contact Us';
    $metaDescription = 'Have a question or feedback for bw Superbakeshop? Send us a message and our team will get back to you.';
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
    <div class="min-h-screen bg-white text-navy-800">
        {{-- header — same look as the landing page's nav (sticky navy bar,
             circular logo badge overflowing the bottom edge), but with only
             a "Back to home" link instead of the full Menu/Store/Partner
             nav and Sign In/Order Now buttons. --}}
        <header class="sticky top-0 z-50 bg-navbar">
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
                        <img src="/images/logo (1).png" alt="bw Superbakeshop" width="225" height="225" class="h-full w-full object-contain">
                    </span>
                </a>

                <a href="/" class="text-sm font-semibold text-white/90 transition hover:text-white">← Back to home</a>
            </nav>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
            @if(session('contact_success'))
                {{-- success state (replaces the form after a submit) --}}
                <div class="rounded-3xl border border-slate-100 bg-white p-10 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-8 w-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <h1 class="mt-5 font-brand text-2xl font-bold text-navy-800">Message sent! ✉️</h1>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">Thanks for reaching out — our team has your message and will get back to you at the email or number you provided.</p>
                    <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('menu') }}" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">Browse the menu</a>
                        <a href="/" class="rounded-full border border-slate-300 px-7 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">Back to home</a>
                    </div>
                </div>
            @else
                <div class="text-center">
                    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-500">Get in touch</span>
                    <h1 class="mt-2 font-brand text-4xl font-bold text-navy-800">Contact Us</h1>
                    <p class="mx-auto mt-3 max-w-md text-sm text-slate-500">Questions, feedback, or just want to say hi? Send us a message and our team will get back to you.</p>
                </div>

                @if($errors->any())
                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="contact-form" method="POST" action="{{ route('contact.store') }}" novalidate class="mt-8 space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-navy-800">Name <span class="text-red-500">*</span></span>
                            <input type="text" name="name" required value="{{ old('name', $prefillName) }}"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-navy-800">Email <span class="text-red-500">*</span></span>
                            <input type="email" name="email" required value="{{ old('email', $prefillEmail) }}"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </label>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-navy-800">Phone <span class="font-normal text-slate-400">(optional)</span></span>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-sm font-semibold text-navy-800">Subject <span class="font-normal text-slate-400">(optional)</span></span>
                            <input type="text" name="subject" value="{{ old('subject') }}" placeholder="What's this about?"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </label>
                    </div>
                    <label class="block">
                        <span class="mb-1 block text-sm font-semibold text-navy-800">Message <span class="text-red-500">*</span></span>
                        <textarea name="message" rows="5" required placeholder="Tell us what's on your mind…"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">{{ old('message') }}</textarea>
                    </label>
                    <button type="submit" data-submit-button class="flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                        <span data-submit-spinner class="hidden h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        <span data-submit-label>Send message</span>
                    </button>
                </form>

                <script>
                    // A real (non-AJAX) form submit — the browser doesn't
                    // navigate until the server responds, which can take a
                    // moment. Without feedback, that gap reads as a dead
                    // button, so disable it and swap in a spinner immediately.
                    document.getElementById('contact-form').addEventListener('submit', function () {
                        var button = this.querySelector('[data-submit-button]');
                        button.disabled = true;
                        button.querySelector('[data-submit-spinner]').classList.remove('hidden');
                        button.querySelector('[data-submit-label]').textContent = 'Sending…';
                    });
                </script>
            @endif
        </main>

        {{-- footer --}}
        @include('partials.site-footer', ['f' => $footerContent, 'social' => $social])
    </div>
</body>
</html>
