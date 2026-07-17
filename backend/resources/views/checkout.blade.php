<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout — BW Superbakeshop</title>
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-navy-50/40 text-navy-800">

@if($step === 'done')
    {{-- Confirmation — the order already exists (either just placed, or a
         PayMongo payment we just reconciled via OrderCreationService::reconcile()). --}}
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center px-4 sm:px-6">
            <a href="/"><img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-9 w-auto"></a>
        </div>
    </header>
    <main class="mx-auto max-w-xl px-4 py-12 sm:px-6">
        <div class="rounded-3xl border border-slate-100 bg-white p-8 text-center shadow-sm sm:p-10">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
            </div>
            <h1 class="mt-5 text-2xl font-bold text-navy-800">Order placed!</h1>
            <p class="mt-2 text-sm text-slate-500">Thank you — we've received your order and our bakers are on it. 🧡</p>

            <dl class="mx-auto mt-6 max-w-xs space-y-2 rounded-2xl bg-navy-50/60 p-5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Order #</dt><dd class="font-semibold text-navy-800">{{ strtoupper(substr($order->id, 0, 8)) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="font-semibold capitalize text-amber-600">{{ $order->status }}</dd></div>
                @if($order->fulfillment_branch)
                    <div class="flex justify-between gap-4"><dt class="shrink-0 text-slate-500">{{ $order->delivery_type === 'pickup' ? 'Pickup' : 'Branch' }}</dt><dd class="text-right font-semibold text-navy-800">{{ $order->fulfillment_branch }}</dd></div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-slate-500">Payment</dt>
                    <dd class="font-semibold text-navy-800">
                        {{ ['qrph' => 'QRPH', 'cash' => 'Cash on Pickup', 'paymongo' => 'Online (PayMongo)'][$order->payment_method] ?? 'Cash' }}
                        <span class="{{ $order->payment_status === 'paid' ? 'text-green-600' : 'text-amber-600' }}"> · {{ $order->payment_status === 'paid' ? 'Paid' : 'Pay on pickup' }}</span>
                    </dd>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt class="font-bold text-navy-800">Total</dt><dd class="font-bold text-brand-600">₱{{ number_format($order->total, 2) }}</dd></div>
            </dl>

            <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('my-orders') }}" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">View my orders</a>
                <a href="{{ route('menu') }}" class="rounded-full border border-slate-300 px-7 py-3 text-sm font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">Order more</a>
            </div>
        </div>
    </main>
@else
    <script id="stores-data" type="application/json">{!! $stores->toJson() !!}</script>
    <script id="vouchers-data" type="application/json">{!! $vouchers->toJson() !!}</script>

    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <a href="/" class="shrink-0"><img src="/images/logo (1).png" alt="bw Superbakeshop" class="h-9 w-auto"></a>
                <ol class="hidden items-center gap-1 text-xs sm:flex">
                    <li id="step-pill-1" class="rounded-full bg-brand-500 px-3 py-1 font-bold text-white">1. Delivery &amp; Details</li>
                    <li id="step-pill-2" class="rounded-full bg-slate-200 px-3 py-1 font-bold text-slate-500">2. Payment</li>
                </ol>
            </div>
            <a href="{{ route('menu') }}" class="flex shrink-0 items-center gap-2 rounded-full bg-navy-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-600">
                🛒 Cart (<span class="cart-item-count">0</span>)
            </a>
        </div>
    </header>

    <div id="empty-cart" class="hidden flex-col items-center justify-center gap-4 px-4 py-24 text-center">
        <p class="text-lg font-semibold text-navy-800">Your cart is empty.</p>
        <p class="text-sm text-slate-500">Add some treats before checking out.</p>
        <a href="{{ route('menu') }}" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30">Browse the menu</a>
    </div>

    <form id="checkout-form" method="POST" action="{{ route('checkout.store') }}" class="hidden">
        @csrf
        <input type="hidden" name="items_json" id="items_json">

        <main class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_22rem]">
            <div class="space-y-6">
                <div id="price-changes-banner" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <p class="font-semibold">Prices updated</p>
                    <ul id="price-changes-list" class="mt-1 list-disc pl-5 text-amber-700"></ul>
                </div>
                <div id="unavailable-banner" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold">Some items are no longer available</p>
                    <ul id="unavailable-list" class="mt-1 list-disc pl-5"></ul>
                    <a href="{{ route('menu') }}" class="mt-1 inline-block text-xs font-semibold underline">Update your cart</a>
                </div>

                @if($errors->any())
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($error)
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
                @endif

                {{-- ============ STEP 1: Delivery / Details / Notes ============ --}}
                <div id="step-form" class="space-y-6">
                    <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-navy-800">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs text-white">1</span>
                            Delivery or Pickup
                        </h2>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" data-mode="delivery" class="mode-card flex flex-col items-start gap-1 rounded-xl border p-4 text-left transition">
                                <span class="text-2xl">🚚</span><span class="text-sm font-semibold text-navy-800">Delivery</span><span class="text-xs text-slate-500">We'll deliver to your door</span>
                            </button>
                            <button type="button" data-mode="pickup" class="mode-card flex flex-col items-start gap-1 rounded-xl border p-4 text-left transition">
                                <span class="text-2xl">🏪</span><span class="text-sm font-semibold text-navy-800">Pickup</span><span class="text-xs text-slate-500">Pick up at a BW branch</span>
                            </button>
                        </div>
                        <input type="hidden" name="delivery_type" id="delivery_type" value="delivery">

                        <div id="delivery-address-wrap" class="mt-5">
                            <label class="mb-1 block text-sm font-semibold text-navy-800">📍 Delivery address</label>
                            <textarea name="address" id="address" rows="2" placeholder="House / unit no., street, barangay, city" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"></textarea>
                        </div>

                        <div class="mt-5">
                            <label id="branch-label" class="mb-3 block text-sm font-semibold text-navy-800">📍 Choose a branch to deliver from</label>
                            <button type="button" id="find-nearest-branch"
                                class="mb-2 inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-xs font-semibold text-brand-600 transition hover:bg-brand-100 disabled:opacity-60">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="7" /><circle cx="12" cy="12" r="2.5" /><line x1="12" y1="2" x2="12" y2="5" /><line x1="12" y1="19" x2="12" y2="22" /><line x1="2" y1="12" x2="5" y2="12" /><line x1="19" y1="12" x2="22" y2="12" />
                                </svg>
                                Find nearest store
                            </button>
                            <p id="nearest-branch-status" class="mb-2 hidden text-xs" role="status"></p>
                            <input type="text" id="branch-search" placeholder="Search by branch name, area, or city" class="mb-3 hidden w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            <div id="branch-list" class="grid max-h-80 gap-2 overflow-y-auto pr-1 sm:grid-cols-2"></div>
                            <input type="hidden" name="fulfillment_store_id" id="fulfillment_store_id">
                        </div>

                        <div id="delivery-speed-wrap" class="mt-5">
                            <p class="mb-2 text-sm font-semibold text-navy-800">Delivery option</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <button type="button" data-speed="standard" class="speed-card flex items-center justify-between rounded-xl border p-3 text-left transition">
                                    <span><span class="block text-sm font-semibold text-navy-800">Standard Delivery</span><span class="block text-xs text-slate-500">30–45 mins</span></span>
                                    <span class="standard-price text-sm font-bold text-brand-600"></span>
                                </button>
                                <button type="button" data-speed="express" class="speed-card flex items-center justify-between rounded-xl border p-3 text-left transition">
                                    <span><span class="block text-sm font-semibold text-navy-800">Express Delivery</span><span class="block text-xs text-slate-500">15–25 mins</span></span>
                                    <span class="text-sm font-bold text-brand-600">₱149.00</span>
                                </button>
                            </div>
                            <input type="hidden" name="delivery_speed" id="delivery_speed" value="standard">
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-navy-800"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs text-white">2</span>Customer Details</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Full Name</span>
                                <input type="text" id="cust_name" value="{{ $user['name'] ?? '' }}" placeholder="Juan Dela Cruz" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"></label>
                            <label class="block"><span class="mb-1 block text-xs font-medium text-slate-500">Mobile Number</span>
                                <input type="tel" name="phone" id="phone" value="{{ $contactNumber ?? '' }}" placeholder="0917 123 4567" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"></label>
                            <label class="block sm:col-span-2"><span class="mb-1 block text-xs font-medium text-slate-500">Email Address</span>
                                <input type="email" id="cust_email" value="{{ $user['email'] ?? '' }}" placeholder="you@email.com" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"></label>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-navy-800"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs text-white">3</span>Order Notes (Optional)</h2>
                        <textarea name="notes" rows="3" placeholder="Any special requests? (e.g. message on the cake, allergies, gate code)" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"></textarea>
                    </section>

                    <p id="form-error" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></p>

                    <button type="button" id="continue-to-payment" class="flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        Continue to Payment →
                    </button>
                    <a href="{{ route('menu') }}" class="block text-center text-sm font-medium text-slate-500 hover:text-brand-600">← Back to Cart</a>
                </div>

                {{-- ============ STEP 2: Payment ============ --}}
                <div id="step-payment" class="hidden space-y-6">
                    <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="mb-4 flex items-center gap-2 text-base font-bold text-navy-800"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-xs text-white">4</span>Payment Method</h2>
                        <div class="space-y-3">
                            @if($paymongoEnabled)
                                <button type="button" data-pay="paymongo" class="pay-card flex w-full items-center gap-3 rounded-xl border p-4 text-left transition">
                                    <span class="text-2xl">💳</span><span class="flex-1"><span class="block text-sm font-semibold text-navy-800">Pay online (GCash / Card / Maya)</span><span class="block text-xs text-slate-500">Secure checkout powered by PayMongo</span></span>
                                </button>
                            @endif
                            <button type="button" data-pay="qrph" class="pay-card flex w-full items-center gap-3 rounded-xl border p-4 text-left transition">
                                <span class="text-2xl">🔳</span><span class="flex-1"><span class="block text-sm font-semibold text-navy-800">QRPH</span><span class="block text-xs text-slate-500">Scan with any bank or e-wallet app</span></span>
                            </button>
                            <button type="button" data-pay="cash" id="pay-cash-card" class="pay-card hidden flex w-full items-center gap-3 rounded-xl border p-4 text-left transition">
                                <span class="text-2xl">💵</span><span class="flex-1"><span class="block text-sm font-semibold text-navy-800">Cash on Pickup</span><span class="block text-xs text-slate-500">Pay with cash when you pick up your order</span></span>
                            </button>
                        </div>
                        <input type="hidden" name="payment_method" id="payment_method" value="qrph">

                        <div id="qrph-panel" class="mt-4 hidden flex-col items-center gap-3 rounded-xl border border-slate-200 p-5 text-center">
                            <img id="qrph-image" src="" alt="QRPH payment code" class="h-48 w-48 rounded-lg">
                            <p class="text-sm font-semibold text-navy-800">Scan to pay <span id="qrph-amount"></span></p>
                            <p class="text-xs text-slate-500">Open your bank or e-wallet app, scan this QRPH code to pay, then place your order.</p>
                        </div>
                        <p id="cash-panel" class="mt-4 hidden rounded-xl border border-slate-200 p-4 text-sm text-slate-600">💵 Pay with cash when you pick up your order at the store. Please bring the exact amount if possible.</p>
                        <p id="paymongo-panel" class="mt-4 hidden rounded-xl border border-slate-200 p-4 text-sm text-slate-600">💳 You'll be redirected to PayMongo's secure checkout, then brought back here once it's done.</p>
                    </section>

                    <button type="submit" id="place-order" class="flex w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 py-3.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        Place Order
                    </button>
                    <button type="button" id="back-to-details" class="block w-full text-center text-sm font-medium text-slate-500 hover:text-brand-600">← Back to details</button>
                </div>
            </div>

            {{-- ---- order summary ---- --}}
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-navy-800">Order Summary</h2>
                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600"><span class="cart-item-count">0</span> items</span>
                    </div>
                    <ul id="summary-items" class="mt-4 space-y-3"></ul>
                    <a href="{{ route('menu') }}" class="mt-3 inline-block text-xs font-semibold text-brand-600 hover:underline">+ Add more items</a>

                    <div id="selected-branch-summary" class="mt-4 hidden rounded-xl border border-brand-100 bg-brand-50/50 p-3 text-xs"></div>

                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <div class="flex gap-2">
                            <input type="text" id="voucher-code-input" placeholder="Voucher code" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            <button type="button" id="voucher-apply-btn" class="shrink-0 rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Apply</button>
                        </div>
                        <p id="voucher-error" class="mt-1 hidden text-xs text-red-600"></p>
                        <p id="voucher-applied" class="mt-1 hidden text-xs font-medium text-green-600"></p>
                        <input type="hidden" name="voucher" id="voucher">
                    </div>

                    <div id="summary-totals" class="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-sm"></div>
                </div>
            </aside>
        </main>
    </form>

    <script id="live-checkout-config" type="application/json">{!! json_encode(['qrUrl' => route('checkout.qr'), 'storeUrl' => route('checkout.store')]) !!}</script>

    <script>
    (function () {
        const VAT_RATE = 0.12, DELIVERY_FEE = 79, EXPRESS_FEE = 149, FREE_DELIVERY_MIN = 1000;
        const peso = (n) => `₱${Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        // Default picture for items without an image (same as /menu); if it
        // fails to load, onerror clears the img leaving the plain gray box.
        const FALLBACK_IMG = 'https://xhy0hjgguaqll6zn.public.blob.vercel-storage.com/custom-cake-refs/1781654816384-p23ferfqoq.png';

        function readCheckout() {
            try { return JSON.parse(localStorage.getItem('bw_checkout') || 'null'); } catch { return null; }
        }

        const payload = readCheckout();
        const STORES = JSON.parse(document.getElementById('stores-data').textContent || '[]');
        const VOUCHER_DEFS = {};
        JSON.parse(document.getElementById('vouchers-data').textContent || '[]').forEach(v => {
            VOUCHER_DEFS[v.code] = { type: v.type, value: Number(v.value) || 0, label: v.label || '' };
        });

        if (!payload || !Array.isArray(payload.items) || !payload.items.length) {
            document.getElementById('empty-cart').classList.remove('hidden');
            document.getElementById('empty-cart').classList.add('flex');
            return;
        }
        document.getElementById('checkout-form').classList.remove('hidden');

        let items = payload.items.map(i => ({ ...i }));
        let mode = 'delivery';
        let speed = 'standard';
        let branchId = null;
        let voucherCode = payload.voucher || null;
        let payMethod = document.getElementById('payment_method').value;

        document.querySelectorAll('.cart-item-count').forEach(el => {
            el.textContent = items.reduce((s, i) => s + i.qty, 0);
        });

        // ---- reconcile against live product data ----
        fetch('/api/products').then(r => r.ok ? r.json() : []).then(rows => {
            const live = {};
            rows.forEach(p => { live[p.id] = p; });
            const priceChanges = [];
            const unavailable = [];
            items = items.map(i => {
                // Promo bundle line: every component must still be purchasable.
                // Its price isn't repriced here — the server re-verifies the
                // bundle against the saved promo and charges its saved price.
                if (i.bundle_products) {
                    const dead = i.bundle_products.map(id => live[id]).findIndex(p => !p || p.status === 'sold_out');
                    if (dead !== -1) unavailable.push({ name: i.name, reason: 'promo no longer available' });
                    return i;
                }
                const p = live[i.product_id];
                if (!p) { unavailable.push({ name: i.name, reason: 'no longer available' }); return i; }
                if (p.status === 'sold_out') { unavailable.push({ name: i.name, reason: 'sold out' }); return i; }
                const livePrice = Number(p.price);
                if (Number.isFinite(livePrice) && livePrice !== Number(i.price)) {
                    priceChanges.push({ name: i.name, from: Number(i.price), to: livePrice });
                    return { ...i, price: livePrice };
                }
                return i;
            });

            if (priceChanges.length) {
                document.getElementById('price-changes-banner').classList.remove('hidden');
                document.getElementById('price-changes-list').innerHTML = priceChanges.map(c => `<li>${c.name}: ${peso(c.from)} → <span class="font-semibold">${peso(c.to)}</span></li>`).join('');
            }
            if (unavailable.length) {
                document.getElementById('unavailable-banner').classList.remove('hidden');
                document.getElementById('unavailable-list').innerHTML = unavailable.map(u => `<li>${u.name} — ${u.reason}</li>`).join('');
                document.getElementById('continue-to-payment').disabled = true;
                document.getElementById('continue-to-payment').classList.add('cursor-not-allowed', 'opacity-60');
            }
            renderSummary();
        }).catch(() => {});

        function subtotal() { return items.reduce((s, i) => s + Number(i.price || 0) * i.qty, 0); }
        function voucherDiscount(sub) {
            const def = voucherCode ? VOUCHER_DEFS[voucherCode] : null;
            if (!def) return 0;
            if (def.type === 'percent') return (sub * def.value) / 100;
            if (def.type === 'amount') return Math.min(def.value, sub);
            return 0;
        }
        function computeTotals() {
            const sub = subtotal();
            const discount = voucherDiscount(sub);
            const discounted = sub - discount;
            const def = voucherCode ? VOUCHER_DEFS[voucherCode] : null;
            const freeDelivery = sub >= FREE_DELIVERY_MIN || def?.type === 'freedel';
            let delivery = 0;
            if (mode === 'delivery') delivery = speed === 'express' ? EXPRESS_FEE : (freeDelivery ? 0 : DELIVERY_FEE);
            const vat = discounted * VAT_RATE;
            const total = discounted + vat + delivery;
            return { sub, discount, delivery, vat, total, freeDelivery };
        }

        function renderSummary() {
            const t = computeTotals();
            document.getElementById('summary-items').innerHTML = items.map(i => `
                <li class="flex items-center gap-3">
                    <span class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                        <img src="${i.img || FALLBACK_IMG}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-navy-800 px-1 text-[0.6rem] font-bold text-white">${i.qty}</span>
                    </span>
                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-navy-800">${i.name}</span>
                    <span class="text-sm font-semibold text-navy-800">${peso(Number(i.price || 0) * i.qty)}</span>
                </li>`).join('');

            document.getElementById('summary-totals').innerHTML = `
                <div class="flex justify-between text-slate-600"><span>Subtotal</span><span class="font-semibold text-navy-800">${peso(t.sub)}</span></div>
                ${t.discount > 0 ? `<div class="flex justify-between text-green-600"><span>Discount</span><span class="font-semibold">−${peso(t.discount)}</span></div>` : ''}
                <div class="flex justify-between text-slate-600"><span>${mode === 'pickup' ? 'Pickup' : 'Delivery Fee'}</span><span class="${t.delivery === 0 ? 'font-semibold text-green-600' : ''}">${t.delivery === 0 ? 'FREE' : peso(t.delivery)}</span></div>
                <div class="flex justify-between text-slate-600"><span>VAT (12%)</span><span>${peso(t.vat)}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-navy-800"><span>Total</span><span>${peso(t.total)}</span></div>
            `;

            document.querySelector('.standard-price').textContent = t.freeDelivery ? 'FREE' : peso(DELIVERY_FEE);

            const placeBtn = document.getElementById('place-order');
            if (placeBtn) {
                placeBtn.textContent = payMethod === 'qrph' ? `I've Paid · ${peso(t.total)}` : payMethod === 'paymongo' ? `Pay online · ${peso(t.total)}` : `Place Order · ${peso(t.total)}`;
            }

            if (payMethod === 'qrph') {
                document.getElementById('qrph-amount').textContent = `Scan to pay ${peso(t.total)}`;
                document.getElementById('qrph-image').src = `{{ route('checkout.qr') }}?amount=${t.total.toFixed(2)}`;
            }

            return t;
        }

        // ---- delivery mode / speed ----
        function paintModeCards() {
            document.querySelectorAll('.mode-card').forEach(btn => {
                const on = btn.dataset.mode === mode;
                btn.classList.toggle('border-brand-400', on);
                btn.classList.toggle('bg-brand-50/60', on);
                btn.classList.toggle('ring-2', on);
                btn.classList.toggle('ring-brand-500/20', on);
                btn.classList.toggle('border-slate-200', !on);
            });
            document.getElementById('delivery-address-wrap').classList.toggle('hidden', mode !== 'delivery');
            document.getElementById('delivery-speed-wrap').classList.toggle('hidden', mode !== 'delivery');
            document.getElementById('branch-label').textContent = mode === 'pickup' ? '📍 Choose a pickup branch' : '📍 Choose a branch to deliver from';
            document.getElementById('pay-cash-card').classList.toggle('hidden', mode !== 'pickup');
            if (mode !== 'pickup' && payMethod === 'cash') setPayMethod('qrph');
            renderBranches();
        }
        document.querySelectorAll('.mode-card').forEach(btn => btn.addEventListener('click', () => {
            mode = btn.dataset.mode;
            document.getElementById('delivery_type').value = mode;
            branchId = null;
            document.getElementById('fulfillment_store_id').value = '';
            paintModeCards();
            renderSummary();
        }));

        function paintSpeedCards() {
            document.querySelectorAll('.speed-card').forEach(btn => {
                const on = btn.dataset.speed === speed;
                btn.classList.toggle('border-brand-400', on);
                btn.classList.toggle('bg-brand-50/60', on);
                btn.classList.toggle('ring-2', on);
                btn.classList.toggle('border-slate-200', !on);
            });
        }
        document.querySelectorAll('.speed-card').forEach(btn => btn.addEventListener('click', () => {
            speed = btn.dataset.speed;
            document.getElementById('delivery_speed').value = speed;
            paintSpeedCards();
            renderSummary();
        }));

        // ---- branch picker ----
        function servingStores() {
            return STORES.filter(s => (s.fulfillment || 'both') === 'both' || (s.fulfillment || 'both') === mode);
        }
        function renderBranches() {
            const list = document.getElementById('branch-list');
            const q = (document.getElementById('branch-search').value || '').trim().toLowerCase();
            const serving = servingStores();
            document.getElementById('branch-search').classList.toggle('hidden', serving.length <= 5);
            const matches = !q ? serving : serving.filter(s => [s.name, s.address, s.region].some(f => (f || '').toLowerCase().includes(q)));

            if (!serving.length) {
                list.innerHTML = `<p class="col-span-2 rounded-xl border border-slate-200 p-4 text-sm text-slate-500">No branches offer ${mode} right now. Please try ${mode === 'pickup' ? 'delivery' : 'pickup'} or check the <a href="/stores" class="font-semibold text-brand-600 hover:underline">store locator</a>.</p>`;
                return;
            }
            if (!matches.length) {
                list.innerHTML = `<p class="col-span-2 rounded-xl border border-slate-200 p-4 text-sm text-slate-500">No branches match “${q}”.</p>`;
                return;
            }
            if (branchId && !serving.some(s => s.id === branchId)) branchId = null;

            list.innerHTML = matches.map(s => `
                <button type="button" data-branch="${s.id}" class="branch-card flex items-start gap-3 rounded-xl border p-3 text-left transition ${branchId === s.id ? 'border-brand-400 bg-brand-50/60 ring-2 ring-brand-500/20' : 'border-slate-200 hover:border-brand-200'}">
                    <span class="mt-0.5">📍</span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-navy-800">${s.name}</span><span class="block text-xs text-slate-500">${s.address}</span></span>
                </button>`).join('');
            list.querySelectorAll('[data-branch]').forEach(btn => btn.addEventListener('click', () => {
                branchId = Number(btn.dataset.branch);
                document.getElementById('fulfillment_store_id').value = branchId;
                renderBranches();
                renderSelectedBranchSummary();
            }));
        }
        document.getElementById('branch-search').addEventListener('input', renderBranches);

        // ---- find nearest branch (geolocation, mirrors the /stores locator) ----
        const nearestBranchBtn = document.getElementById('find-nearest-branch');
        const nearestBranchStatus = document.getElementById('nearest-branch-status');

        function setNearestBranchStatus(text, isError) {
            nearestBranchStatus.textContent = text;
            nearestBranchStatus.classList.toggle('hidden', !text);
            nearestBranchStatus.classList.toggle('text-red-600', !!isError);
            nearestBranchStatus.classList.toggle('text-slate-500', !isError);
        }

        function haversineKm(lat1, lng1, lat2, lng2) {
            const toRad = (d) => (d * Math.PI) / 180;
            const a = Math.sin(toRad(lat2 - lat1) / 2) ** 2
                + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(toRad(lng2 - lng1) / 2) ** 2;
            return 2 * 6371 * Math.asin(Math.sqrt(a));
        }

        nearestBranchBtn.addEventListener('click', () => {
            if (!navigator.geolocation) { setNearestBranchStatus('Location is not supported by this browser.', true); return; }
            nearestBranchBtn.disabled = true;
            setNearestBranchStatus('Locating you…');
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    nearestBranchBtn.disabled = false;
                    const { latitude: lat, longitude: lng } = pos.coords;
                    // Only branches that serve the current mode and have a map pin.
                    const pinned = servingStores().filter(s => Number.isFinite(Number(s.latitude)) && Number.isFinite(Number(s.longitude)));
                    if (!pinned.length) { setNearestBranchStatus('No branches have map pins yet.', true); return; }
                    const dist = (s) => haversineKm(lat, lng, Number(s.latitude), Number(s.longitude));
                    const nearest = pinned.reduce((best, s) => dist(s) < dist(best) ? s : best);
                    branchId = Number(nearest.id);
                    document.getElementById('fulfillment_store_id').value = branchId;
                    document.getElementById('branch-search').value = '';
                    renderBranches();
                    renderSelectedBranchSummary();
                    document.querySelector(`#branch-list [data-branch="${branchId}"]`)?.scrollIntoView({ block: 'nearest' });
                    const km = dist(nearest);
                    setNearestBranchStatus(`Nearest store: ${nearest.name} — ${km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km'} away`);
                },
                (err) => {
                    nearestBranchBtn.disabled = false;
                    setNearestBranchStatus(err.code === err.PERMISSION_DENIED
                        ? 'Location permission denied — allow location access and try again.'
                        : 'Could not get your location. Please try again.', true);
                },
                { enableHighAccuracy: true, timeout: 10000 },
            );
        });

        function renderSelectedBranchSummary() {
            const el = document.getElementById('selected-branch-summary');
            const store = STORES.find(s => s.id === branchId);
            if (!store) { el.classList.add('hidden'); return; }
            el.classList.remove('hidden');
            el.innerHTML = `<span class="block font-semibold text-navy-800">${mode === 'pickup' ? 'Pickup at' : 'Delivered by'} ${store.name}</span><span class="block text-slate-500">${store.address}</span>`;
        }

        // ---- voucher ----
        function applyVoucherPreview() {
            const key = (document.getElementById('voucher-code-input').value || '').trim().toUpperCase();
            const err = document.getElementById('voucher-error');
            const applied = document.getElementById('voucher-applied');
            if (!key) return;
            if (!VOUCHER_DEFS[key]) { err.textContent = 'Invalid voucher code'; err.classList.remove('hidden'); applied.classList.add('hidden'); return; }
            voucherCode = key;
            document.getElementById('voucher').value = key;
            err.classList.add('hidden');
            applied.textContent = `🎟️ ${key} applied`;
            applied.classList.remove('hidden');
            renderSummary();
        }
        document.getElementById('voucher-apply-btn').addEventListener('click', applyVoucherPreview);
        if (voucherCode) { document.getElementById('voucher').value = voucherCode; }

        // ---- payment method ----
        function setPayMethod(m) {
            payMethod = m;
            document.getElementById('payment_method').value = m;
            document.querySelectorAll('.pay-card').forEach(btn => {
                const on = btn.dataset.pay === m;
                btn.classList.toggle('border-brand-400', on);
                btn.classList.toggle('bg-brand-50/60', on);
                btn.classList.toggle('ring-2', on);
                btn.classList.toggle('border-slate-200', !on);
            });
            document.getElementById('qrph-panel').classList.toggle('hidden', m !== 'qrph');
            document.getElementById('qrph-panel').classList.toggle('flex', m === 'qrph');
            document.getElementById('cash-panel').classList.toggle('hidden', m !== 'cash');
            document.getElementById('paymongo-panel').classList.toggle('hidden', m !== 'paymongo');
            renderSummary();
        }
        document.querySelectorAll('.pay-card').forEach(btn => btn.addEventListener('click', () => setPayMethod(btn.dataset.pay)));

        // ---- step navigation ----
        function showStep(step) {
            document.getElementById('step-form').classList.toggle('hidden', step !== 'form');
            document.getElementById('step-payment').classList.toggle('hidden', step !== 'payment');
            document.getElementById('step-pill-1').classList.toggle('bg-brand-500', step === 'form');
            document.getElementById('step-pill-1').classList.toggle('text-white', step === 'form');
            document.getElementById('step-pill-1').classList.toggle('bg-slate-200', step !== 'form');
            document.getElementById('step-pill-2').classList.toggle('bg-brand-500', step === 'payment');
            document.getElementById('step-pill-2').classList.toggle('text-white', step === 'payment');
            document.getElementById('step-pill-2').classList.toggle('bg-slate-200', step !== 'payment');
            window.scrollTo({ top: 0 });
        }

        document.getElementById('continue-to-payment').addEventListener('click', () => {
            const name = document.getElementById('cust_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const email = document.getElementById('cust_email').value.trim();
            const err = document.getElementById('form-error');
            err.classList.add('hidden');

            if (!name || !phone || !email) { err.textContent = 'Please fill in your name, mobile number, and email.'; err.classList.remove('hidden'); return; }
            if (mode === 'delivery' && !document.getElementById('address').value.trim()) { err.textContent = 'Please enter a delivery address.'; err.classList.remove('hidden'); return; }
            if (!branchId) { err.textContent = mode === 'pickup' ? 'Please choose a branch to pick up from.' : 'Please choose a branch to deliver from.'; err.classList.remove('hidden'); return; }

            showStep('payment');
        });
        document.getElementById('back-to-details').addEventListener('click', () => showStep('form'));

        document.getElementById('checkout-form').addEventListener('submit', () => {
            document.getElementById('items_json').value = JSON.stringify(items.map(i => i.bundle_products
                ? { bundle_products: i.bundle_products, qty: i.qty }
                : { product_id: i.product_id, name: i.name, qty: i.qty }));
            document.getElementById('place-order').disabled = true;
            document.getElementById('place-order').textContent = 'Placing order…';
        });

        // ---- initial paint ----
        paintModeCards();
        paintSpeedCards();
        setPayMethod('qrph');
        renderSummary();

        @if($step === 'payment')
            showStep('payment');
        @endif
    })();
    </script>
@endif
</body>
</html>
