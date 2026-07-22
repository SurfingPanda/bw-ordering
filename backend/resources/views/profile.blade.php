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
<body class="min-h-screen bg-brand-50 text-navy-800">

<header class="border-b border-slate-100 bg-white">
    <div class="mx-auto flex max-w-3xl items-start justify-between gap-4 px-4 py-6 sm:px-6">
        <div class="flex items-center gap-3">
            <a href="/" class="shrink-0"><img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24"></a>
            <div>
                <h1 class="text-2xl font-bold text-navy-800">My Profile</h1>
                <p class="text-sm text-slate-500">Manage your account details.</p>
            </div>
        </div>
        <a href="{{ route('menu') }}" class="text-sm font-medium text-slate-500 transition hover:text-brand-600">← Back to menu</a>
    </div>
</header>

<main class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
    @php
        $initials = collect(explode(' ', trim($profile['name'] ?: ($profile['email'] ?: '?'))))
            ->filter()->map(fn ($p) => mb_substr($p, 0, 1))->slice(0, 2)->implode('');
        $initials = mb_strtoupper($initials ?: '?');

        $memberSince = null;
        if (!empty($profile['memberSince'])) {
            try {
                $memberSince = \Illuminate\Support\Carbon::parse($profile['memberSince'])->format('F Y');
            } catch (\Throwable) {
                $memberSince = null;
            }
        }

        // Small, known set (google/facebook OAuth + email/password — see
        // routes/web.php's oauth.redirect) — "email" covers a normal
        // password sign-up alongside any OAuth identity also on the account.
        $providerMeta = [
            'google' => ['label' => 'Google', 'icon' => '<path fill="#4285F4" d="M21.6 12.227c0-.709-.064-1.39-.182-2.045H12v3.868h5.382a4.6 4.6 0 0 1-1.996 3.018v2.51h3.232c1.891-1.742 2.982-4.305 2.982-7.35Z" /><path fill="#34A853" d="M12 21.6c2.7 0 4.964-.895 6.618-2.422l-3.232-2.51c-.895.6-2.04.955-3.386.955-2.605 0-4.81-1.76-5.596-4.124H3.064v2.59A9.996 9.996 0 0 0 12 21.6Z" /><path fill="#FBBC05" d="M6.404 13.499a5.99 5.99 0 0 1 0-3.998v-2.59H3.064a10.003 10.003 0 0 0 0 9.178l3.34-2.59Z" /><path fill="#EA4335" d="M12 5.377c1.468 0 2.786.505 3.823 1.496l2.868-2.868C16.96 2.39 14.695 1.5 12 1.5A9.996 9.996 0 0 0 3.064 6.91l3.34 2.59C7.19 7.137 9.395 5.377 12 5.377Z" />'],
            'facebook' => ['label' => 'Facebook', 'icon' => '<path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.02 4.39 11.01 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.69.24 2.69.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.08 24 18.09 24 12.07Z" />'],
            'email' => ['label' => 'Email & password', 'icon' => '<path fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4z" /><path fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m4 6 8 7 8-7" />'],
        ];
    @endphp

    {{-- identity summary: gradient banner + avatar overlapping it, plus the
         "member since" / "how you sign in" details that were missing before --}}
    <section class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="h-20 bg-gradient-to-r from-brand-500 to-brand-600 sm:h-24"></div>
        <div class="-mt-10 flex flex-col items-center gap-4 px-6 pb-6 text-center sm:-mt-10 sm:flex-row sm:items-end sm:text-left">
            @if($profile['avatar'])
                {{-- Fall back to initials if the avatar (e.g. a stale/blocked Google
                     photo URL) fails to load, so we never show a broken-image icon. --}}
                <img src="{{ $profile['avatar'] }}" alt="{{ $profile['name'] }}" referrerpolicy="no-referrer"
                    onerror="this.remove(); const i = document.getElementById('avatar-initials'); i.classList.remove('hidden'); i.classList.add('flex');"
                    class="h-20 w-20 shrink-0 rounded-full object-cover ring-4 ring-white">
            @endif
            <span id="avatar-initials"
                class="{{ $profile['avatar'] ? 'hidden' : 'flex' }} h-20 w-20 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-navy-700 to-navy-900 text-2xl font-bold text-white ring-4 ring-white">
                {{ $initials }}
            </span>
            <div class="min-w-0 flex-1 sm:pb-1">
                <p class="truncate text-lg font-bold text-navy-800">{{ $profile['name'] }}</p>
                <p class="truncate text-sm text-slate-500">{{ $profile['email'] }}</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-end sm:pb-1">
                @if($memberSince)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-3 py-1.5 text-xs font-semibold text-navy-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" /></svg>
                        Member since {{ $memberSince }}
                    </span>
                @endif
                @foreach($profile['providers'] as $p)
                    @continue(!isset($providerMeta[$p]))
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-navy-50 px-3 py-1.5 text-xs font-semibold text-navy-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" aria-hidden="true">{!! $providerMeta[$p]['icon'] !!}</svg>
                        {{ $providerMeta[$p]['label'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- account details --}}
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
            </span>
            <h2 class="text-lg font-bold text-navy-800">Account details</h2>
        </div>
        <p class="mt-1 text-sm text-slate-500">Update your name, contact number, and delivery address.</p>

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
            <div>
                <label for="address" class="mb-1 block text-sm font-medium text-navy-800">Delivery address</label>
                <textarea id="address" name="address" rows="2" maxlength="500" placeholder="House / unit no., street, barangay, city"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">{{ old('address', $profile['address']) }}</textarea>
                <p class="mt-1 text-xs text-slate-400">Saved here so checkout can prefill it — you can still edit it per order.</p>
            </div>
            <button type="submit"
                class="rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40">
                Save changes
            </button>
        </form>
    </section>

    {{-- password --}}
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></svg>
            </span>
            <h2 class="text-lg font-bold text-navy-800">Change password</h2>
        </div>
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
