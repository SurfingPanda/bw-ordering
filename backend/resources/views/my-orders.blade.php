<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders — BW Superbakeshop</title>
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-brand-50 text-navy-800">

@php
    $statusLabel = ['pending' => 'Order Placed', 'preparing' => 'Preparing', 'completed' => 'Delivered', 'cancelled' => 'Cancelled'];
    $statusStyle = ['pending' => 'bg-amber-100 text-amber-700', 'preparing' => 'bg-orange-100 text-orange-700', 'completed' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700'];
    $paymentLabel = ['qrph' => 'QRPH', 'cash' => 'Cash on Pickup', 'paymongo' => 'Online (PayMongo)'];
    $deliverySpeedLabel = ['standard' => 'Standard delivery', 'express' => 'Express delivery'];
    $isActive = fn ($o) => in_array($o->status, ['pending', 'preparing'], true);
    $trackIndex = fn ($status) => ['pending' => 0, 'preparing' => 1, 'completed' => 3][$status] ?? 0;
    $track = [['label' => 'Order Placed', 'icon' => '🧾'], ['label' => 'Preparing', 'icon' => '👨‍🍳'], ['label' => 'Out for Delivery', 'icon' => '🛵'], ['label' => 'Delivered', 'icon' => '📦']];
@endphp

<header class="border-b border-slate-100 bg-white">
    <div class="mx-auto flex max-w-5xl flex-col gap-4 px-4 py-6 sm:px-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="/" class="shrink-0"><img src="{{ $nav['logo'] ?? '/images/logo (1).png' }}" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24"></a>
                <div>
                    <h1 class="text-2xl font-bold text-navy-800">My Orders</h1>
                    <p class="text-sm text-slate-500">Track and reorder your favorite treats.</p>
                </div>
            </div>
            <a href="{{ route('menu') }}" class="text-sm font-medium text-slate-500 transition hover:text-brand-600">← Back to menu</a>
        </div>

        {{-- primary switch: real orders vs. custom cake (Customize) requests --}}
        <div class="flex w-fit gap-1 rounded-full bg-slate-100 p-1" id="view-tabs">
            <button type="button" data-view="orders" class="view-btn rounded-full px-5 py-2 text-sm font-semibold transition">
                🧾 Orders <span class="text-xs opacity-70">{{ $orders->count() }}</span>
            </button>
            <button type="button" data-view="cakes" class="view-btn rounded-full px-5 py-2 text-sm font-semibold transition">
                🎂 Customize <span class="text-xs opacity-70">{{ $cakeRequests->count() }}</span>
            </button>
        </div>

        <div id="orders-controls" class="flex flex-col gap-4">
            <div class="flex flex-wrap gap-2" id="order-tabs">
                <button type="button" data-tab="all" class="tab-btn rounded-full px-4 py-2 text-sm font-semibold transition">All Orders</button>
                <button type="button" data-tab="active" class="tab-btn rounded-full px-4 py-2 text-sm font-semibold transition">Active</button>
                <button type="button" data-tab="completed" class="tab-btn rounded-full px-4 py-2 text-sm font-semibold transition">Completed</button>
                <button type="button" data-tab="cancelled" class="tab-btn rounded-full px-4 py-2 text-sm font-semibold transition">Cancelled</button>
            </div>

            <div class="relative">
                <input type="search" id="order-search" placeholder="Search orders by order number or item"
                    class="w-full rounded-full border border-slate-300 py-2.5 pl-4 pr-4 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            </div>
        </div>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
    {{-- Post-checkout confirmation: CheckoutController redirects here with
         ?placed=<order id>; the page JS also clears the client cart. --}}
    @if(request('placed'))
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm">
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-600">
                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            <div>
                <p class="font-semibold text-green-800">Order placed! 🧡</p>
                <p class="mt-0.5 text-green-700">Thank you — we've received order <span class="font-semibold">#{{ strtoupper(substr((string) request('placed'), 0, 8)) }}</span> and our bakers are on it. Track it below.</p>
            </div>
        </div>
    @endif

    <div id="view-orders">
    @if($orders->isEmpty())
        <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
            <p class="text-sm text-slate-500">You haven't placed any orders here yet.</p>
            <a href="{{ route('menu') }}" class="mt-4 inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30">Browse the menu</a>
        </div>
    @else
        <p id="no-match" class="hidden py-16 text-center text-sm text-slate-500">No orders match your search.</p>

        <div id="order-list" class="space-y-8">
            @foreach($orders as $order)
                @php
                    $items = is_array($order->items) ? $order->items : [];
                    $itemCount = collect($items)->sum('qty');
                    $searchBlob = strtolower($order->id.' '.collect($items)->pluck('name')->implode(' '));
                    $active = $isActive($order);
                @endphp
                <div class="order-card rounded-2xl border border-slate-100 bg-white p-5 shadow-sm"
                     data-status="{{ $order->status }}" data-active="{{ $active ? '1' : '0' }}" data-search="{{ $searchBlob }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="flex items-start gap-4">
                            <div class="flex items-center">
                                @foreach(array_slice($items, 0, 2) as $i)
                                    @php $img = $imgMap[$i['product_id'] ?? ''] ?? ($imgMap[strtolower($i['name'] ?? '')] ?? null); @endphp
                                    <span class="-ml-2 h-12 w-12 overflow-hidden rounded-xl border-2 border-white bg-slate-100 shadow-sm first:ml-0">
                                        @if($img)<img src="{{ $img }}" alt="" class="h-full w-full object-cover">@else<span class="flex h-full w-full items-center justify-center text-lg">🍞</span>@endif
                                    </span>
                                @endforeach
                                @if(count($items) > 2)
                                    <span class="-ml-2 flex h-12 w-12 items-center justify-center rounded-xl border-2 border-white bg-navy-800 text-xs font-bold text-white shadow-sm">+{{ count($items) - 2 }}</span>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-bold text-navy-800">Order #{{ strtoupper(substr($order->id, 0, 8)) }}</h3>
                                <p class="text-xs text-slate-400">{{ $order->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <span class="rounded-full bg-navy-50 px-2 py-0.5 text-[0.65rem] font-semibold text-navy-700">{{ $order->delivery_type === 'pickup' ? '🏪 Pickup' : '🚚 Delivery' }}</span>
                                    @if($order->payment_method)
                                        <span class="rounded-full bg-navy-50 px-2 py-0.5 text-[0.65rem] font-semibold text-navy-700">{{ $paymentLabel[$order->payment_method] ?? $order->payment_method }}</span>
                                    @endif
                                    @if($order->status !== 'cancelled')
                                        <span class="rounded-full px-2 py-0.5 text-[0.65rem] font-semibold {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $order->payment_status === 'paid' ? '✓ Paid' : ($order->payment_method === 'cash' ? 'Pay at pickup' : 'Awaiting payment') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusStyle[$order->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$order->status] ?? $order->status }}</span>
                    </div>

                    @if($active)
                        <div class="my-5 flex items-center">
                            @foreach($track as $idx => $step)
                                @php $done = $idx <= $trackIndex($order->status); @endphp
                                <div class="flex shrink-0 flex-col items-center gap-1">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full text-sm {{ $done ? 'bg-brand-500 text-white shadow-sm shadow-brand-500/40' : 'bg-slate-100 text-slate-300' }}">{{ $step['icon'] }}</span>
                                    <span class="text-[0.6rem] font-medium {{ $done ? 'text-navy-700' : 'text-slate-400' }}">{{ $step['label'] }}</span>
                                </div>
                                @if($idx < count($track) - 1)
                                    <span class="mx-1 mb-4 h-0.5 flex-1 rounded-full {{ $idx < $trackIndex($order->status) ? 'bg-brand-500' : 'bg-slate-200' }}"></span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap items-start justify-between gap-4 border-t border-slate-100 pt-4">
                        <div class="text-sm">
                            <p class="mb-1 font-semibold text-navy-800">{{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}</p>
                            <ul class="space-y-0.5 text-xs text-slate-600">
                                @foreach($items as $i)
                                    <li class="flex justify-between gap-4"><span class="truncate"><span class="font-semibold text-navy-700">{{ $i['qty'] ?? 0 }}×</span> {{ $i['name'] ?? '' }}</span><span class="shrink-0 text-slate-400">₱{{ number_format(($i['price'] ?? 0) * ($i['qty'] ?? 0), 2) }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-xs text-slate-400">Total</p>
                            <p class="text-xl font-bold text-brand-600">₱{{ number_format($order->total, 2) }}</p>
                        </div>
                    </div>

                    <div class="order-details mt-3 hidden border-t border-slate-100 pt-4 text-xs text-slate-600">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1">
                                @if($order->address)<p><span class="font-semibold text-navy-700">📍 Deliver to:</span> {{ $order->address }}</p>@endif
                                @if($order->fulfillment_branch)<p><span class="font-semibold text-navy-700">{{ $order->delivery_type === 'pickup' ? '🏪 Pick up at:' : '🏬 Delivered by:' }}</span> {{ $order->fulfillment_branch }}</p>@endif
                                @if($order->delivery_type !== 'pickup' && $order->delivery_speed)
                                    <p><span class="font-semibold text-navy-700">⏱ Speed:</span> {{ $deliverySpeedLabel[$order->delivery_speed] ?? ucfirst($order->delivery_speed) }}</p>
                                @endif
                                @if($order->notes)<p><span class="font-semibold text-navy-700">📝 Notes:</span> {{ $order->notes }}</p>@endif
                                <p><span class="font-semibold text-navy-700">👤 Ordered by:</span> {{ $order->customer_name }}</p>
                                @if($order->customer_phone)<p><span class="font-semibold text-navy-700">📞 Contact:</span> {{ $order->customer_phone }}</p>@endif
                                @if($order->customer_email)<p><span class="font-semibold text-navy-700">✉️ Email:</span> {{ $order->customer_email }}</p>@endif
                                @if($order->payment_ref)<p><span class="font-semibold text-navy-700">🧾 Payment ref:</span> {{ $order->payment_ref }}</p>@endif
                            </div>
                            {{-- Price breakdown — every figure is the server-computed value
                                 actually stored on the order (OrderCreationService), not
                                 recalculated here, so it can never drift from what was charged. --}}
                            <div class="space-y-1 rounded-xl bg-slate-50 p-3">
                                <div class="flex justify-between"><span>Subtotal</span><span class="font-medium text-navy-800">₱{{ number_format($order->subtotal, 2) }}</span></div>
                                @if($order->voucher)
                                    <div class="flex justify-between text-green-700"><span>Voucher ({{ $order->voucher }})</span><span class="font-medium">−₱{{ number_format($order->discount, 2) }}</span></div>
                                @endif
                                <div class="flex justify-between">
                                    <span>Delivery fee</span>
                                    <span class="font-medium {{ $order->delivery == 0 ? 'text-green-700' : 'text-navy-800' }}">{{ $order->delivery_type === 'pickup' ? '—' : ($order->delivery == 0 ? 'FREE' : '₱'.number_format($order->delivery, 2)) }}</span>
                                </div>
                                <div class="flex justify-between"><span>VAT (12%)</span><span class="font-medium text-navy-800">₱{{ number_format($order->vat, 2) }}</span></div>
                                <div class="flex justify-between border-t border-slate-200 pt-1 text-sm font-bold text-navy-800"><span>Total</span><span>₱{{ number_format($order->total, 2) }}</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" class="toggle-details text-sm font-medium text-brand-600 hover:underline">View Details</button>
                        <div class="ml-auto flex gap-2">
                            <button type="button" class="reorder-btn rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600"
                                data-items="{{ json_encode(array_map(fn ($i) => ['product_id' => $i['product_id'] ?? null, 'qty' => $i['qty'] ?? 1], $items)) }}">
                                Reorder
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Paginated client-side (all orders are already in the DOM for
             instant filter/search — see MyOrdersController) so a customer
             with a long order history isn't handed one giant scroll. --}}
        <div class="mt-6 text-center">
            <button type="button" id="load-more" class="hidden rounded-full border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-navy-700 shadow-sm transition hover:border-brand-400 hover:text-brand-600"></button>
        </div>
    @endif

    </div>

    {{-- Customize tab: custom cake inquiries (quotes, not paid orders).
         Matched in MyOrdersController by user id, falling back to the account
         email for signed-out submissions. --}}
    <div id="view-cakes" class="hidden">
    @if($cakeRequests->isEmpty())
        <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
            <p class="text-3xl">🎂</p>
            <p class="mt-2 text-sm text-slate-500">No custom cake requests yet.</p>
            <a href="{{ route('custom-cake') }}" class="mt-4 inline-block rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30">Customize a cake</a>
        </div>
    @else
        @php
            $ccStatusLabel = ['new' => 'Request received', 'quoted' => 'Quote sent', 'closed' => 'Closed'];
            $ccStatusStyle = ['new' => 'bg-amber-100 text-amber-700', 'quoted' => 'bg-blue-100 text-blue-700', 'closed' => 'bg-slate-100 text-slate-600'];
        @endphp
        <section>
            <p class="text-sm text-slate-500">Inquiries from the <a href="{{ route('custom-cake') }}" class="font-medium text-brand-600 hover:underline">Customize Your Cake</a> page — our team follows up with a quote.</p>
            <div class="mt-4 space-y-4">
                @foreach($cakeRequests as $cc)
                    <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex items-start gap-4">
                                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-2xl">🎂</span>
                                <div>
                                    <h3 class="font-bold text-navy-800">Cake Request #{{ $cc->id }}</h3>
                                    <p class="text-xs text-slate-400">Sent {{ $cc->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        @if($cc->delivery_type)
                                            <span class="rounded-full bg-navy-50 px-2 py-0.5 text-[0.65rem] font-semibold text-navy-700">{{ $cc->delivery_type === 'pickup' ? '🏪 Pickup' : '🚚 Delivery' }}</span>
                                        @endif
                                        @if($cc->fulfillment_branch)
                                            <span class="rounded-full bg-navy-50 px-2 py-0.5 text-[0.65rem] font-semibold text-navy-700">📍 {{ $cc->fulfillment_branch }}</span>
                                        @endif
                                        @foreach(array_filter([$cc->occasion, $cc->flavor, $cc->size, $cc->frosting_color]) as $chip)
                                            <span class="rounded-full bg-navy-50 px-2 py-0.5 text-[0.65rem] font-semibold text-navy-700">{{ $chip }}</span>
                                        @endforeach
                                        @if($cc->needed_by)
                                            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[0.65rem] font-semibold text-brand-600">Needed by {{ $cc->needed_by->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $ccStatusStyle[$cc->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $ccStatusLabel[$cc->status] ?? ucfirst($cc->status) }}</span>
                        </div>
                        <p class="mt-3 border-t border-slate-100 pt-3 text-xs leading-relaxed text-slate-600 line-clamp-2">{{ $cc->description }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    </div>

    <p class="mt-10 text-center text-xs text-slate-400">
        Can't find your order? <a href="mailto:support@bwsuperbakeshop.com" class="font-semibold text-brand-600 hover:underline">Contact Support</a>
    </p>
</main>

<script>
(function () {
    let tab = 'all';
    // 'orders' | 'cakes' — the primary Orders/Customize switch. Deep-linkable
    // via ?view=cakes; landing with ?placed= always shows Orders.
    let view = new URLSearchParams(window.location.search).get('view') === 'cakes' ? 'cakes' : 'orders';

    function paintViews() {
        document.querySelectorAll('.view-btn').forEach(btn => {
            const on = btn.dataset.view === view;
            btn.classList.toggle('bg-gradient-to-r', on);
            btn.classList.toggle('from-brand-500', on);
            btn.classList.toggle('to-brand-600', on);
            btn.classList.toggle('text-white', on);
            btn.classList.toggle('shadow-md', on);
            btn.classList.toggle('text-navy-700', !on);
        });
        document.getElementById('view-orders').classList.toggle('hidden', view !== 'orders');
        document.getElementById('view-cakes').classList.toggle('hidden', view !== 'cakes');
        document.getElementById('orders-controls').classList.toggle('hidden', view !== 'orders');
    }

    document.querySelectorAll('.view-btn').forEach(btn => btn.addEventListener('click', () => { view = btn.dataset.view; paintViews(); }));

    function paintTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            const on = btn.dataset.tab === tab;
            btn.classList.toggle('bg-gradient-to-r', on);
            btn.classList.toggle('from-brand-500', on);
            btn.classList.toggle('to-brand-600', on);
            btn.classList.toggle('text-white', on);
            btn.classList.toggle('shadow-md', on);
            btn.classList.toggle('bg-slate-100', !on);
            btn.classList.toggle('text-navy-700', !on);
        });
    }

    // Client-side pagination: every order card is already in the DOM (see
    // MyOrdersController's comment on why filtering stays client-side), so
    // a customer with dozens of orders was just handed one long page to
    // scroll through. `visibleCount` caps how many *matching* cards are
    // actually shown; "Load more" bumps it. Resets to PAGE_SIZE whenever
    // the tab or search changes so pagination always applies to the
    // current result set, not the unfiltered total.
    const PAGE_SIZE = 5;
    let visibleCount = PAGE_SIZE;

    function applyFilters() {
        const q = (document.getElementById('order-search')?.value || '').trim().toLowerCase();
        const cards = Array.from(document.querySelectorAll('.order-card'));
        const matches = cards.filter(card => {
            const status = card.dataset.status;
            const isActiveOrder = card.dataset.active === '1';
            const byTab = tab === 'all' || (tab === 'active' ? isActiveOrder : status === tab);
            const byText = !q || card.dataset.search.includes(q);
            return byTab && byText;
        });

        cards.forEach(card => card.classList.add('hidden'));
        matches.slice(0, visibleCount).forEach(card => card.classList.remove('hidden'));

        const noMatch = document.getElementById('no-match');
        if (noMatch) noMatch.classList.toggle('hidden', matches.length !== 0);

        const remaining = matches.length - visibleCount;
        const loadMore = document.getElementById('load-more');
        if (loadMore) {
            loadMore.classList.toggle('hidden', remaining <= 0);
            if (remaining > 0) loadMore.textContent = `Load more orders (${remaining} more)`;
        }
    }

    document.getElementById('load-more')?.addEventListener('click', () => { visibleCount += PAGE_SIZE; applyFilters(); });
    document.querySelectorAll('.tab-btn').forEach(btn => btn.addEventListener('click', () => { tab = btn.dataset.tab; visibleCount = PAGE_SIZE; paintTabs(); applyFilters(); }));
    document.getElementById('order-search')?.addEventListener('input', () => { visibleCount = PAGE_SIZE; applyFilters(); });

    document.querySelectorAll('.toggle-details').forEach(btn => btn.addEventListener('click', () => {
        const details = btn.closest('.order-card').querySelector('.order-details');
        details.classList.toggle('hidden');
        btn.textContent = details.classList.contains('hidden') ? 'View Details' : 'Hide details';
    }));

    document.querySelectorAll('.reorder-btn').forEach(btn => btn.addEventListener('click', () => {
        let lines = [];
        try { lines = JSON.parse(btn.dataset.items || '[]'); } catch {}
        const cart = {};
        lines.forEach(l => { if (l.product_id) cart[l.product_id] = (cart[l.product_id] || 0) + (l.qty || 1); });
        try { localStorage.setItem('bw_cart', JSON.stringify(cart)); } catch {}
        window.location.href = '{{ route("menu") }}';
    }));

    // Arriving with ?placed= means the cart was just converted into an order —
    // clear it (and the checkout summary) so the menu badge doesn't keep the
    // old items.
    if (new URLSearchParams(window.location.search).get('placed')) {
        try { localStorage.removeItem('bw_cart'); localStorage.removeItem('bw_checkout'); } catch {}
    }

    paintViews();
    paintTabs();
    applyFilters();
})();
</script>
</body>
</html>
