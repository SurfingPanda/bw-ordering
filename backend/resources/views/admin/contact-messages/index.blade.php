@extends('layouts.site-editor')

@section('title', 'Contact Messages')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'contactMessages'])
@endsection

@section('content')
    @php
        $statusLabel = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'];
        $total = array_sum($counts);
        $tabCounts = [
            '' => $total,
            'new' => $counts['new'] ?? 0,
            'read' => $counts['read'] ?? 0,
            'replied' => $counts['replied'] ?? 0,
        ];
        // shared classes for the secondary "Mark as …" buttons in the detail panel
        $cmActionCls = 'inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-navy-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40';
    @endphp

    @if(session('status'))
        <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700 sm:hidden">{{ session('status') }}</div>
    @endif

    <div data-inbox class="flex h-[calc(100vh-11rem)] min-h-[520px] flex-col">
        {{-- toolbar: compact status filter pills (server-side links, unchanged) + client-side search --}}
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Filter messages by status">
                @foreach(['' => 'All', 'new' => 'New', 'read' => 'Read', 'replied' => 'Replied'] as $key => $label)
                    @php $on = $filter === ($key === '' ? null : $key); @endphp
                    <a href="{{ route('admin.contact-messages', array_filter(['status' => $key])) }}"
                        role="tab" aria-selected="{{ $on ? 'true' : 'false' }}"
                        @class([
                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition',
                            'bg-brand-500 text-white shadow-sm' => $on,
                            'bg-white text-navy-700 ring-1 ring-slate-200 hover:bg-slate-50' => ! $on,
                        ])>
                        {{ $label }}
                        <span @class([
                            'rounded-full px-1.5 py-0.5 text-[11px] font-bold tabular-nums',
                            'bg-white/25 text-white' => $on,
                            'bg-slate-100 text-slate-500' => ! $on,
                        ])>{{ $tabCounts[$key] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="relative w-full sm:w-64">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
                </svg>
                <input data-search type="search" placeholder="Search messages…" aria-label="Search messages"
                    class="w-full rounded-full border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-navy-800 outline-none transition placeholder:text-slate-400 focus:border-brand-400 focus:ring-2 focus:ring-brand-500/15">
            </div>
        </div>

        @if($messages->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />
                    </svg>
                </div>
                <p class="mt-4 text-base font-semibold text-navy-800">No {{ $filter ? '"'.$statusLabel[$filter].'"' : '' }} messages</p>
                <p class="mt-1 text-sm text-slate-500">Messages sent through the contact form will appear here.</p>
            </div>
        @else
            <div class="flex min-h-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {{-- LEFT: message list --}}
                <div data-list-pane class="flex w-full flex-col overflow-y-auto scrollbar-slim md:w-[35%] md:min-w-[300px] md:max-w-[440px] md:border-r md:border-slate-200">
                    @foreach($messages as $msg)
                        @php
                            $dt = $msg->created_at?->timezone('Asia/Manila');
                            $dateShort = $dt
                                ? ($dt->isToday() ? $dt->format('g:i A') : ($dt->isCurrentYear() ? $dt->format('M j') : $dt->format('M j, Y')))
                                : '';
                            $preview = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $msg->message), 90);
                            $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($msg->name ?: ($msg->email ?: '?')), 0, 1));
                            $isNew = $msg->status === 'new';
                        @endphp
                        <button type="button" data-msg-item data-id="{{ $msg->id }}" data-active="false"
                            data-search="{{ \Illuminate\Support\Str::lower(trim(($msg->name ?? '').' '.($msg->email ?? '').' '.($msg->phone ?? '').' '.($msg->subject ?? '').' '.($msg->message ?? ''))) }}"
                            aria-label="Message from {{ $msg->name ?: 'unknown sender' }}{{ $msg->subject ? ': '.$msg->subject : '' }}{{ $isNew ? ' (unread)' : '' }}"
                            class="flex w-full gap-3 border-b border-slate-100 border-l-4 border-l-transparent px-4 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500/40 data-[active=true]:border-l-brand-500 data-[active=true]:bg-brand-50">
                            <span class="relative mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-navy-100 text-sm font-bold text-navy-700">
                                {{ $initial }}
                                @if($isNew)
                                    <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-brand-500" aria-hidden="true"></span>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span @class(['truncate text-sm text-navy-800', 'font-bold' => $isNew, 'font-medium' => ! $isNew])>{{ $msg->name ?: 'Unknown sender' }}</span>
                                    <span class="shrink-0 text-[11px] text-slate-400">{{ $dateShort }}</span>
                                </span>
                                <span class="mt-0.5 block truncate text-[13px] font-semibold text-navy-700">{{ $msg->subject ?: 'No subject' }}</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $preview }}</span>
                            </span>
                        </button>
                    @endforeach
                    <div data-no-results class="hidden px-4 py-10 text-center text-sm text-slate-500">No messages match your search.</div>
                </div>

                {{-- RIGHT: message detail --}}
                <div data-detail-pane class="hidden min-w-0 flex-1 flex-col overflow-y-auto scrollbar-slim md:flex">
                    <div data-detail-empty class="flex flex-1 flex-col items-center justify-center px-6 py-16 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />
                            </svg>
                        </div>
                        <p class="mt-4 text-base font-semibold text-navy-800">Select a message</p>
                        <p class="mt-1 text-sm text-slate-500">Choose a message from the list to view its details.</p>
                    </div>

                    @foreach($messages as $msg)
                        @php
                            $dt = $msg->created_at?->timezone('Asia/Manila');
                            $fullDate = $dt ? $dt->format('M j, Y · g:i A') : '—';
                            $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($msg->name ?: ($msg->email ?: '?')), 0, 1));
                            $mailto = 'mailto:'.$msg->email.'?subject='.rawurlencode('Re: '.($msg->subject ?: 'Your message to BW Superbakeshop'));
                        @endphp
                        <article data-msg-detail data-id="{{ $msg->id }}" tabindex="-1" class="hidden flex-col focus:outline-none">
                            <button type="button" data-back class="flex items-center gap-1 border-b border-slate-100 px-4 py-2.5 text-sm font-semibold text-navy-700 transition hover:bg-slate-50 md:hidden">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 6-6 6 6 6" /></svg>
                                Back to Messages
                            </button>

                            <div class="px-5 py-5 sm:px-7">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <h2 class="text-lg font-bold leading-snug text-navy-800 sm:text-xl">{{ $msg->subject ?: 'No subject' }}</h2>
                                        <div class="mt-3 flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-navy-100 text-sm font-bold text-navy-700">{{ $initial }}</span>
                                            <div class="min-w-0 text-sm">
                                                <p class="font-semibold text-navy-800">{{ $msg->name ?: 'Unknown sender' }}</p>
                                                <p class="truncate text-slate-500">{{ $msg->email }}</p>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-slate-400">Sent {{ $fullDate }}</p>
                                    </div>

                                    {{-- status control — same form/route/behaviour as before --}}
                                    <form method="POST" action="{{ route('admin.contact-messages.status', $msg) }}" id="status-form-{{ $msg->id }}" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="filter" value="{{ $filter }}">
                                        <label for="status-select-{{ $msg->id }}" class="sr-only">Message status</label>
                                        <select id="status-select-{{ $msg->id }}" name="status" onchange="this.form.submit()"
                                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                            @foreach($statusLabel as $value => $label)
                                                <option value="{{ $value }}" @selected($msg->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>

                                {{-- actions — all wired to existing functionality (mailto reply + status route) --}}
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <a href="{{ $mailto }}"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-brand-600 hover:to-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 7 4 12l5 5" /><path d="M4 12h11a5 5 0 0 1 5 5v1" /></svg>
                                        Reply
                                    </a>
                                    @if($msg->status !== 'read')
                                        <button type="button" onclick="cmSetStatus({{ $msg->id }}, 'read')" class="{{ $cmActionCls }}">Mark as read</button>
                                    @endif
                                    @if($msg->status !== 'new')
                                        <button type="button" onclick="cmSetStatus({{ $msg->id }}, 'new')" class="{{ $cmActionCls }}">Mark as unread</button>
                                    @endif
                                    @if($msg->status !== 'replied')
                                        <button type="button" onclick="cmSetStatus({{ $msg->id }}, 'replied')" class="{{ $cmActionCls }}">Mark as replied</button>
                                    @endif
                                </div>

                                {{-- compact contact row --}}
                                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-1.5 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-600">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 4-6 8-6s8 2 8 6" /></svg>
                                        {{ $msg->name ?: 'Unknown sender' }}
                                    </span>
                                    <a href="mailto:{{ $msg->email }}" class="inline-flex items-center gap-1.5 font-medium text-brand-600 hover:underline">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></svg>
                                        {{ $msg->email }}
                                    </a>
                                    @if($msg->phone)
                                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $msg->phone) }}" class="inline-flex items-center gap-1.5 font-medium text-brand-600 hover:underline">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5c0 9 6 15 15 15l1-4-5-2-2 2c-2-1-5-4-6-6l2-2-2-5z" /></svg>
                                            {{ $msg->phone }}
                                        </a>
                                    @endif
                                </div>

                                <hr class="my-5 border-slate-200">

                                <div class="max-w-prose whitespace-pre-line text-[15px] leading-7 text-slate-700">{{ $msg->message }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
<script>
    ;(() => {
        const root = document.querySelector('[data-inbox]')
        if (!root) return
        const listPane = root.querySelector('[data-list-pane]')
        const detailPane = root.querySelector('[data-detail-pane]')
        if (!listPane || !detailPane) return

        const items = Array.from(root.querySelectorAll('[data-msg-item]'))
        const details = Array.from(root.querySelectorAll('[data-msg-detail]'))
        const emptyEl = root.querySelector('[data-detail-empty]')
        const noResults = root.querySelector('[data-no-results]')
        const search = root.querySelector('[data-search]')
        const desktop = () => window.matchMedia('(min-width: 768px)').matches

        function showDetailMobile() {
            listPane.classList.add('hidden')
            detailPane.classList.remove('hidden')
            detailPane.classList.add('flex')
        }
        function showListMobile() {
            detailPane.classList.add('hidden')
            detailPane.classList.remove('flex')
            listPane.classList.remove('hidden')
        }

        function selectMessage(id) {
            let found = false
            details.forEach((d) => {
                const on = d.dataset.id === id
                d.classList.toggle('hidden', !on)
                d.classList.toggle('flex', on)
                if (on) found = true
            })
            if (!found) return
            if (emptyEl) emptyEl.classList.add('hidden')
            items.forEach((it) => { it.dataset.active = String(it.dataset.id === id) })
            root.dataset.selected = id
            if (!desktop()) showDetailMobile()
        }

        items.forEach((it) => {
            it.addEventListener('click', () => selectMessage(it.dataset.id))
        })

        root.querySelectorAll('[data-back]').forEach((b) => {
            b.addEventListener('click', showListMobile)
        })

        // Arrow-key navigation through the (visible) list
        listPane.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return
            const vis = items.filter((it) => !it.classList.contains('hidden'))
            if (!vis.length) return
            e.preventDefault()
            const cur = vis.findIndex((it) => it.dataset.active === 'true')
            let next = e.key === 'ArrowDown' ? cur + 1 : cur - 1
            if (cur === -1) next = 0
            next = Math.max(0, Math.min(vis.length - 1, next))
            vis[next].focus()
            selectMessage(vis[next].dataset.id)
        })

        if (search) {
            search.addEventListener('input', () => {
                const q = search.value.trim().toLowerCase()
                let n = 0
                items.forEach((it) => {
                    const match = !q || (it.dataset.search || '').includes(q)
                    it.classList.toggle('hidden', !match)
                    if (match) n++
                })
                if (noResults) noResults.classList.toggle('hidden', n > 0)
            })
        }

        window.addEventListener('resize', () => {
            if (desktop()) {
                listPane.classList.remove('hidden')
                detailPane.classList.remove('hidden')
                detailPane.classList.remove('flex')
            } else if (root.dataset.selected) {
                showDetailMobile()
            } else {
                showListMobile()
            }
        })
    })()

    // Secondary "Mark as …" buttons reuse the message's existing status <form>.
    window.cmSetStatus = function (id, status) {
        const form = document.getElementById('status-form-' + id)
        if (!form) return
        const sel = form.querySelector('select[name="status"]')
        if (sel) sel.value = status
        form.submit()
    }
</script>
@endsection
