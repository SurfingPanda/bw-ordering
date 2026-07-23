{{-- The Site Editor sidebar nav, shared by the content editor and the pages it
     links out to (Stores/Vouchers CRUD) so they all live in the same shell.

     Most items are ?section= links into the content editor (its JS intercepts
     them for instant client-side switching); Products, Vouchers, and Find a
     Store go straight to their own editor pages, like the old in-editor
     sections. Vars: $activeSection (server-rendered highlight; the content
     page passes null and lets its tab JS drive it), $navCounts. --}}
@php
    $navGroups = [
        ['label' => 'Landing Page', 'items' => [
            ['key' => 'announcement', 'label' => 'Announcement', 'icon' => 'megaphone'],
            ['key' => 'banners', 'label' => 'Promo Banners', 'icon' => 'image', 'count' => $navCounts['banners'] ?? null],
            ['key' => 'whatsNew', 'label' => "What's New", 'icon' => 'sparkle'],
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
            ['key' => 'storesPage', 'label' => 'Find a Store Page', 'icon' => 'pin'],
            ['key' => 'franchise', 'label' => 'Franchise', 'icon' => 'briefcase'],
            ['key' => 'footer', 'label' => 'Footer', 'icon' => 'layout'],
            ['key' => 'buttons', 'label' => 'Buttons', 'icon' => 'toggle'],
        ]],
    ];

    // Management pages (the old /admin panel, folded into this shell). Items
    // follow the account's access: role defaults + per-user grants from
    // Users & Roles (see Controller::editorNavAccess). Pages that don't pass
    // $navAccess fall back to showing everything to admins.
    $navAccess = $navAccess ?? (! empty($isAdminUser) ? ['users' => true, 'orders' => true, 'customCakes' => true, 'contactMessages' => true] : []);
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
    $activeSection = $activeSection ?? null;
@endphp

@foreach($navGroups as $group)
    @php($groupActive = in_array($activeSection, array_column($group['items'], 'key'), true))
    <div data-nav-group="{{ $group['label'] }}">
        <button type="button" data-group-toggle
            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wider transition {{ $groupActive ? 'text-brand-400' : 'text-navy-50/50' }} hover:text-navy-50/90">
            <x-admin-icon name="chevron-right" data-chevron class="h-4 w-4 shrink-0 rotate-90 transition-transform" />
            <span>{{ $group['label'] }}</span>
        </button>
        <div data-group-items class="mb-1 mt-0.5 space-y-0.5 pl-2">
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

<script>
    // Collapsible sidebar groups — lives with the nav so every page that
    // includes it gets working group toggles. Collapsed groups are remembered
    // in localStorage so navigating between editor pages keeps them minimized.
    (() => {
        const KEY = 'bw_editor_nav_collapsed'
        const readCollapsed = () => {
            try { return JSON.parse(localStorage.getItem(KEY) || '[]') } catch { return [] }
        }
        const paint = (group, open) => {
            group.querySelector('[data-group-items]').classList.toggle('hidden', !open)
            group.querySelector('[data-chevron]').classList.toggle('rotate-90', open)
        }
        document.querySelectorAll('[data-nav-group]').forEach((group) => {
            const name = group.dataset.navGroup
            if (readCollapsed().includes(name)) paint(group, false)
            group.querySelector('[data-group-toggle]').addEventListener('click', () => {
                const open = group.querySelector('[data-group-items]').classList.contains('hidden')
                paint(group, open)
                const collapsed = readCollapsed().filter((n) => n !== name)
                if (!open) collapsed.push(name)
                try { localStorage.setItem(KEY, JSON.stringify(collapsed)) } catch {}
            })
        })
    })()
</script>
