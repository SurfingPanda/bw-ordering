{{-- The Site Editor's top nav bar (formerly a static left sidebar — the nav
     itself, its data, and its section-switching JS are unchanged; only where
     it lives moved), shared by the content editor and the pages it links out
     to (Stores/Vouchers CRUD) so they all live in the same shell.

     Renders two variants from the same $navGroups data:
       - Desktop (hidden lg:flex): one dropdown button per group, opening a
         panel of that group's items. Only one open at a time.
       - Mobile (lg:hidden): a hamburger-toggled panel holding all groups as
         a collapsible accordion (same interaction the old sidebar had).

     Most items are ?section= links into the content editor (its JS intercepts
     them for instant client-side switching); Products, Vouchers, and Find a
     Store go straight to their own editor pages, like the old in-editor
     sections. Vars: $activeSection (server-rendered highlight; the content
     page passes null and lets its tab JS drive it), $navCounts. --}}
@php
    $navGroups = [
        ['label' => 'Landing Page', 'items' => [
            ['key' => 'nav', 'label' => 'Navigation Bar', 'icon' => 'toggle'],
            ['key' => 'announcement', 'label' => 'Announcement', 'icon' => 'megaphone'],
            ['key' => 'banners', 'label' => 'Promo Banners', 'icon' => 'image', 'count' => $navCounts['banners'] ?? null],
            ['key' => 'whatsNew', 'label' => "What's New", 'icon' => 'sparkle'],
            ['key' => 'bestSellersSection', 'label' => 'Best Sellers', 'icon' => 'star'],
            ['key' => 'categoriesSection', 'label' => 'Categories Heading', 'icon' => 'grid'],
            ['key' => 'menuCategories', 'label' => 'Menu Categories', 'icon' => 'grid'],
            ['key' => 'customCake', 'label' => 'Custom Cake', 'icon' => 'cake'],
            ['key' => 'customCakeForm', 'label' => 'Custom Cake Page', 'icon' => 'cake'],
            ['key' => 'storeLocator', 'label' => 'Store Locator', 'icon' => 'pin'],
            ['key' => 'newsletter', 'label' => 'Sweet Deals', 'icon' => 'mail'],
        ]],
        ['label' => 'Shop & Menu', 'items' => [
            ['key' => 'products', 'label' => 'Products', 'icon' => 'tag', 'count' => $navCounts['products'] ?? null, 'href' => route('admin.products.index')],
            ['key' => 'menuPromo', 'label' => 'Menu Promo', 'icon' => 'megaphone'],
            ['key' => 'vouchers', 'label' => 'Vouchers', 'icon' => 'ticket', 'count' => $navCounts['vouchers'] ?? null, 'href' => route('admin.vouchers.index')],
            ['key' => 'payment', 'label' => 'Payment QR', 'icon' => 'qr'],
        ]],
        ['label' => 'Other Pages', 'items' => [
            ['key' => 'authPanel', 'label' => 'Login Page', 'icon' => 'login'],
            ['key' => 'social', 'label' => 'Social Links', 'icon' => 'share'],
            ['key' => 'stores', 'label' => 'Find a Store', 'icon' => 'pin', 'count' => $navCounts['stores'] ?? null, 'href' => route('admin.stores.index')],
            ['key' => 'franchise', 'label' => 'Franchise', 'icon' => 'briefcase'],
            ['key' => 'about', 'label' => 'About Page', 'icon' => 'layout'],
            ['key' => 'footer', 'label' => 'Footer', 'icon' => 'layout'],
            ['key' => 'legal', 'label' => 'Legal Pages', 'icon' => 'layout'],
            ['key' => 'buttons', 'label' => 'Buttons', 'icon' => 'toggle'],
        ]],
    ];

    // Management pages (the old /admin panel, folded into this shell). Items
    // follow the account's access: role defaults + per-user grants from
    // Users & Roles (see Controller::editorNavAccess). Pages that don't pass
    // $navAccess fall back to showing everything to admins.
    $navAccess = $navAccess ?? (! empty($isAdminUser) ? ['users' => true, 'orders' => true, 'customCakes' => true, 'contactMessages' => true, 'audit' => true] : []);
    $adminItems = [];
    if (! empty($navAccess['users'])) {
        $adminItems[] = ['key' => 'users', 'label' => 'Users & Roles', 'icon' => 'users', 'href' => route('admin.users')];
    }
    if (! empty($navAccess['orders'])) {
        $adminItems[] = ['key' => 'orders', 'label' => 'Orders', 'icon' => 'list', 'href' => route('admin.orders')];
    }
    if (! empty($navAccess['customCakes'])) {
        $adminItems[] = ['key' => 'customCakes', 'label' => 'Custom Cakes', 'icon' => 'cake', 'href' => route('admin.custom-cakes')];
    }
    if (! empty($navAccess['contactMessages'])) {
        $adminItems[] = ['key' => 'contactMessages', 'label' => 'Contact Messages', 'icon' => 'mail', 'count' => $navCounts['contactMessages'] ?? null, 'href' => route('admin.contact-messages')];
    }
    if ($adminItems) {
        $navGroups[] = ['label' => 'Management', 'items' => $adminItems];
    }

    // Audit Log — its own group, sat right beside Management. Editors + admins
    // (falls back to $isAdminUser so it still shows on the pages whose
    // controllers pass a hand-rolled $navAccess without the 'audit' key).
    if (! empty($navAccess['audit']) || ! empty($isAdminUser)) {
        $navGroups[] = ['label' => 'Audit Log', 'standalone' => true, 'items' => [
            ['key' => 'audit', 'label' => 'Audit Log', 'icon' => 'clock', 'href' => route('admin.audit-log')],
        ]];
    }
    $activeSection = $activeSection ?? null;
@endphp

{{-- ============ Desktop: one dropdown per group ============ --}}
<nav class="hidden min-w-0 flex-1 items-center gap-1 lg:flex" aria-label="Site Editor sections">
    @foreach($navGroups as $group)
        @php($groupActive = in_array($activeSection, array_column($group['items'], 'key'), true))
        {{-- A "standalone" group (Audit Log) renders as a plain link, no
             dropdown — it's a single destination, nothing to choose between. --}}
        @if(! empty($group['standalone']) && isset($group['items'][0]['href']))
            @php($only = $group['items'][0])
            <a href="{{ $only['href'] }}"
                class="flex items-center gap-1.5 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition {{ $groupActive ? 'bg-white/10 text-white' : 'text-navy-50/70 hover:bg-white/5 hover:text-white' }}">
                <x-admin-icon :name="$only['icon']" class="h-4 w-4 shrink-0" />
                <span>{{ $group['label'] }}</span>
            </a>
            @continue
        @endif
        <div class="relative" data-dropdown>
            <button type="button" data-dropdown-toggle
                class="flex items-center gap-1.5 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition {{ $groupActive ? 'bg-white/10 text-white' : 'text-navy-50/70 hover:bg-white/5 hover:text-white' }}">
                <span>{{ $group['label'] }}</span>
                <x-admin-icon name="chevron-right" data-dropdown-chevron class="h-3.5 w-3.5 shrink-0 rotate-90 transition-transform" />
            </button>
            <div data-dropdown-panel class="invisible absolute left-0 top-full z-40 mt-1 w-64 -translate-y-1 rounded-xl border border-slate-200 bg-white p-1.5 opacity-0 shadow-xl transition-all duration-150">
                @foreach($group['items'] as $item)
                    @php($on = $activeSection === $item['key'])
                    <a href="{{ $item['href'] ?? route('admin.content', ['section' => $item['key']]) }}"
                        @unless(isset($item['href'])) data-tab="{{ $item['key'] }}" @endunless
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-navy-700 transition hover:bg-navy-50 {{ $on ? 'bg-gradient-to-r from-brand-500 to-brand-600 !text-white shadow shadow-brand-500/30' : '' }}">
                        <x-admin-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        @if(($item['count'] ?? null) !== null)
                            <span data-tab-badge class="ml-auto shrink-0 rounded-full px-2 py-0.5 text-xs font-bold {{ $on ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500' }}">{{ $item['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</nav>

{{-- ============ Mobile: hamburger-toggled accordion panel ============ --}}
<div id="mobile-nav-panel" class="invisible absolute inset-x-0 top-full z-40 max-h-[calc(100vh-4rem)] -translate-y-1 overflow-y-auto border-t border-white/10 bg-navy-900 p-3 opacity-0 transition-all duration-150 lg:hidden">
    @foreach($navGroups as $group)
        @php($groupActive = in_array($activeSection, array_column($group['items'], 'key'), true))
        {{-- Standalone group (Audit Log): a single link, no accordion. --}}
        @if(! empty($group['standalone']) && isset($group['items'][0]['href']))
            @php($only = $group['items'][0])
            <a href="{{ $only['href'] }}"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $groupActive ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/30' : 'text-navy-50/70 hover:bg-white/5 hover:text-white' }}">
                <x-admin-icon :name="$only['icon']" class="h-5 w-5 shrink-0" />
                <span>{{ $group['label'] }}</span>
            </a>
            @continue
        @endif
        <div data-nav-group="{{ $group['label'] }}">
            <button type="button" data-accordion-toggle
                class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wider transition {{ $groupActive ? 'text-brand-400' : 'text-navy-50/50' }} hover:text-navy-50/90">
                <x-admin-icon name="chevron-right" data-accordion-chevron class="h-4 w-4 shrink-0 rotate-90 transition-transform" />
                <span>{{ $group['label'] }}</span>
            </button>
            <div data-accordion-items class="mb-1 mt-0.5 space-y-0.5 pl-2">
                @foreach($group['items'] as $item)
                    @php($on = $activeSection === $item['key'])
                    <a href="{{ $item['href'] ?? route('admin.content', ['section' => $item['key']]) }}"
                        @unless(isset($item['href'])) data-tab="{{ $item['key'] }}" @endunless
                        class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $on ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-lg shadow-brand-500/30' : 'text-navy-50/70 hover:bg-white/5 hover:text-white' }}">
                        <x-admin-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                        <span>{{ $item['label'] }}</span>
                        @if(($item['count'] ?? null) !== null)
                            <span data-tab-badge class="ml-auto rounded-full px-2 py-0.5 text-xs font-bold {{ $on ? 'bg-white/25 text-white' : 'bg-white/10 text-navy-50/70' }}">{{ $item['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
    // Desktop group dropdowns — one open at a time, closes on outside
    // click/Escape/picking a link. Each [data-dropdown] is independent, but
    // opening one closes any other that's open.
    (() => {
        const dropdowns = Array.from(document.querySelectorAll('[data-dropdown]'))
        function paint(d, open) {
            const panel = d.querySelector('[data-dropdown-panel]')
            const chevron = d.querySelector('[data-dropdown-chevron]')
            panel.classList.toggle('invisible', !open)
            panel.classList.toggle('opacity-0', !open)
            panel.classList.toggle('-translate-y-1', !open)
            chevron.classList.toggle('rotate-90', !open)
            chevron.classList.toggle('-rotate-90', open)
        }
        function closeAll(except) {
            dropdowns.forEach((d) => { if (d !== except) paint(d, false) })
        }
        dropdowns.forEach((d) => {
            const toggle = d.querySelector('[data-dropdown-toggle]')
            toggle.addEventListener('click', (e) => {
                e.stopPropagation()
                const willOpen = d.querySelector('[data-dropdown-panel]').classList.contains('invisible')
                closeAll(d)
                paint(d, willOpen)
            })
            // Picking a link closes the dropdown — the tab-click handler
            // elsewhere still does the actual section switch.
            d.querySelectorAll('[data-dropdown-panel] a').forEach((a) => {
                a.addEventListener('click', () => paint(d, false))
            })
        })
        document.addEventListener('click', () => closeAll(null))
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(null) })
    })()

    // Mobile hamburger panel + its internal accordion. Collapsed groups are
    // remembered in localStorage so navigating between editor pages keeps
    // them minimized (same behavior the old sidebar had). Deferred to
    // DOMContentLoaded — #mobile-nav-toggle lives in the parent layout,
    // rendered further down the page than this partial, so it doesn't exist
    // yet when this inline script would otherwise run synchronously.
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('mobile-nav-toggle')
        const panel = document.getElementById('mobile-nav-panel')
        if (!toggleBtn || !panel) return

        window.closeMobileNav = () => {
            panel.classList.add('invisible', 'opacity-0', '-translate-y-1')
            toggleBtn.setAttribute('aria-expanded', 'false')
        }
        window.toggleMobileNav = () => {
            const willOpen = panel.classList.contains('invisible')
            panel.classList.toggle('invisible', !willOpen)
            panel.classList.toggle('opacity-0', !willOpen)
            panel.classList.toggle('-translate-y-1', !willOpen)
            toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false')
        }
        toggleBtn.addEventListener('click', (e) => { e.stopPropagation(); window.toggleMobileNav() })
        panel.addEventListener('click', (e) => e.stopPropagation())
        document.addEventListener('click', () => window.closeMobileNav())
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeMobileNav() })
        panel.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => window.closeMobileNav()))

        const KEY = 'bw_editor_nav_collapsed'
        const readCollapsed = () => {
            try { return JSON.parse(localStorage.getItem(KEY) || '[]') } catch { return [] }
        }
        const paintAccordion = (group, open) => {
            group.querySelector('[data-accordion-items]').classList.toggle('hidden', !open)
            group.querySelector('[data-accordion-chevron]').classList.toggle('rotate-90', open)
        }
        panel.querySelectorAll('[data-nav-group]').forEach((group) => {
            const name = group.dataset.navGroup
            if (readCollapsed().includes(name)) paintAccordion(group, false)
            group.querySelector('[data-accordion-toggle]').addEventListener('click', () => {
                const open = group.querySelector('[data-accordion-items]').classList.contains('hidden')
                paintAccordion(group, open)
                const collapsed = readCollapsed().filter((n) => n !== name)
                if (!open) collapsed.push(name)
                try { localStorage.setItem(KEY, JSON.stringify(collapsed)) } catch {}
            })
        })
    })
</script>
