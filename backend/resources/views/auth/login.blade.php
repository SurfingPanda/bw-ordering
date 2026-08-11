<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — BW Superbakeshop</title>
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
                        <h2 class="mt-4 text-2xl font-bold text-navy-800">Welcome Back!</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Please sign in to continue to your <br class="hidden sm:block">
                            BW Ordering System
                        </p>
                    </div>

                    @if(session('status'))
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @error('email')
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    {{-- Registered but never clicked the confirmation link: GoTrue
                         refuses the password login, so instead of the misleading
                         "invalid password" we prompt for confirmation + offer a
                         resend (SessionController::store flashes unconfirmed_email). --}}
                    @if(session('unconfirmed_email'))
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <p class="font-semibold">Confirm your email to sign in</p>
                            <p class="mt-1 text-amber-700">
                                We sent a confirmation link to
                                <span class="font-medium">{{ session('unconfirmed_email') }}</span>.
                                Click it to activate your account, then sign in here.
                            </p>
                            <form method="POST" action="{{ route('confirmation.resend') }}" class="mt-2">
                                @csrf
                                <input type="hidden" name="email" value="{{ session('unconfirmed_email') }}">
                                <button type="submit" class="font-semibold text-brand-600 underline-offset-2 transition hover:underline">
                                    Resend confirmation email
                                </button>
                            </form>
                        </div>
                    @endif

                    @if(request('oauth') === 'failed')
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            Social sign-in didn't complete. Please try again.
                        </div>
                    @endif

                    <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Email</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <input type="email" name="email" id="login-email" autocomplete="email" required value="{{ old('email') }}" placeholder="Enter your email"
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Password</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <input type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-10 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <label class="flex items-center gap-2 text-slate-600">
                                <input type="checkbox" name="remember" id="login-remember" class="h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                                Remember me
                            </label>
                            <a href="{{ route('password.request') }}" class="font-medium text-brand-600 hover:text-brand-500">Forgot Password?</a>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40">
                            Sign In
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
                        Don't have an account?
                        <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-500">Create one</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        // "Remember me" only ever remembers the email, never the password —
        // the password field's autocomplete="current-password" already lets
        // the browser's own password manager offer to fill that in securely;
        // this app has no business holding onto it itself.
        var STORAGE_KEY = 'bw_remembered_email';
        var form = document.getElementById('login-form');
        var emailField = document.getElementById('login-email');
        var rememberBox = document.getElementById('login-remember');

        var remembered = '';
        try { remembered = window.localStorage.getItem(STORAGE_KEY) || ''; } catch (e) {}
        if (remembered) {
            if (!emailField.value) emailField.value = remembered;
            rememberBox.checked = true;
        }

        form.addEventListener('submit', function () {
            try {
                if (rememberBox.checked) {
                    window.localStorage.setItem(STORAGE_KEY, emailField.value.trim());
                } else {
                    window.localStorage.removeItem(STORAGE_KEY);
                }
            } catch (e) {}
        });
    })();
    </script>
    @if($editable ?? false)
        @include('partials._editor-bridge')
    @endif
</body>
</html>
