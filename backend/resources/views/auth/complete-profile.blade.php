<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>One last step — BW Superbakeshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Pacifico&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
    <div class="flex min-h-screen items-center justify-center bg-navy-900 p-4 sm:p-6">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl lg:grid-cols-[1fr_1.1fr]">
            {{-- brand panel --}}
            @include('auth._brand-panel')

            {{-- form panel --}}
            <div class="flex flex-col justify-center px-7 py-10 sm:px-12">
                <div class="mx-auto w-full max-w-sm">
                    <div class="mb-6 flex flex-col items-center text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-navy-700 to-navy-900 text-3xl shadow-lg ring-4 ring-brand-500/20">
                            📞
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-navy-800">One last step</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Hi {{ $user['name'] ?? 'there' }}! Please add your contact number <br class="hidden sm:block">
                            so we can reach you about your orders.
                        </p>
                    </div>

                    @error('contact_number')
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    <form method="POST" action="{{ route('complete-profile.store') }}" novalidate class="space-y-4">
                        @csrf

                        <div>
                            <label class="mb-1 block text-sm font-medium text-navy-800">Contact number</label>
                            <input type="tel" name="contact_number" required inputmode="tel" autocomplete="tel" maxlength="20"
                                value="{{ old('contact_number') }}" placeholder="e.g. 0917 123 4567" data-phone-input
                                class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-brand-500 to-brand-600 py-3 text-sm font-semibold uppercase tracking-wide text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 focus:ring-2 focus:ring-brand-500/40">
                            Continue
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        Don't want to add a number?
                        <button type="button" onclick="showLogoutConfirm()" class="font-semibold text-brand-600 underline-offset-2 transition hover:underline">
                            Log out
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Log-out confirmation (ConfirmModal port, same pattern as the landing page) --}}
    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
    <div id="logout-confirm-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" onclick="hideLogoutConfirm(event)" role="dialog" aria-modal="true" aria-label="Log out?">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-navy-800">Log out?</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">You'll be signed out without adding a contact number.</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="hideLogoutConfirm()" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">
                    Cancel
                </button>
                <button type="button" onclick="document.getElementById('logout-form').submit()" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                    Log out
                </button>
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
        });
        window.showLogoutConfirm = function () {
            var el = document.getElementById('logout-confirm-modal');
            if (el) { el.classList.remove('hidden'); el.classList.add('flex'); }
        };
        window.hideLogoutConfirm = function (e) {
            if (e && e.target !== e.currentTarget) return;
            var el = document.getElementById('logout-confirm-modal');
            if (el) { el.classList.add('hidden'); el.classList.remove('flex'); }
        };
    </script>
</body>
</html>
