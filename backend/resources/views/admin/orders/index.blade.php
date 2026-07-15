@extends('layouts.site-editor')

@section('title', 'Orders')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'orders'])
@endsection

@section('content')
    @php
        $statusLabel = ['pending' => 'Order Placed', 'preparing' => 'Preparing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
        $statusStyle = ['pending' => 'bg-amber-100 text-amber-700', 'preparing' => 'bg-orange-100 text-orange-700', 'completed' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700'];
        $paymentLabel = ['qrph' => 'QRPH', 'cash' => 'Cash on Pickup', 'paymongo' => 'Online (PayMongo)'];
        $total = array_sum($counts);
    @endphp

    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 sm:hidden">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    {{-- status filter tabs --}}
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach([null => 'All', 'pending' => 'Placed', 'preparing' => 'Preparing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
            @php $on = $filter === ($key === '' ? null : $key); @endphp
            <a href="{{ route('admin.orders', array_filter(['status' => $key])) }}"
                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $on ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md' : 'bg-white text-navy-700 shadow-sm hover:bg-slate-50' }}">
                {{ $label }}
                <span class="ml-1 text-xs {{ $on ? 'text-white/80' : 'text-slate-400' }}">{{ $key ? ($counts[$key] ?? 0) : $total }}</span>
            </a>
        @endforeach
    </div>

    @if($orders->isEmpty())
        <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
            <p class="text-3xl">🧾</p>
            <p class="mt-2 text-sm text-slate-500">No orders here yet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($orders as $order)
                @php $items = is_array($order->items) ? $order->items : []; @endphp
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-navy-800">Order #{{ strtoupper(substr($order->id, 0, 8)) }}</h2>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusStyle[$order->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$order->status] ?? $order->status }}</span>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $order->payment_status === 'paid' ? '✓ Paid' : 'Awaiting payment' }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $order->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs text-slate-600">
                                <span class="rounded-full bg-navy-50 px-2 py-0.5 font-semibold text-navy-700">{{ $order->delivery_type === 'pickup' ? '🏪 Pickup' : '🚚 Delivery' }}</span>
                                @if($order->payment_method)
                                    <span class="rounded-full bg-navy-50 px-2 py-0.5 font-semibold text-navy-700">{{ $paymentLabel[$order->payment_method] ?? $order->payment_method }}</span>
                                @endif
                                @if($order->fulfillment_branch)
                                    <span class="rounded-full bg-navy-50 px-2 py-0.5 font-semibold text-navy-700">📍 {{ $order->fulfillment_branch }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" class="flex items-center gap-1.5">
                                @csrf
                                <input type="hidden" name="filter" value="{{ $filter }}">
                                <label class="text-xs font-medium text-slate-500" for="status-{{ $order->id }}">Status</label>
                                <select id="status-{{ $order->id }}" name="status" onchange="this.form.submit()"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                    @foreach($statusLabel as $value => $label)
                                        <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form method="POST" action="{{ route('admin.orders.payment', $order->id) }}" class="flex items-center gap-1.5">
                                @csrf
                                <input type="hidden" name="filter" value="{{ $filter }}">
                                <label class="text-xs font-medium text-slate-500" for="pay-{{ $order->id }}">Payment</label>
                                <select id="pay-{{ $order->id }}" name="payment_status" onchange="this.form.submit()"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                    <option value="pending" @selected($order->payment_status !== 'paid')>Pending</option>
                                    <option value="paid" @selected($order->payment_status === 'paid')>Paid</option>
                                </select>
                            </form>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-[1fr_auto]">
                        <div class="min-w-0 text-sm">
                            <ul class="space-y-0.5 text-xs text-slate-600">
                                @foreach($items as $i)
                                    <li><span class="font-semibold text-navy-700">{{ $i['qty'] ?? 0 }}×</span> {{ $i['name'] ?? '' }} <span class="text-slate-400">— ₱{{ number_format(($i['price'] ?? 0) * ($i['qty'] ?? 0), 2) }}</span></li>
                                @endforeach
                            </ul>
                            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-600">
                                @if($order->customer_name)<span>👤 {{ $order->customer_name }}</span>@endif
                                @if($order->customer_email)<a href="mailto:{{ $order->customer_email }}" class="font-medium text-brand-600 hover:underline">✉️ {{ $order->customer_email }}</a>@endif
                                @if($order->phone ?? null)<span>📞 {{ $order->phone }}</span>@endif
                            </div>
                            @if($order->address)<p class="mt-1 text-xs text-slate-600"><span class="font-semibold text-navy-700">📍 Deliver to:</span> {{ $order->address }}</p>@endif
                            @if($order->notes)<p class="mt-1 text-xs text-slate-600"><span class="font-semibold text-navy-700">📝 Notes:</span> {{ $order->notes }}</p>@endif
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-xs text-slate-400">Total</p>
                            <p class="text-xl font-bold text-brand-600">₱{{ number_format($order->total, 2) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
