<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create your account — BW Superbakeshop</title>
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
            <img src="/images/bakery-interior.jpg" alt="" aria-hidden="true" loading="lazy" decoding="async" class="h-full w-full object-cover">
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
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-navy-700 to-navy-900 text-3xl shadow-lg ring-4 ring-brand-500/20">
                            🏪
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-navy-800">Create your account</h2>
                        <p class="mt-1 text-sm text-slate-500">Join the Bakery Ordering System</p>
                    </div>

                    @error('form')
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    <form method="POST" action="{{ route('register') }}" novalidate class="space-y-4">
                        @csrf

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Name</label>
                            <input type="text" name="name" required minlength="2" value="{{ old('name') }}" placeholder="Jane Baker"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Email</label>
                            <input type="email" name="email" required autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Contact number</label>
                            <input type="tel" name="contact_number" required inputmode="tel" autocomplete="tel" maxlength="20"
                                pattern="[\d+\-\s()]{7,}" title="Enter a valid contact number (digits only)"
                                value="{{ old('contact_number') }}" placeholder="e.g. 0917 123 4567" data-phone-input
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            @error('contact_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Password</label>
                            <div class="relative">
                                <input type="password" name="password" required minlength="6" placeholder="At least 6 characters" data-password-field
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-3 pr-10 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                @include('auth._password-toggle')
                            </div>
                            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Confirm password</label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" required placeholder="Re-enter your password" data-password-field
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-3 pr-10 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                @include('auth._password-toggle')
                            </div>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40">
                            Create account
                        </button>
                    </form>

                    {{-- Social sign-in — each button togglable in the Site
                         Editor (Login Page section); the "or" divider only
                         shows when at least one is on. --}}
                    @php($showGoogle = $authPanel['showGoogle'] ?? true)
                    @php($showFacebook = $authPanel['showFacebook'] ?? true)
                    @if($showGoogle || $showFacebook)
                    <div class="my-5 flex items-center gap-3">
                        <span class="h-px flex-1 bg-slate-200"></span>
                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">or</span>
                        <span class="h-px flex-1 bg-slate-200"></span>
                    </div>
                    @endif

                    @if($showGoogle)
                    <a href="{{ route('oauth.redirect', 'google') }}"
                        class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-semibold text-navy-800 transition hover:bg-slate-50 focus:ring-2 focus:ring-brand-500/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M21.6 12.227c0-.709-.064-1.39-.182-2.045H12v3.868h5.382a4.6 4.6 0 0 1-1.996 3.018v2.51h3.232c1.891-1.742 2.982-4.305 2.982-7.35Z" />
                            <path fill="#34A853" d="M12 21.6c2.7 0 4.964-.895 6.618-2.422l-3.232-2.51c-.895.6-2.04.955-3.386.955-2.605 0-4.81-1.76-5.596-4.124H3.064v2.59A9.996 9.996 0 0 0 12 21.6Z" />
                            <path fill="#FBBC05" d="M6.404 13.499a5.99 5.99 0 0 1 0-3.998v-2.59H3.064a10.003 10.003 0 0 0 0 9.178l3.34-2.59Z" />
                            <path fill="#EA4335" d="M12 5.377c1.468 0 2.786.505 3.823 1.496l2.868-2.868C16.96 2.39 14.695 1.5 12 1.5A9.996 9.996 0 0 0 3.064 6.91l3.34 2.59C7.19 7.137 9.395 5.377 12 5.377Z" />
                        </svg>
                        Continue with Google
                    </a>
                    @endif

                    @if($showFacebook)
                    <a href="{{ route('oauth.redirect', 'facebook') }}"
                        class="{{ $showGoogle ? 'mt-3' : '' }} flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white py-2.5 text-sm font-semibold text-navy-800 transition hover:bg-slate-50 focus:ring-2 focus:ring-brand-500/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="#1877F2" aria-hidden="true">
                            <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.02 4.39 11.01 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.08 24 18.09 24 12.07Z" />
                        </svg>
                        Continue with Facebook
                    </a>
                    @endif

                    <p class="mt-6 text-center text-xs text-slate-400">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Phone numbers only: keep digits plus the few valid symbols (lib/phone.js).
            document.querySelectorAll('[data-phone-input]').forEach(function (input) {
                input.addEventListener('input', function () {
                    var clean = input.value.replace(/[^\d+\-\s()]/g, '');
                    if (clean !== input.value) input.value = clean;
                });
            });

            // Show/hide password toggles (EyeIcons port).
            document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var input = btn.parentElement.querySelector('[data-password-field]');
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.querySelector('[data-eye]').classList.toggle('hidden', show);
                    btn.querySelector('[data-eye-off]').classList.toggle('hidden', !show);
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });
        });
    </script>
</body>
</html>
