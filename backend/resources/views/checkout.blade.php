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
        <div class="mx-auto flex h-20 max-w-6xl items-center px-4 sm:px-6">
            <a href="/"><img src="{{ $nav['logo'] ?? '/images/logo (1).png' }}" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24"></a>
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
                        {{ ['qrph' => 'QRPH', 'cash' => $order->delivery_type === 'pickup' ? 'Cash on Pickup' : 'Cash on Delivery', 'paymongo' => 'Online (PayMongo)'][$order->payment_method] ?? 'Cash' }}
                        <span class="{{ $order->payment_status === 'paid' ? 'text-green-600' : 'text-amber-600' }}"> · {{ $order->payment_status === 'paid' ? 'Paid' : ($order->payment_method === 'cash' ? ($order->delivery_type === 'pickup' ? 'Pay on pickup' : 'Pay on delivery') : 'Awaiting payment') }}</span>
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
        <div class="mx-auto flex h-20 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-4">
                <a href="/" class="shrink-0"><img src="{{ $nav['logo'] ?? '/images/logo (1).png' }}" alt="bw Superbakeshop" class="h-14 w-20 shrink-0 object-cover sm:h-16 sm:w-24"></a>
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
                        <div id="selected-branch-summary" class="mt-4 hidden rounded-xl border border-brand-100 bg-brand-50/50 p-3 text-sm"></div>

                        <div id="delivery-address-wrap" class="mt-5">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <p id="saved-address-title" class="text-sm font-semibold text-navy-800">Your saved locations</p>
                                <button type="button" id="add-delivery-address" aria-haspopup="dialog" aria-controls="delivery-address-dialog" class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-xs font-semibold text-brand-600 transition hover:bg-brand-100 disabled:opacity-60">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                    Add new location
                                </button>
                            </div>
                            <div id="saved-address-list" class="grid gap-3 sm:grid-cols-2" role="group" aria-labelledby="saved-address-title"></div>
                            <input type="hidden" name="address" id="address" value="{{ $savedAddress ?? '' }}">
                            <input type="hidden" name="delivery_latitude" id="checkout-delivery-latitude">
                            <input type="hidden" name="delivery_longitude" id="checkout-delivery-longitude">
                            <p id="saved-address-empty" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center text-sm text-slate-500">No saved locations yet. Add a location for delivery.</p>
                        </div>

                        <div id="pickup-branch-wrap" class="mt-5 hidden">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <label id="branch-label" class="block text-sm font-semibold text-navy-800">📍 Choose a pickup branch</label>
                            <button type="button" id="find-nearest-branch"
                                class="ml-auto inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-2 text-xs font-semibold text-brand-600 transition hover:bg-brand-100 disabled:opacity-60">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="7" /><circle cx="12" cy="12" r="2.5" /><line x1="12" y1="2" x2="12" y2="5" /><line x1="12" y1="19" x2="12" y2="22" /><line x1="2" y1="12" x2="5" y2="12" /><line x1="19" y1="12" x2="22" y2="12" />
                                </svg>
                                Find nearest store
                            </button>
                            </div>
                            <p id="nearest-branch-status" class="mb-2 hidden text-xs" role="status"></p>
                            <input type="text" id="branch-search" placeholder="Search by branch name, area, or city" class="mb-3 hidden w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                            <div id="branch-list" class="grid max-h-80 gap-2 overflow-y-auto pr-1 sm:grid-cols-2"></div>
                            <input type="hidden" name="fulfillment_store_id" id="fulfillment_store_id">
                        </div>

                        <div id="delivery-speed-wrap" class="mt-5">
                            <p class="mb-2 text-sm font-semibold text-navy-800">Delivery option</p>
                            <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                                <p class="text-sm font-bold text-green-800">Grab delivery</p>
                                <p class="mt-1 text-sm text-slate-600">The Grab delivery fee is paid separately and is not included in your order total.</p>
                            </div>
                            <input type="hidden" name="delivery_speed" id="delivery_speed" value="grab">
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
                            @foreach(($onlineMethods ?? []) as $method)
                                <button type="button" data-pay="{{ $method }}" class="pay-card flex w-full items-center gap-3 rounded-xl border p-4 text-left transition">
                                    <span class="text-2xl">{{ ['gcash' => '📱', 'card' => '💳', 'qrph' => '▦'][$method] }}</span><span class="flex-1"><span class="block text-sm font-semibold text-navy-800">{{ ['gcash' => 'GCash', 'card' => 'Credit / debit card', 'qrph' => 'QR Ph'][$method] }}</span><span class="block text-xs text-slate-500">{{ ['gcash' => 'Approve securely in GCash', 'card' => 'Visa / Mastercard · secure card payment', 'qrph' => 'Scan with your bank or e-wallet app'][$method] }}</span></span>
                                </button>
                            @endforeach
                            <button type="button" data-pay="cash" id="pay-cash-card" class="pay-card flex w-full items-center gap-3 rounded-xl border p-4 text-left transition">
                                <span class="text-2xl">💵</span><span class="flex-1"><span id="cash-method-label" class="block text-sm font-semibold text-navy-800">Cash on Delivery</span><span id="cash-method-help" class="block text-xs text-slate-500">Pay with cash when your order arrives</span></span>
                            </button>
                        </div>
                        <input type="hidden" name="payment_method" id="payment_method" value="cash">
                        <input type="hidden" name="payment_channel" id="payment_channel">

                        <p id="cash-panel" class="mt-4 hidden rounded-xl border border-slate-200 p-4 text-sm text-slate-600">💵 Pay with cash when you pick up your order at the store. Please bring the exact amount if possible.</p>
                        <p id="paymongo-panel" class="mt-4 hidden rounded-xl border border-slate-200 p-4 text-sm text-slate-600">Your order details are already filled in. Complete payment securely; confirmation is checked automatically.</p>
                    </section>
                    <p id="payment-error" role="alert" class="hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"></p>

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

    <dialog id="delivery-address-dialog" aria-labelledby="delivery-address-dialog-title" class="m-auto max-h-[90vh] w-[calc(100%_-_2rem)] max-w-lg overflow-y-auto rounded-3xl bg-white p-6 text-navy-800 shadow-2xl backdrop:bg-navy-950/50 backdrop:backdrop-blur-sm sm:p-8">
        <form id="delivery-address-form">
            <h2 id="delivery-address-dialog-title" class="text-xl font-bold">Add a new location</h2>
            <p class="mt-2 text-sm text-slate-500">Save an address to your account and use it for this delivery.</p>
            <p id="delivery-address-label-title" class="mb-2 mt-5 block text-sm font-semibold">Label <span class="font-normal text-slate-400">(optional)</span></p>
            <div class="grid grid-cols-3 gap-2" role="group" aria-labelledby="delivery-address-label-title">
                <button type="button" data-address-label="Home" aria-pressed="false" class="address-label-choice rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:border-brand-300">Home</button>
                <button type="button" data-address-label="Work" aria-pressed="false" class="address-label-choice rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:border-brand-300">Work</button>
                <button type="button" data-address-label="Partner" aria-pressed="false" class="address-label-choice rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:border-brand-300">Partner</button>
            </div>
            <input type="hidden" id="delivery-address-label" value="">
            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">Pin your delivery location</p>
                        <p class="mt-1 text-xs text-slate-500">Use your current location or tap the map to place the pin.</p>
                    </div>
                    <button type="button" id="locate-delivery-address" class="inline-flex shrink-0 items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-600 transition hover:bg-brand-100 disabled:opacity-60">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                        <span>Locate me</span>
                    </button>
                </div>
                <div id="delivery-address-map" class="h-56 overflow-hidden rounded-xl border border-slate-200 bg-slate-100" role="application" aria-label="Map. Tap to pin your delivery location."></div>
                <p id="delivery-address-map-status" class="mt-2 min-h-4 text-xs text-slate-500" role="status" aria-live="polite">Tap the map to drop a pin.</p>
                <input type="hidden" id="new-address-latitude">
                <input type="hidden" id="new-address-longitude">
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-semibold">Province / Metro Manila
                    <select id="address-province" required disabled class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-normal disabled:bg-slate-50"><option value="">Select province</option></select>
                </label>
                <label class="block text-sm font-semibold">City / Municipality
                    <select id="address-city" required disabled class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-normal disabled:bg-slate-50"><option value="">Select province first</option></select>
                </label>
                <label class="block text-sm font-semibold sm:col-span-2">Barangay
                    <select id="address-barangay" required disabled class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-normal disabled:bg-slate-50"><option value="">Select city first</option></select>
                </label>
            </div>
            <p id="address-area-status" class="mt-2 text-xs text-slate-500 empty:hidden" role="status"></p>
            <button type="button" id="address-area-retry" hidden class="mt-2 text-xs font-semibold text-brand-600 underline">Retry loading locations</button>
            <label for="new-delivery-address" class="mb-2 mt-4 block text-sm font-semibold">House / unit no., street &amp; subdivision</label>
            <textarea id="new-delivery-address" rows="2" required maxlength="300" placeholder="e.g. Unit 2, 123 Mabini St., Sunny Village" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"></textarea>
            <label for="delivery-address-landmark" class="mb-2 mt-4 block text-sm font-semibold">Nearby landmark <span class="font-normal text-slate-400">(optional)</span></label>
            <input type="text" id="delivery-address-landmark" maxlength="120" placeholder="e.g. Near St. Joseph Church, beside the pharmacy" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            <p class="mt-2 text-xs text-slate-500">A nearby church, school, shop, or building can help the rider find you.</p>
            <p id="save-address-status" class="mt-3 text-sm text-slate-500 empty:hidden" role="status"></p>
            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <button type="button" id="cancel-delivery-address" class="rounded-full bg-slate-100 px-5 py-2.5 text-sm font-semibold">Cancel</button>
                <button type="submit" id="save-delivery-address" class="rounded-full bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Save location</button>
            </div>
        </form>
    </dialog>
    @vite('resources/js/ph-address-picker.js')
    @vite('resources/js/delivery-address-map.js')
    <script id="native-checkout-config" type="application/json">@json(['createUrl' => route('checkout.payment.create'), 'csrf' => csrf_token()])</script>
    @vite('resources/js/native-payments.js')
    <dialog id="delete-address-dialog" aria-labelledby="delete-address-title" aria-describedby="delete-address-description" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-3xl bg-white p-6 text-navy-800 shadow-2xl backdrop:bg-navy-950/50 backdrop:backdrop-blur-sm">
        <h2 id="delete-address-title" class="text-xl font-bold">Delete this location?</h2>
        <p id="delete-address-description" class="mt-2 text-sm text-slate-500">Are you sure? This location will be removed from your account. Your previous orders will stay unchanged.</p>
        <p id="delete-address-detail" class="mt-4 break-words rounded-xl bg-slate-50 p-4 text-sm"></p>
        <p id="delete-address-status" class="mt-3 text-sm text-red-600 empty:hidden" role="status"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="cancel-delete-address" autofocus class="rounded-full bg-slate-100 px-5 py-2.5 text-sm font-semibold disabled:opacity-50">Cancel</button>
            <button type="button" id="confirm-delete-address" class="rounded-full bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">Yes, delete</button>
        </div>
    </dialog>

    <script id="live-checkout-config" type="application/json">{!! json_encode(['qrUrl' => route('checkout.qr'), 'storeUrl' => route('checkout.store')]) !!}</script>

    {{-- Shared pricing formula + peso formatter (also used by menu.blade.php). --}}
    @include('partials.order-pricing')

    <script>
    (function () {
        let savedAddresses = @json($savedAddresses ?? []);
        const addressInput = document.getElementById('address');
        const checkoutLatitude = document.getElementById('checkout-delivery-latitude');
        const checkoutLongitude = document.getElementById('checkout-delivery-longitude');
        const addressList = document.getElementById('saved-address-list');
        const addressDialog = document.getElementById('delivery-address-dialog');
        const addressForm = document.getElementById('delivery-address-form');
        const newAddressInput = document.getElementById('new-delivery-address');
        const addressLabel = document.getElementById('delivery-address-label');
        const addressLabelChoices = [...document.querySelectorAll('.address-label-choice')];
        const addressStatus = document.getElementById('save-address-status');
        const saveAddressButton = document.getElementById('save-delivery-address');
        let selectedAddressIndex = savedAddresses.length ? 0 : -1;
        const deleteDialog = document.getElementById('delete-address-dialog');
        const deleteConfirm = document.getElementById('confirm-delete-address');
        const deleteCancel = document.getElementById('cancel-delete-address');
        const deleteStatus = document.getElementById('delete-address-status');
        let deletingIndex = null;
        function chooseAddressLabel(label) {
            addressLabel.value = label;
            addressLabelChoices.forEach(button => {
                const active = button.dataset.addressLabel === label;
                button.setAttribute('aria-pressed', String(active));
                button.classList.toggle('border-brand-500', active);
                button.classList.toggle('bg-brand-50', active);
                button.classList.toggle('text-brand-700', active);
                button.classList.toggle('font-semibold', active);
                button.classList.toggle('border-slate-300', !active);
                button.classList.toggle('text-slate-600', !active);
                button.classList.toggle('font-medium', !active);
            });
        }
        addressLabelChoices.forEach(button => button.addEventListener('click', () => {
            chooseAddressLabel(button.dataset.addressLabel);
        }));
        function renderAddresses() {
            const addLocationButton = document.getElementById('add-delivery-address');
            addLocationButton.hidden = savedAddresses.length >= 2;
            addLocationButton.classList.toggle('inline-flex', savedAddresses.length < 2);
            addLocationButton.classList.toggle('hidden', savedAddresses.length >= 2);
            addressInput.value = savedAddresses[selectedAddressIndex]?.address || '';
            checkoutLatitude.value = savedAddresses[selectedAddressIndex]?.latitude ?? '';
            checkoutLongitude.value = savedAddresses[selectedAddressIndex]?.longitude ?? '';
            addressList.replaceChildren();
            document.getElementById('saved-address-empty').classList.toggle('hidden', savedAddresses.length > 0);
            savedAddresses.forEach((saved, index) => {
                const active = selectedAddressIndex === index;
                const card = document.createElement('div');
                card.className = `flex items-start gap-2 rounded-xl border p-4 transition ${active ? 'border-brand-500 bg-brand-50 ring-1 ring-inset ring-brand-500' : 'border-slate-200 hover:border-brand-300'}`;
                const choice = document.createElement('label');
                choice.className = 'flex min-w-0 flex-1 cursor-pointer items-start gap-3';
                const radio = document.createElement('input');
                radio.type = 'radio'; radio.name = 'saved_delivery_address'; radio.value = index;
                radio.checked = active; radio.className = 'mt-1 shrink-0 accent-orange-500';
                const text = document.createElement('span');
                text.className = 'min-w-0';
                const title = document.createElement('span');
                title.className = 'block text-sm font-semibold text-navy-800';
                title.textContent = saved.label || 'Delivery address';
                const detail = document.createElement('span');
                detail.className = 'mt-1 block break-words text-sm text-slate-500';
                detail.textContent = saved.address;
                text.append(title, detail); choice.append(radio, text);
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-red-600 transition hover:bg-red-50 focus-visible:outline-red-600';
                remove.title = 'Delete location';
                const trash = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                trash.setAttribute('viewBox', '0 0 24 24');
                trash.setAttribute('class', 'h-4 w-4');
                trash.setAttribute('fill', 'none');
                trash.setAttribute('stroke', 'currentColor');
                trash.setAttribute('stroke-width', '1.8');
                trash.setAttribute('stroke-linecap', 'round');
                trash.setAttribute('stroke-linejoin', 'round');
                trash.setAttribute('aria-hidden', 'true');
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', 'M3 6h18M9 6V4h6v2M5 6l1 14h12l1-14M10 10v6m4-6v6');
                trash.append(path);
                remove.append(trash);
                remove.setAttribute('aria-label', `Delete ${saved.label || 'delivery address'}: ${saved.address}`);
                remove.addEventListener('click', () => {
                    deletingIndex = index;
                    deleteStatus.textContent = '';
                    document.getElementById('delete-address-detail').textContent = `${saved.label ? saved.label + ' — ' : ''}${saved.address}`;
                    deleteDialog.showModal();
                });
                card.append(choice);
                if (savedAddresses.length > 1) card.append(remove);
                radio.addEventListener('change', () => {
                    selectedAddressIndex = index; renderAddresses();
                    addressList.querySelector(`input[value="${index}"]`)?.focus({ preventScroll: true });
                });
                addressList.append(card);
            });
        }
        deleteCancel.addEventListener('click', () => deleteDialog.close());
        deleteDialog.addEventListener('cancel', event => { if (deleteConfirm.disabled) event.preventDefault(); });
        deleteConfirm.addEventListener('click', async () => {
            if (deleteConfirm.disabled || deletingIndex === null) return;
            const entry = savedAddresses[deletingIndex];
            const previous = savedAddresses[selectedAddressIndex];
            deleteConfirm.disabled = true; deleteCancel.disabled = true;
            deleteStatus.textContent = 'Deleting location…';
            try {
                const response = await fetch(@json(route('profile.addresses.destroy')), {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({ index: deletingIndex, label: entry.label, address: entry.address }),
                });
                const data = await response.json().catch(() => { throw new Error('Could not delete this location. Please refresh and sign in again.'); });
                if (!response.ok) throw new Error(data.message || 'Could not delete this location. Please try again.');
                savedAddresses = data.addresses;
                selectedAddressIndex = savedAddresses.findIndex(saved => saved.address === previous?.address && saved.label === previous?.label);
                if (selectedAddressIndex < 0) selectedAddressIndex = savedAddresses.length ? 0 : -1;
                renderAddresses();
                deleteDialog.close();
                document.getElementById('add-delivery-address').focus();
            } catch (error) { deleteStatus.textContent = error.message; }
            finally { deleteConfirm.disabled = false; deleteCancel.disabled = false; }
        });
        document.getElementById('add-delivery-address').addEventListener('click', () => {
            if (savedAddresses.length >= 2) return;
            addressForm.reset(); chooseAddressLabel(''); addressStatus.textContent = ''; addressDialog.showModal(); newAddressInput.focus();
            document.dispatchEvent(new Event('bw:address-open'));
        });
        document.getElementById('cancel-delivery-address').addEventListener('click', () => addressDialog.close());
        addressDialog.addEventListener('cancel', event => { if (saveAddressButton.disabled) event.preventDefault(); });
        addressForm.addEventListener('submit', async event => {
            event.preventDefault();
            if (saveAddressButton.disabled) return;
            if (!newAddressInput.value.trim()) { addressStatus.textContent = 'Please enter a delivery address.'; newAddressInput.focus(); return; }
            const areaFields = ['address-barangay', 'address-city', 'address-province'].map(id => document.getElementById(id));
            const missingArea = areaFields.find(field => field.disabled || !field.value);
            if (missingArea) { addressStatus.textContent = 'Please select your province, city / municipality, and barangay.'; missingArea.focus(); return; }
            const landmark = document.getElementById('delivery-address-landmark').value.trim();
            const fullAddress = [newAddressInput.value.trim(), ...areaFields.map(field => field.selectedOptions[0].textContent), 'Philippines', ...(landmark ? [`Landmark: ${landmark}`] : [])].join(', ');
            if (fullAddress.length > 500) { addressStatus.textContent = 'Please shorten your house and street details.'; return; }
            saveAddressButton.disabled = true;
            document.getElementById('cancel-delivery-address').disabled = true;
            addressStatus.textContent = 'Saving address…';
            try {
                const response = await fetch(@json(route('profile.addresses.store')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({
                        label: addressLabel.value,
                        address: fullAddress,
                        latitude: document.getElementById('new-address-latitude').value || null,
                        longitude: document.getElementById('new-address-longitude').value || null,
                    }),
                });
                const data = await response.json().catch(() => { throw new Error('Could not save your address. Please refresh and sign in again.'); });
                if (!response.ok) throw new Error(data.message || 'Could not save your address. Please try again.');
                savedAddresses = data.addresses;
                selectedAddressIndex = Number(data.selected);
                renderAddresses();
                addressDialog.close();
            } catch (error) {
                addressStatus.textContent = error.message || 'Could not save your address. Please try again.';
            } finally {
                saveAddressButton.disabled = false;
                document.getElementById('cancel-delivery-address').disabled = false;
            }
        });
        renderAddresses();
        const { peso, DELIVERY_FEE, EXPRESS_DELIVERY_FEE, computeTotals, renderTotalsHTML } = window.OrderPricing;
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
        let speed = 'grab';
        let branchId = null;
        // Revalidate the saved preference against today's branches before using it.
        try {
            const preference = JSON.parse(localStorage.getItem('bw_location') || 'null');
            const preferredStore = STORES.find(store => String(store.id) === String(preference?.id));
            if (preferredStore) {
                branchId = preferredStore.id;
                if (preferredStore.fulfillment === 'pickup') mode = 'pickup';
                document.getElementById('delivery_type').value = mode;
                document.getElementById('fulfillment_store_id').value = branchId;
            }
        } catch { /* Continue with manual branch selection when storage is unavailable. */ }
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

        function renderSummary() {
            const sub = subtotal();
            const voucher = voucherCode ? VOUCHER_DEFS[voucherCode] : null;
            const t = computeTotals({ subtotal: sub, voucher, deliveryMode: mode, deliverySpeed: speed });
            document.getElementById('summary-items').innerHTML = items.map(i => `
                <li class="flex items-center gap-3">
                    <span class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                        <img src="${i.img || FALLBACK_IMG}" alt="" class="h-full w-full object-cover" onerror="this.remove()">
                        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-navy-800 px-1 text-[0.6rem] font-bold text-white">${i.qty}</span>
                    </span>
                    <span class="min-w-0 flex-1 break-words text-sm font-medium text-navy-800">${i.name}</span>
                    <span class="text-sm font-semibold text-navy-800">${peso(Number(i.price || 0) * i.qty)}</span>
                </li>`).join('');

            document.getElementById('summary-totals').innerHTML = renderTotalsHTML(t, { deliveryLabel: mode === 'pickup' ? 'Pickup' : 'Delivery Fee' });


            const placeBtn = document.getElementById('place-order');
            if (placeBtn) {
                placeBtn.textContent = `${({ gcash: 'Pay with GCash', card: 'Pay by card', qrph: 'Generate payment QR', cash: 'Place Order' })[payMethod] || 'Pay'} · ${peso(t.total)}`;
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
            document.getElementById('pickup-branch-wrap').classList.toggle('hidden', mode !== 'pickup');
            document.getElementById('delivery-speed-wrap').classList.toggle('hidden', mode !== 'delivery');
            document.getElementById('branch-label').textContent = mode === 'pickup' ? '📍 Choose a pickup branch' : '📍 Choose a branch to deliver from';
            document.getElementById('cash-method-label').textContent = mode === 'pickup' ? 'Cash on Pickup' : 'Cash on Delivery';
            document.getElementById('cash-method-help').textContent = mode === 'pickup' ? 'Pay with cash when you pick up your order' : 'Pay with cash when your order arrives';
            document.getElementById('cash-panel').textContent = mode === 'pickup'
                ? 'Pay with cash at the store when you pick up your order. Please bring the exact amount if possible.'
                : 'Pay for your order in cash when it arrives. The Grab delivery fee is paid separately. Please prepare the exact amount if possible.';
            renderBranches();
        }
        document.querySelectorAll('.mode-card').forEach(btn => btn.addEventListener('click', () => {
            mode = btn.dataset.mode;
            document.getElementById('delivery_type').value = mode;
            const selectedStore = STORES.find(store => String(store.id) === String(branchId));
            const fulfillsMode = selectedStore && ((selectedStore.fulfillment || 'both') === 'both' || (selectedStore.fulfillment || 'both') === mode);
            if (!fulfillsMode) branchId = null;
            document.getElementById('fulfillment_store_id').value = branchId || '';
            paintModeCards();
            renderSelectedBranchSummary();
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
            if (branchId && !serving.some(s => String(s.id) === String(branchId))) branchId = null;

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
            const store = STORES.find(s => String(s.id) === String(branchId));
            if (!store) { el.classList.add('hidden'); el.replaceChildren(); return; }
            el.classList.remove('hidden');
            const heading = document.createElement('span');
            heading.className = 'block font-semibold text-navy-800';
            heading.textContent = `${mode === 'pickup' ? 'Pickup at' : 'Delivery from'} ${store.name}`;
            const address = document.createElement('span');
            address.className = 'mt-1 block text-slate-600';
            address.textContent = store.address;
            el.replaceChildren(heading, address);
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
            document.getElementById('payment_method').value = m === 'cash' ? 'cash' : 'paymongo';
            document.getElementById('payment_channel').value = m === 'cash' ? '' : m;
            document.querySelectorAll('.pay-card').forEach(btn => {
                const on = btn.dataset.pay === m;
                btn.classList.toggle('border-brand-400', on);
                btn.classList.toggle('bg-brand-50/60', on);
                btn.classList.toggle('ring-2', on);
                btn.classList.toggle('border-slate-200', !on);
            });
            document.getElementById('cash-panel').classList.toggle('hidden', m !== 'cash');
            document.getElementById('paymongo-panel').classList.toggle('hidden', m === 'cash');
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
            if (!branchId) { err.textContent = mode === 'pickup' ? 'Please choose a branch to pick up from.' : 'Please go back to the menu and choose a nearby branch for delivery.'; err.classList.remove('hidden'); return; }

            showStep('payment');
        });
        document.getElementById('back-to-details').addEventListener('click', () => showStep('form'));

        document.getElementById('checkout-form').addEventListener('submit', event => {
            document.getElementById('items_json').value = JSON.stringify(items.map(i => i.bundle_products
                ? { bundle_products: i.bundle_products, qty: i.qty }
                : { product_id: i.product_id, name: i.name, qty: i.qty }));
            if (payMethod !== 'cash') {
                event.preventDefault();
                if (window.BWNativeCheckout) window.BWNativeCheckout(event.currentTarget);
                else {
                    const error = document.getElementById('payment-error');
                    error.textContent = 'Payment is still loading. Please try again in a moment.';
                    error.classList.remove('hidden');
                }
                return;
            }
            document.getElementById('place-order').disabled = true;
            document.getElementById('place-order').textContent = 'Placing order…';
        });

        // ---- initial paint ----
        paintModeCards();
        paintSpeedCards();
        renderSelectedBranchSummary();
        setPayMethod(@json(($onlineMethods ?? [])[0] ?? 'cash'));
        renderSummary();

        @if($step === 'payment')
            showStep('payment');
        @endif
    })();
    </script>
@endif
</body>
</html>
