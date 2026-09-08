@extends('layouts.site-editor')

@section('title', 'Audit Log')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'audit'])
@endsection

@section('content')
    @php
        $fmt = fn ($d) => $d
            ? \Illuminate\Support\Carbon::parse($d)->timezone('Asia/Manila')->format('M j, Y · g:i A')
            : '—';

        // A colour per action family so the table scans quickly.
        $actionStyle = function (string $action) {
            return match (true) {
                str_starts_with($action, 'content.') => 'bg-blue-100 text-blue-700',
                str_starts_with($action, 'product.') => 'bg-purple-100 text-purple-700',
                str_starts_with($action, 'store.') => 'bg-teal-100 text-teal-700',
                str_starts_with($action, 'voucher.') => 'bg-amber-100 text-amber-700',
                str_starts_with($action, 'user.') => 'bg-brand-100 text-brand-600',
                str_starts_with($action, 'order.') => 'bg-orange-100 text-orange-700',
                str_starts_with($action, 'custom_cake.') => 'bg-pink-100 text-pink-700',
                str_starts_with($action, 'contact_message.') => 'bg-green-100 text-green-700',
                default => 'bg-slate-100 text-slate-600',
            };
        };
        $roleStyle = [
            'admin' => 'bg-brand-100 text-brand-600',
            'editor' => 'bg-blue-100 text-blue-700',
            'cashier' => 'bg-green-100 text-green-700',
        ];

        $hasFilters = $filters['action'] !== '' || $filters['actor'] !== '' || $filters['q'] !== '';
    @endphp

    {{-- filters --}}
    <form method="GET" action="{{ route('admin.audit-log') }}" class="mb-5 flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-500">
            Search
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Summary, target, or name"
                class="w-64 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-normal text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
        </label>

        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-500">
            Action
            <select name="action" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-normal text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                <option value="">All actions</option>
                @foreach($actionOptions as $opt)
                    <option value="{{ $opt }}" @selected($filters['action'] === $opt)>{{ \App\Models\AuditLog::ACTIONS[$opt] ?? $opt }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-500">
            Person
            <select name="actor" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-normal text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                <option value="">Anyone</option>
                @foreach($actorOptions as $opt)
                    <option value="{{ $opt }}" @selected($filters['actor'] === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>

        <button type="submit" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
            Apply
        </button>
        @if($hasFilters)
            <a href="{{ route('admin.audit-log') }}" class="rounded-full border border-slate-300 px-5 py-2 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">
                Clear
            </a>
        @endif
    </form>

    @if($logs->isEmpty())
        <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm">
            <p class="text-3xl">🕓</p>
            <p class="mt-2 text-sm text-slate-500">
                {{ $hasFilters ? 'No entries match these filters.' : 'No staff actions have been recorded yet.' }}
            </p>
        </div>
    @else
        <p class="mb-3 text-xs text-slate-400">{{ $logs->total() }} {{ \Illuminate\Support\Str::plural('entry', $logs->total()) }}</p>

        {{-- desktop table --}}
        <div class="hidden overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/70 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">When</th>
                        <th class="px-4 py-3 font-semibold">Person</th>
                        <th class="px-4 py-3 font-semibold">Action</th>
                        <th class="px-4 py-3 font-semibold">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($logs as $log)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ $fmt($log->created_at) }}</td>
                            <td class="px-4 py-3">
                                <span class="block font-semibold text-navy-800">{{ $log->actor_name ?: '—' }}</span>
                                <span class="block text-xs text-slate-400">{{ $log->actor_email ?: 'unknown' }}</span>
                                @if($log->actor_role)
                                    <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[0.65rem] font-bold {{ $roleStyle[$log->actor_role] ?? 'bg-slate-100 text-slate-500' }}">
                                        {{ ucfirst($log->actor_role) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $actionStyle($log->action) }}">
                                    {{ $log->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if($log->target)
                                    <span class="block font-medium text-navy-700">{{ $log->target }}</span>
                                @endif
                                @if($log->summary)
                                    <span class="block text-slate-500">{{ $log->summary }}</span>
                                @endif
                                @if($log->meta)
                                    <span class="mt-1 block text-xs text-slate-400">
                                        @foreach($log->meta as $k => $v)
                                            <span class="mr-2 whitespace-nowrap">{{ $k }}: <span class="font-semibold text-slate-500">{{ is_scalar($v) ? $v : json_encode($v) }}</span></span>
                                        @endforeach
                                    </span>
                                @endif
                                @unless($log->target || $log->summary || $log->meta)
                                    <span class="text-slate-300">—</span>
                                @endunless
                                @if($log->ip_address)
                                    <span class="mt-1 block text-[0.65rem] text-slate-300">IP {{ $log->ip_address }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- mobile cards --}}
        <div class="space-y-3 md:hidden">
            @foreach($logs as $log)
                <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $actionStyle($log->action) }}">{{ $log->label() }}</span>
                        <span class="text-xs text-slate-400">{{ $fmt($log->created_at) }}</span>
                    </div>
                    @if($log->target)
                        <p class="mt-2 text-sm font-medium text-navy-700">{{ $log->target }}</p>
                    @endif
                    @if($log->summary)
                        <p class="text-sm text-slate-500">{{ $log->summary }}</p>
                    @endif
                    <p class="mt-2 text-xs text-slate-400">
                        {{ $log->actor_name ?: 'Unknown' }} · {{ $log->actor_email ?: '—' }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- pager --}}
        @if($logs->hasPages())
            <div class="mt-5 flex items-center justify-between text-sm">
                @if($logs->onFirstPage())
                    <span class="rounded-full border border-slate-200 px-4 py-2 text-slate-300">← Newer</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="rounded-full border border-slate-300 px-4 py-2 font-semibold text-navy-700 transition hover:bg-slate-50">← Newer</a>
                @endif

                <span class="text-xs text-slate-400">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>

                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="rounded-full border border-slate-300 px-4 py-2 font-semibold text-navy-700 transition hover:bg-slate-50">Older →</a>
                @else
                    <span class="rounded-full border border-slate-200 px-4 py-2 text-slate-300">Older →</span>
                @endif
            </div>
        @endif
    @endif
@endsection
