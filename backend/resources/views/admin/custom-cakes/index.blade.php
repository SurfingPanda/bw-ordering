@extends('layouts.site-editor')

@section('title', 'Custom Cakes')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'customCakes'])
@endsection

@section('content')
    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 sm:hidden">{{ session('status') }}</div>
    @endif
    @php
        $statusLabel = ['new' => 'New', 'quoted' => 'Quoted', 'closed' => 'Closed'];
        $statusStyle = ['new' => 'bg-amber-100 text-amber-700', 'quoted' => 'bg-blue-100 text-blue-700', 'closed' => 'bg-slate-100 text-slate-600'];
        $total = array_sum($counts);
    @endphp

    {{-- status filter tabs --}}
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach([null => 'All', 'new' => 'New', 'quoted' => 'Quoted', 'closed' => 'Closed'] as $key => $label)
            @php $on = $filter === ($key === '' ? null : $key); @endphp
            <a href="{{ route('admin.custom-cakes', array_filter(['status' => $key])) }}"
                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $on ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md' : 'bg-white text-navy-700 shadow-sm hover:bg-slate-50' }}">
                {{ $label }}
                <span class="ml-1 text-xs {{ $on ? 'text-white/80' : 'text-slate-400' }}">{{ $key ? ($counts[$key] ?? 0) : $total }}</span>
            </a>
        @endforeach
    </div>

    @if($requests->isEmpty())
        <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
            <p class="text-3xl">🎂</p>
            <p class="mt-2 text-sm text-slate-500">No {{ $filter ? "\"{$statusLabel[$filter]}\"" : '' }} cake requests yet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($requests as $cc)
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-navy-800">Request #{{ $cc->id }}</h2>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusStyle[$cc->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabel[$cc->status] ?? ucfirst($cc->status) }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">Sent {{ $cc->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                        </div>

                        {{-- status control: pick → auto-submits --}}
                        <form method="POST" action="{{ route('admin.custom-cakes.status', $cc) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="filter" value="{{ $filter }}">
                            <label class="text-xs font-medium text-slate-500" for="status-{{ $cc->id }}">Status</label>
                            <select id="status-{{ $cc->id }}" name="status" onchange="this.form.submit()"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                @foreach($statusLabel as $value => $label)
                                    <option value="{{ $value }}" @selected($cc->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @if($cc->delivery_type)
                            <span class="rounded-full bg-navy-50 px-2.5 py-0.5 text-xs font-semibold text-navy-700">{{ $cc->delivery_type === 'pickup' ? '🏪 Pickup' : '🚚 Delivery' }}</span>
                        @endif
                        @if($cc->fulfillment_branch)
                            <span class="rounded-full bg-navy-50 px-2.5 py-0.5 text-xs font-semibold text-navy-700">📍 {{ $cc->fulfillment_branch }}</span>
                        @endif
                        @foreach(array_filter([$cc->occasion, $cc->flavor, $cc->size, $cc->frosting_color]) as $chip)
                            <span class="rounded-full bg-navy-50 px-2.5 py-0.5 text-xs font-semibold text-navy-700">{{ $chip }}</span>
                        @endforeach
                        @if($cc->needed_by)
                            <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600">Needed by {{ $cc->needed_by->format('M j, Y') }}</span>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-[1fr_auto]">
                        <div class="min-w-0 text-sm">
                            <p class="whitespace-pre-line leading-relaxed text-slate-600">{{ $cc->description }}</p>
                            @if($cc->address)
                                <p class="mt-2 text-xs text-slate-600"><span class="font-semibold text-navy-700">📍 Deliver to:</span> {{ $cc->address }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-600">
                                <span><span class="font-semibold text-navy-700">👤</span> {{ $cc->name }}</span>
                                <a href="mailto:{{ $cc->email }}" class="font-medium text-brand-600 hover:underline">✉️ {{ $cc->email }}</a>
                                @if($cc->phone)
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $cc->phone) }}" class="font-medium text-brand-600 hover:underline">📞 {{ $cc->phone }}</a>
                                @endif
                                @if($cc->reference_link)
                                    <a href="{{ $cc->reference_link }}" target="_blank" rel="noopener noreferrer" class="font-medium text-brand-600 hover:underline">🔗 Inspiration link</a>
                                @endif
                            </div>
                        </div>
                        @if($cc->reference_path)
                            <a href="{{ route('admin.custom-cakes.reference', $cc) }}" target="_blank"
                                class="block h-28 w-28 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100 transition hover:ring-2 hover:ring-brand-500/40"
                                title="Open the uploaded design peg">
                                <img src="{{ route('admin.custom-cakes.reference', $cc) }}" alt="Design reference" loading="lazy" class="h-full w-full object-cover">
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
