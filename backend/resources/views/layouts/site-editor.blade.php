{{-- The Site Editor shell — ported from the old AdminContent.jsx page chrome:
     navy sidebar with grouped/collapsible sections, sticky white header with the
     active section title + Save, and a live-preview pane on the right (xl+).
     Distinct from layouts/admin (the orders/products dashboard shell), exactly
     as the two shells were distinct pages in the SPA. --}}
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

    <div class="flex min-h-screen flex-col bg-navy-50/40 text-navy-800 lg:flex-row">
        {{-- backdrop behind the mobile drawer --}}
        <div id="sidebar-backdrop" aria-hidden="true" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>

        {{-- sidebar — off-canvas drawer on mobile, static on lg+ --}}
        <aside id="editor-sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85%] shrink-0 -translate-x-full transform flex-col bg-navy-900 text-white shadow-2xl transition-transform duration-300 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:w-64 lg:max-w-none lg:translate-x-0 lg:shadow-none">
            <div class="relative flex h-24 items-center justify-center border-b border-white/10 px-5">
                <img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-20 w-auto">
                <button type="button" id="sidebar-close" aria-label="Close menu"
                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-navy-50/70 transition hover:bg-white/10 hover:text-white lg:hidden">
                    <x-admin-icon name="close" class="h-5 w-5" />
                </button>
            </div>
            <div class="border-b border-white/10 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-400">Site Editor</p>
                <p class="mt-1 text-xs text-navy-50/60">Manage your landing page</p>
            </div>

            <nav class="flex flex-1 flex-col gap-1 overflow-y-auto p-3">
                @yield('editor-nav')
            </nav>

            <div class="border-t border-white/10 px-5 py-4">
                <div class="flex items-center gap-3 p-1">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500 text-sm font-bold">
                        {{ strtoupper(substr($user['email'] ?? 'E', 0, 1)) }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-1.5">
                            <span class="block truncate text-sm font-semibold">{{ $user['name'] ?? 'Editor' }}</span>
                            @include('partials.role-badge', ['email' => $user['email'] ?? ''])
                        </span>
                        <span class="block truncate text-xs text-navy-50/60">{{ $user['email'] ?? '' }}</span>
                    </span>
                </div>
                <div class="mt-3 flex gap-2">
                    <a href="/" class="flex-1 rounded-lg bg-white/10 py-2 text-center text-xs font-semibold transition hover:bg-white/20">
                        View site
                    </a>
                    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
                    <button type="button" onclick="showLogoutConfirm()" class="flex-1 rounded-lg bg-white/10 py-2 text-center text-xs font-semibold transition hover:bg-brand-600">
                        Logout
                    </button>
                </div>
            </div>
        </aside>

        {{-- Log-out confirmation (ConfirmModal port, same pattern as the landing/menu pages) --}}
        <div id="logout-confirm-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" onclick="hideLogoutConfirm(event)" role="dialog" aria-modal="true" aria-label="Log out?">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" onclick="event.stopPropagation()">
                <h3 class="text-lg font-bold text-navy-800">Log out?</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">You'll be signed out of the Site Editor.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="hideLogoutConfirm()" class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">Cancel</button>
                    <button type="button" onclick="document.getElementById('logout-form').submit()" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">Log out</button>
                </div>
            </div>
        </div>

        {{-- main --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
                <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-2">
                        <button type="button" id="sidebar-open" aria-label="Open menu"
                            class="-ml-1 shrink-0 rounded-lg p-2 text-navy-700 transition hover:bg-navy-50 lg:hidden">
                            <x-admin-icon name="menu" class="h-6 w-6" />
                        </button>
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
                    <aside class="hidden shrink-0 border-l border-slate-200 bg-slate-100/70 xl:block xl:w-[42%]">
                        <div class="sticky top-16 flex h-[calc(100vh-4rem)] flex-col p-5">
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
        // Mobile drawer open/close (same off-canvas behavior as the SPA shell).
        const editorSidebar = document.getElementById('editor-sidebar')
        const sidebarBackdrop = document.getElementById('sidebar-backdrop')
        function setSidebarOpen(open) {
            editorSidebar.classList.toggle('-translate-x-full', !open)
            editorSidebar.classList.toggle('translate-x-0', open)
            sidebarBackdrop.classList.toggle('hidden', !open)
        }
        document.getElementById('sidebar-open').addEventListener('click', () => setSidebarOpen(true))
        document.getElementById('sidebar-close').addEventListener('click', () => setSidebarOpen(false))
        sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false))

        window.showLogoutConfirm = function () {
            const el = document.getElementById('logout-confirm-modal')
            if (el) { el.classList.remove('hidden'); el.classList.add('flex') }
        }
        window.hideLogoutConfirm = function (e) {
            if (e && e.target !== e.currentTarget) return
            const el = document.getElementById('logout-confirm-modal')
            if (el) { el.classList.add('hidden'); el.classList.remove('flex') }
        }
    </script>
    @yield('scripts')
</body>
</html>
