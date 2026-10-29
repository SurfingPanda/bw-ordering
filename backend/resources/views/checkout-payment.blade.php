<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete payment — BW Superbakeshop</title>
    @include('partials.favicon')
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-slate-50 text-navy-800">
    <header class="border-b border-slate-100 bg-white">
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-4 py-5 sm:px-6">
            <a href="{{ route('menu') }}" class="text-lg font-bold">BW Superbakeshop</a>
            <a href="{{ route('my-orders') }}" class="text-sm font-medium text-slate-500 hover:text-brand-600">My orders</a>
        </div>
    </header>
    <main class="mx-auto grid max-w-4xl gap-6 px-4 py-8 sm:px-6 md:grid-cols-[1fr_18rem]">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-bold uppercase tracking-widest text-brand-600">Secure payment</p>
            <h1 class="mt-2 text-2xl font-bold">Complete your payment</h1>
            <p class="mt-2 text-sm text-slate-500">Your order details are saved. Just complete the payment below.</p>
            <p id="native-status" class="mt-5 rounded-xl bg-slate-50 p-4 text-sm text-slate-600" role="status" aria-live="polite">Checking payment status…</p>
            <p id="native-error" class="mt-3 hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert"></p>
            <form id="native-payment-form" class="mt-5">
                <input type="hidden" id="native-channel" value="{{ $order->payment_channel }}">
                <p class="text-xs font-medium text-slate-500">Selected payment method</p>
                <p id="native-channel-label" class="mt-1 text-sm font-semibold">{{ ['gcash' => 'GCash', 'card' => 'Credit / debit card', 'qrph' => 'QR Ph'][$order->payment_channel] ?? 'Online payment' }}</p>
                <div id="native-card-fields" class="mt-4 hidden space-y-4">
                    <label class="block text-sm font-semibold">Card number
                        <input id="native-card-number" type="text" inputmode="numeric" autocomplete="cc-number" maxlength="23" placeholder="1234 5678 9012 3456" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal" disabled>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm font-semibold">Expiry
                            <input id="native-card-expiry" type="text" inputmode="numeric" autocomplete="cc-exp" maxlength="7" placeholder="MM / YY" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal" disabled>
                        </label>
                        <label class="block text-sm font-semibold">Security code
                            <input id="native-card-cvc" type="password" inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="CVC" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal" disabled>
                        </label>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500">Your card details go directly to PayMongo and are never saved on this website.</p>
                </div>
                <button type="submit" id="native-pay-button" disabled class="mt-5 w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-3 text-sm font-bold text-white disabled:opacity-50">Continue</button>
            </form>
            <div id="native-qr-panel" class="mt-5 hidden rounded-2xl border border-slate-200 p-5 text-center">
                <img id="native-qr-image" alt="Scan this QR Ph code to pay for your order" class="mx-auto h-56 w-56 max-w-full object-contain">
                <p class="mt-3 text-lg font-bold">₱{{ number_format($order->total, 2) }}</p>
                <p class="mt-2 text-sm text-slate-500">Scan with your bank or e-wallet app. This single-use QR expires after 30 minutes. We’ll confirm payment automatically.</p>
            </div>
            <a id="native-authorize" hidden class="mt-4 hidden rounded-full bg-brand-500 px-5 py-3 text-center text-sm font-bold text-white">Continue to authorize payment</a>
            <button type="button" id="native-check" class="mt-5 w-full rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold disabled:opacity-50">Check payment status</button>
            <a href="{{ route('menu') }}" class="mt-4 block text-center text-xs font-medium text-slate-500 hover:text-brand-600">Back to menu</a>
        </section>
        <aside class="h-fit rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <h2 class="font-bold">Order #{{ strtoupper(substr((string) $order->id, 0, 8)) }}</h2>
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                @foreach($order->items ?? [] as $item)
                    <li class="flex justify-between gap-3"><span>{{ $item['qty'] ?? 1 }} × {{ $item['name'] ?? 'Item' }}</span><span class="shrink-0">₱{{ number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) }}</span></li>
                @endforeach
            </ul>
            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                <p class="flex justify-between gap-3"><span>Subtotal</span><span>₱{{ number_format($order->subtotal, 2) }}</span></p>
                @if($order->discount > 0)<p class="flex justify-between gap-3 text-green-700"><span>Discount</span><span>−₱{{ number_format($order->discount, 2) }}</span></p>@endif
                <p class="flex justify-between gap-3"><span>VAT</span><span>₱{{ number_format($order->vat, 2) }}</span></p>
                @if($order->delivery_type === 'delivery')<p class="flex justify-between gap-3 text-slate-500"><span>Grab delivery</span><span>Paid separately</span></p>@endif
                <p class="flex justify-between border-t border-slate-100 pt-3 text-lg font-bold"><span>Total</span><span>₱{{ number_format($order->total, 2) }}</span></p>
            </div>
            <div class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500">
                <p class="font-semibold text-navy-800">{{ $order->customer_name }}</p>
                <p class="mt-1 break-words">{{ $order->customer_email }}</p>
                <p class="mt-1">{{ $order->customer_phone }}</p>
                <p class="mt-3 break-words">{{ $order->delivery_type === 'pickup' ? $order->fulfillment_branch : $order->address }}</p>
            </div>
        </aside>
    </main>
    @php
        $nativeConfig = [
            'statusUrl' => route('checkout.payment.status', $order->id),
            'payUrl' => route('checkout.payment.pay', $order->id),
            'csrf' => csrf_token(), 'publicKey' => $publicKey,
            'autoStart' => request()->boolean('start'), 'amount' => $order->total,
            'billing' => array_filter(['name' => $order->customer_name, 'email' => $order->customer_email, 'phone' => $order->customer_phone]),
        ];
    @endphp
    <script id="native-payment-config" type="application/json">@json($nativeConfig)</script>
    @vite('resources/js/native-payments.js')
</body>
</html>
