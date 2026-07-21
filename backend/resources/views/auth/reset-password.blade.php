<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password — BW Superbakeshop</title>
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
                        <h2 class="mt-4 text-2xl font-bold text-navy-800">Reset Password</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Choose a new password for <br class="hidden sm:block">
                            your BW Ordering account
                        </p>
                    </div>

                    @if($errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    {{-- shown by the script below when the link carries no
                         usable recovery token (expired, used, or malformed) --}}
                    <div id="invalid-link" class="mb-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        This reset link is invalid or has expired.
                        <a href="{{ route('password.request') }}" class="font-semibold underline">Request a new one</a>.
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                        @csrf
                        {{-- Supabase's recovery link lands with the short-lived
                             access token in the URL hash (never sent to the
                             server); the script below moves it in here. old()
                             keeps it across validation round-trips. --}}
                        <input type="hidden" name="access_token" id="access_token" value="{{ old('access_token') }}">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">New Password</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <input type="password" name="password" id="password" autocomplete="new-password" required minlength="6" placeholder="Enter a new password"
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-10 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                <button type="button" id="toggle-password" aria-label="Show password"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Confirm Password</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" required minlength="6" placeholder="Re-enter the new password"
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            </div>
                        </div>

                        <button type="submit" id="update-btn"
                            class="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-60">
                            Update Password
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs text-slate-400">
                        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">Back to sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Move the recovery token from the URL hash into the form, then scrub
        // it from the address bar. With no token (and none preserved via
        // old()), the link is dead — say so and disable the submit.
        (function () {
            var params = new URLSearchParams(window.location.hash.slice(1));
            var tokenField = document.getElementById('access_token');
            var token = params.get('access_token');
            if (token) {
                tokenField.value = token;
                history.replaceState(null, '', window.location.pathname);
            }
            if (!tokenField.value) {
                document.getElementById('invalid-link').classList.remove('hidden');
                document.getElementById('update-btn').disabled = true;
            }

            // One eye toggle shows/hides both password fields, like the SPA.
            document.getElementById('toggle-password').addEventListener('click', function () {
                var pw = document.getElementById('password');
                var confirm = document.getElementById('password_confirmation');
                var show = pw.type === 'password';
                pw.type = confirm.type = show ? 'text' : 'password';
                this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        })();
    </script>
</body>
</html>
