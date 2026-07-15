{{-- Account settings — Blade port of the SPA's Profile.jsx. Two independent
     forms (account details / change password) posting to ProfileController,
     each with its own named error bag + success flash. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BW Superbakeshop | My Profile</title>
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-navy-50/40 text-navy-800">

<header class="border-b border-slate-100 bg-white">
    <div class="mx-auto flex max-w-3xl items-start justify-between gap-4 px-4 py-6 sm:px-6">
        <div class="flex items-center gap-3">
            <a href="/" class="shrink-0"><img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-10 w-auto"></a>
            <div>
                <h1 class="text-2xl font-bold text-navy-800">My Profile</h1>
                <p class="text-sm text-slate-500">Manage your account details.</p>
            </div>
        </div>
        <a href="{{ route('menu') }}" class="text-sm font-medium text-slate-500 transition hover:text-brand-600">← Back to menu</a>
    </div>
</header>

<main class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
    {{-- identity summary --}}
    <section class="flex items-center gap-4 rounded-2xl bg-white p-6 shadow-sm">
        @php
            $initials = collect(explode(' ', trim($profile['name'] ?: ($profile['email'] ?: '?'))))
                ->filter()->map(fn ($p) => mb_substr($p, 0, 1))->slice(0, 2)->implode('');
            $initials = mb_strtoupper($initials ?: '?');
        @endphp
        @if($profile['avatar'])
            {{-- Fall back to initials if the avatar (e.g. a stale/blocked Google
                 photo URL) fails to load, so we never show a broken-image icon. --}}
            <img src="{{ $profile['avatar'] }}" alt="{{ $profile['name'] }}" referrerpolicy="no-referrer"
                onerror="this.remove(); const i = document.getElementById('avatar-initials'); i.classList.remove('hidden'); i.classList.add('flex');"
                class="h-16 w-16 rounded-full object-cover ring-2 ring-brand-500/20">
        @endif
        <span id="avatar-initials"
            class="{{ $profile['avatar'] ? 'hidden' : 'flex' }} h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-navy-700 to-navy-900 text-xl font-bold text-white ring-2 ring-brand-500/20">
            {{ $initials }}
        </span>
        <div class="min-w-0">
            <p class="truncate text-lg font-bold text-navy-800">{{ $profile['name'] }}</p>
            <p class="truncate text-sm text-slate-500">{{ $profile['email'] }}</p>
        </div>
    </section>

    {{-- account details --}}
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-navy-800">Account details</h2>
        <p class="mt-1 text-sm text-slate-500">Update your name and contact number.</p>

        @if($errors->info->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->info->first() }}
            </div>
        @endif
        @if(session('info_success'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('info_success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.info') }}" novalidate class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-navy-800">Email</label>
                <input type="email" value="{{ $profile['email'] }}" disabled
                    class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Email is tied to your sign-in and can't be changed here.</p>
            </div>
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-navy-800">Name</label>
                <input type="text" id="name" name="name" required minlength="2" value="{{ old('name', $profile['name']) }}" placeholder="Jane Baker"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            </div>
            <div>
                <label for="contact_number" class="mb-1 block text-sm font-medium text-navy-800">Contact number</label>
                <input type="tel" id="contact_number" name="contact_number" required inputmode="tel" autocomplete="tel" maxlength="20"
                    value="{{ old('contact_number', $profile['contact']) }}" placeholder="e.g. 0917 123 4567"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            </div>
            <button type="submit"
                class="rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40">
                Save changes
            </button>
        </form>
    </section>

    {{-- password --}}
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-navy-800">Change password</h2>
        <p class="mt-1 text-sm text-slate-500">Set a new password for signing in with email.</p>

        @if($errors->password->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->password->first() }}
            </div>
        @endif
        @if(session('pw_success'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('pw_success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}" novalidate class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-navy-800">New password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters" data-password-field
                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-3 pr-10 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                    <button type="button" id="toggle-password" aria-label="Show password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600">
                        <svg id="eye-open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg id="eye-closed" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.45 18.45 0 0 1 5.06-5.94" /><path d="M9.9 4.24A9.12 9.12 0 0 1 12 5c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19" /><path d="M14.12 14.12A3 3 0 1 1 9.88 9.88" /><line x1="2" y1="2" x2="22" y2="22" />
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-navy-800">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter your new password" data-password-field
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            </div>
            <button type="submit"
                class="rounded-lg bg-gradient-to-r from-navy-700 to-navy-800 px-6 py-2.5 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-navy-800/30 transition hover:from-navy-800 hover:to-navy-900 focus:ring-2 focus:ring-navy-700/40">
                Update password
            </button>
        </form>
    </section>
</main>

<script>
    // Same behaviors as the old Profile.jsx: keep the contact field to valid
    // phone characters, and one eye toggle that shows/hides both password fields.
    document.getElementById('contact_number').addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/[^\d+\-\s()]/g, '');
    });

    document.getElementById('toggle-password').addEventListener('click', function () {
        const fields = document.querySelectorAll('[data-password-field]');
        const show = fields[0].type === 'password';
        fields.forEach((f) => { f.type = show ? 'text' : 'password'; });
        document.getElementById('eye-open').classList.toggle('hidden', show);
        document.getElementById('eye-closed').classList.toggle('hidden', !show);
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
</script>
</body>
</html>
