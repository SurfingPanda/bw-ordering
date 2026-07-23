@extends('layouts.site-editor')

@section('title', 'Contact Messages')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'contactMessages'])
@endsection

@section('content')
    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 sm:hidden">{{ session('status') }}</div>
    @endif
    @php
        $statusLabel = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'];
        $statusStyle = ['new' => 'bg-amber-100 text-amber-700', 'read' => 'bg-blue-100 text-blue-700', 'replied' => 'bg-emerald-100 text-emerald-700'];
        $total = array_sum($counts);
    @endphp

    {{-- status filter tabs --}}
    <div class="mb-5 flex flex-wrap gap-2">
        @foreach([null => 'All', 'new' => 'New', 'read' => 'Read', 'replied' => 'Replied'] as $key => $label)
            @php $on = $filter === ($key === '' ? null : $key); @endphp
            <a href="{{ route('admin.contact-messages', array_filter(['status' => $key])) }}"
                class="rounded-full px-4 py-2 text-sm font-semibold transition {{ $on ? 'bg-gradient-to-r from-brand-500 to-brand-600 text-white shadow-md' : 'bg-white text-navy-700 shadow-sm hover:bg-slate-50' }}">
                {{ $label }}
                <span class="ml-1 text-xs {{ $on ? 'text-white/80' : 'text-slate-400' }}">{{ $key ? ($counts[$key] ?? 0) : $total }}</span>
            </a>
        @endforeach
    </div>

    @if($messages->isEmpty())
        <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
            <p class="text-3xl">✉️</p>
            <p class="mt-2 text-sm text-slate-500">No {{ $filter ? "\"{$statusLabel[$filter]}\"" : '' }} messages yet.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($messages as $msg)
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold text-navy-800">{{ $msg->subject ?: 'Message #'.$msg->id }}</h2>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusStyle[$msg->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $statusLabel[$msg->status] ?? ucfirst($msg->status) }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">Sent {{ $msg->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                        </div>

                        {{-- status control: pick → auto-submits --}}
                        <form method="POST" action="{{ route('admin.contact-messages.status', $msg) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="filter" value="{{ $filter }}">
                            <label class="text-xs font-medium text-slate-500" for="status-{{ $msg->id }}">Status</label>
                            <select id="status-{{ $msg->id }}" name="status" onchange="this.form.submit()"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                @foreach($statusLabel as $value => $label)
                                    <option value="{{ $value }}" @selected($msg->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    <div class="mt-4 border-t border-slate-100 pt-4 text-sm">
                        <p class="whitespace-pre-line leading-relaxed text-slate-600">{{ $msg->message }}</p>
                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-600">
                            <span><span class="font-semibold text-navy-700">👤</span> {{ $msg->name }}</span>
                            <a href="mailto:{{ $msg->email }}" class="font-medium text-brand-600 hover:underline">✉️ {{ $msg->email }}</a>
                            @if($msg->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $msg->phone) }}" class="font-medium text-brand-600 hover:underline">📞 {{ $msg->phone }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
