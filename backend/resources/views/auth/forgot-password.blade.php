<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password — BW Superbakeshop</title>
    {{-- Utility page, no organic search intent — keep it out of the index
         rather than let it show up as a thin, description-less result. --}}
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Pacifico&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-navy-700 via-navy-800 to-navy-900 p-4 sm:p-6">
        {{-- bakery backdrop --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <img data-editable="authPanel.backgroundImage" src="{{ $authPanel['backgroundImage'] ?? '/images/bakery-interior.jpg' }}" alt="" aria-hidden="true" loading="lazy" decoding="async" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-br from-navy-900/85 via-navy-900/80 to-navy-800/80"></div>
            <div class="absolute -bottom-44 -right-24 h-[30rem] w-[30rem] rounded-full bg-brand-600/15 blur-3xl"></div>
        </div>

        <div class="relative z-10 grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl lg:grid-cols-[1fr_1.1fr]">
            {{-- brand panel --}}
            @include('auth._brand-panel')

            {{-- form panel --}}
            <div class="flex flex-col justify-center px-7 py-10 sm:px-12">
                <div class="mx-auto w-full max-w-sm">
                    <div class="mb-6 flex flex-col items-center text-center">
                        <h2 class="mt-4 text-2xl font-bold text-navy-800">Forgot Password?</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Enter your email and we'll send you <br class="hidden sm:block">
                            a link to reset your password
                        </p>
                    </div>

                    @if(session('resetSent'))
                        {{-- sent state — success regardless of whether the
                             account exists, so emails can't be enumerated --}}
                        <div class="space-y-6">
                            <div class="flex flex-col items-center text-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M22 13V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8" />
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                        <path d="m16 19 2 2 4-4" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm text-slate-600">
                                    If an account exists for
                                    <span class="font-semibold text-navy-800">{{ session('resetSent') }}</span>,
                                    a reset link is on its way. Check your inbox (and spam folder).
                                </p>
                            </div>
                            <a href="{{ route('password.request') }}"
                                class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 text-center text-sm font-semibold text-navy-800 transition hover:bg-slate-50 focus:ring-2 focus:ring-brand-500/30">
                                Send to a different email
                            </a>
                        </div>
                    @else
                        @error('email')
                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {{ $message }}
                            </div>
                        @enderror

                        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="mb-1 block text-sm font-medium text-navy-800">Email</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="2" y="4" width="20" height="16" rx="2" />
                                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                    </svg>
                                    <input type="email" name="email" autocomplete="email" required value="{{ old('email') }}" placeholder="Enter your email"
                                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                </div>
                            </div>

                            <button type="submit"
                                class="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40">
                                Send Reset Link
                            </button>
                        </form>
                    @endif

                    <p class="mt-6 text-center text-xs text-slate-400">
                        Remember your password?
                        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">Back to sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
