{{-- The Site Editor shell — ported from the old AdminContent.jsx page chrome.
     Nav lives in a sticky top bar (desktop group-dropdowns + a mobile hamburger
     panel, both rendered by admin/content/_editor-nav.blade.php), not a side
     column, plus a sticky white sub-header with the active section title +
     Save, and a live-preview pane on the right (xl+). Distinct from
     layouts/admin (the orders/products dashboard shell), exactly as the two
     shells were distinct pages in the SPA. --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Site Editor') — BW Superbakeshop</title>
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body>
    @php($user = session('supabase_user'))

    <div class="flex min-h-screen flex-col bg-navy-50/40 text-navy-800">
        {{-- top bar — logo, nav (desktop dropdowns / mobile hamburger panel), user menu --}}
        <header class="sticky top-0 z-30 bg-navy-900 text-white shadow-lg">
            <div class="relative flex h-16 items-center gap-3 px-4 sm:px-6">
                <a href="/" class="flex shrink-0 items-center gap-2.5">
                    <img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-10 w-auto">
                    <span class="hidden text-sm font-semibold uppercase tracking-[0.2em] text-brand-400 sm:block">Site Editor</span>
                </a>

                @yield('editor-nav')

                <div class="ml-auto flex shrink-0 items-center gap-1">
                    <div class="relative" data-user-menu>
                        <button type="button" data-user-menu-toggle
                            class="flex items-center gap-2 rounded-lg p-1.5 pr-2 transition hover:bg-white/10">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold">
                                {{ strtoupper(substr($user['email'] ?? 'E', 0, 1)) }}
                            </span>
                            <span class="hidden max-w-[9rem] truncate text-sm font-semibold sm:block">{{ $user['name'] ?? 'Editor' }}</span>
                            <x-admin-icon name="chevron-right" data-user-menu-chevron class="hidden h-3.5 w-3.5 shrink-0 rotate-90 text-navy-50/60 transition-transform sm:block" />
                        </button>
                        <div data-user-menu-panel class="invisible absolute right-0 top-full z-40 mt-1 w-60 -translate-y-1 rounded-xl border border-slate-200 bg-white p-1.5 text-navy-800 opacity-0 shadow-xl transition-all duration-150">
                            <div class="border-b border-slate-100 px-3 py-2">
                                <span class="flex items-center gap-1.5">
                                    <span class="block truncate text-sm font-semibold">{{ $user['name'] ?? 'Editor' }}</span>
                                    @include('partials.role-badge', ['email' => $user['email'] ?? ''])
                                </span>
                                <span class="block truncate text-xs text-slate-500">{{ $user['email'] ?? '' }}</span>
                            </div>
                            <a href="/" class="mt-1 flex items-center rounded-lg px-3 py-2 text-sm font-medium text-navy-700 transition hover:bg-navy-50">
                                View site
                            </a>
                            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
                            <button type="button" onclick="showLogoutConfirm()" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                Logout
                            </button>
                        </div>
                    </div>

                    <button type="button" id="mobile-nav-toggle" aria-label="Open menu" aria-expanded="false"
                        class="rounded-lg p-2 text-navy-50/80 transition hover:bg-white/10 hover:text-white lg:hidden">
                        <x-admin-icon name="menu" class="h-6 w-6" />
                    </button>
                </div>
            </div>
        </header>

        {{-- Log-out confirmation (ConfirmModal port, same pattern as the landing/menu pages) --}}
        <div id="logout-confirm-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" onclick="hideLogoutConfirm(event)" role="dialog" aria-modal="true" aria-label="Log out?">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
                <h3 class="text-lg font-bold text-navy-800">Log out?</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">You'll be signed out of the Site Editor.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" id="logout-cancel-btn" onclick="hideLogoutConfirm()" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Cancel</button>
                    <button type="button" id="logout-confirm-btn" onclick="confirmLogout()" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600 disabled:cursor-not-allowed disabled:opacity-70">
                        <svg id="logout-confirm-spinner" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span id="logout-confirm-label">Log out</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-16 z-20 border-b border-slate-200 bg-white">
                <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-2">
                        <h1 id="editor-title" class="truncate text-lg font-bold text-navy-800">@yield('title', 'Site Editor')</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        @if(session('status'))
                            <span class="hidden text-sm font-medium text-green-600 sm:inline">✓ {{ session('status') }}</span>
                        @endif
                        @yield('header-actions')
                    </div>
                </div>
            </header>

            <div class="flex min-w-0 flex-1">
                <main class="min-w-0 flex-1 px-4 py-6 sm:px-6">
                    @yield('content')
                </main>

                {{-- live preview — the real public page in an iframe. Unlike the
                     SPA (which rendered unsaved state), this shows saved content,
                     so it refreshes on every save's redirect. Pages without a
                     preview section (Stores/Vouchers CRUD) get the full width. --}}
                @hasSection('preview')
                    <aside class="hidden shrink-0 border-l border-slate-200 bg-slate-100/70 xl:block xl:w-[55%]">
                        <div class="sticky top-32 flex h-[calc(100vh-8rem)] flex-col p-5">
                            <p class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                @yield('preview-label', 'Live preview — full page')
                            </p>
                            @yield('preview')
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>

    <script>
        // User menu dropdown in the top bar (avatar → View site / Logout).
        ;(() => {
            const wrap = document.querySelector('[data-user-menu]')
            if (!wrap) return
            const panel = wrap.querySelector('[data-user-menu-panel]')
            const chevron = wrap.querySelector('[data-user-menu-chevron]')
            function setOpen(open) {
                panel.classList.toggle('invisible', !open)
                panel.classList.toggle('opacity-0', !open)
                panel.classList.toggle('-translate-y-1', !open)
                if (chevron) chevron.classList.toggle('-rotate-90', open)
            }
            wrap.querySelector('[data-user-menu-toggle]').addEventListener('click', (e) => {
                e.stopPropagation()
                setOpen(panel.classList.contains('invisible'))
            })
            wrap.addEventListener('click', (e) => e.stopPropagation())
            document.addEventListener('click', () => setOpen(false))
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false) })
        })()

        window.showLogoutConfirm = function () {
            const el = document.getElementById('logout-confirm-modal')
            if (el) { el.classList.remove('hidden'); el.classList.add('flex') }
        }
        window.hideLogoutConfirm = function (e) {
            if (e && e.target !== e.currentTarget) return
            const el = document.getElementById('logout-confirm-modal')
            if (el) { el.classList.add('hidden'); el.classList.remove('flex') }
        }
        window.confirmLogout = function () {
            const btn = document.getElementById('logout-confirm-btn')
            if (btn && btn.disabled) return
            if (btn) btn.disabled = true
            const cancel = document.getElementById('logout-cancel-btn')
            if (cancel) cancel.disabled = true
            const spinner = document.getElementById('logout-confirm-spinner')
            if (spinner) spinner.classList.remove('hidden')
            const label = document.getElementById('logout-confirm-label')
            if (label) label.textContent = 'Logging out…'
            document.getElementById('logout-form').submit()
        }
    </script>
    @yield('scripts')
</body>
</html>
