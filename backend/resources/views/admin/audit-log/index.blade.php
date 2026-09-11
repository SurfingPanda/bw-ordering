@extends('layouts.site-editor')

@section('title', 'Audit Log')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'audit'])
@endsection

@section('content')
    @php
        $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->timezone('Asia/Manila')->format('M j, Y \u00b7 g:i A') : '-';
        $actionStyle = fn (string $action) => match (true) {
            str_starts_with($action, 'content.') => 'bg-blue-100 text-blue-700',
            str_starts_with($action, 'asset.') => 'bg-cyan-100 text-cyan-700',
            str_starts_with($action, 'product.') => 'bg-purple-100 text-purple-700',
            str_starts_with($action, 'store.') => 'bg-teal-100 text-teal-700',
            str_starts_with($action, 'voucher.') => 'bg-amber-100 text-amber-700',
            str_starts_with($action, 'user.') => 'bg-brand-100 text-brand-600',
            str_starts_with($action, 'order.') => 'bg-orange-100 text-orange-700',
            str_starts_with($action, 'custom_cake.') => 'bg-pink-100 text-pink-700',
            str_starts_with($action, 'contact_message.') => 'bg-green-100 text-green-700',
            default => 'bg-slate-100 text-slate-600',
        };
        $roleStyle = ['admin' => 'bg-brand-100 text-brand-600', 'editor' => 'bg-blue-100 text-blue-700', 'cashier' => 'bg-green-100 text-green-700'];
        $hasFilters = $filters['action'] !== '' || $filters['actor'] !== '' || $filters['q'] !== '';
    @endphp

    <div class="mb-6 rounded-2xl border border-brand-100 bg-gradient-to-r from-brand-50 to-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div><p class="text-sm font-bold text-navy-800">Website activity</p><p class="mt-1 text-sm text-slate-500">See what was changed, who did it, and any images added to the website.</p></div>
            @if(! $logs->isEmpty())<span class="inline-flex w-fit rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-navy-700 shadow-sm ring-1 ring-brand-100">{{ $logs->total() }} {{ \Illuminate\Support\Str::plural('record', $logs->total()) }}</span>@endif
        </div>
    </div>

    <form method="GET" action="{{ route('admin.audit-log') }}" class="mb-5 flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-500">Search<input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Change, file, section, or person" class="w-64 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-normal text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"></label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-500">Activity<select name="action" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-normal text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"><option value="">All activity</option>@foreach($actionOptions as $opt)<option value="{{ $opt }}" @selected($filters['action'] === $opt)>{{ \App\Models\AuditLog::ACTIONS[$opt] ?? $opt }}</option>@endforeach</select></label>
        <label class="flex flex-col gap-1 text-xs font-semibold text-slate-500">Person<select name="actor" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-normal text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"><option value="">Anyone</option>@foreach($actorOptions as $opt)<option value="{{ $opt }}" @selected($filters['actor'] === $opt)>{{ $opt }}</option>@endforeach</select></label>
        <button type="submit" class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-brand-500/30">Apply</button>
        @if($hasFilters)<a href="{{ route('admin.audit-log') }}" class="rounded-full border border-slate-300 px-5 py-2 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">Clear</a>@endif
    </form>

    @if($logs->isEmpty())
        <div class="rounded-2xl border border-slate-100 bg-white p-10 text-center shadow-sm"><p class="text-sm text-slate-500">{{ $hasFilters ? 'No activity matches these filters.' : 'No staff activity has been recorded yet.' }}</p></div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
            @foreach($logs as $log)
                <article class="border-b border-slate-100 p-4 last:border-b-0 sm:p-5">
                    <div class="flex gap-3">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $actionStyle($log->action) }}"><x-admin-icon :name="str_starts_with($log->action, 'asset.') ? 'image' : (str_starts_with($log->action, 'content.') ? 'edit' : 'clock')" class="h-4 w-4" /></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div><span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $actionStyle($log->action) }}">{{ $log->label() }}</span>@if($log->target)<h2 class="mt-2 break-words text-sm font-bold text-navy-800">{{ $log->target }}</h2>@endif @if($log->summary)<p class="mt-0.5 text-sm text-slate-600">{{ $log->summary }}</p>@endif</div>
                                <time class="shrink-0 text-xs text-slate-400">{{ $fmt($log->created_at) }}</time>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500"><span class="font-semibold text-navy-700">{{ $log->actor_name ?: 'Unknown staff member' }}</span><span>{{ $log->actor_email ?: 'No email recorded' }}</span>@if($log->actor_role)<span class="rounded-full px-2 py-0.5 font-bold {{ $roleStyle[$log->actor_role] ?? 'bg-slate-100 text-slate-500' }}">{{ ucfirst($log->actor_role) }}</span>@endif</div>
                            @if($log->meta)
                                <details class="group mt-3 rounded-xl bg-slate-50 px-3 py-2.5"><summary class="cursor-pointer list-none text-xs font-semibold text-brand-600"><span class="group-open:hidden">View change details</span><span class="hidden group-open:inline">Hide change details</span></summary><dl class="mt-2 space-y-2 border-t border-slate-200 pt-2 text-xs">
                                    @foreach($log->meta as $k => $v)
                                        @php($parts = is_scalar($v) ? preg_split('/\s+\x{00B7}\s+/u', (string) $v) : [json_encode($v)])
                                        <div class="grid gap-0.5 sm:grid-cols-[10rem_1fr] sm:gap-3"><dt class="font-semibold text-slate-500">{{ $k }}</dt><dd class="text-slate-700">@if(count($parts) > 1)<ul class="space-y-1">@foreach($parts as $part)<li>{{ $part }}</li>@endforeach</ul>@else{{ $parts[0] }}@endif</dd></div>
                                    @endforeach
                                    @if($log->ip_address)<div class="grid gap-0.5 sm:grid-cols-[10rem_1fr] sm:gap-3"><dt class="font-semibold text-slate-500">IP address</dt><dd class="text-slate-500">{{ $log->ip_address }}</dd></div>@endif
                                </dl></details>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        @if($logs->hasPages())
            <div class="mt-5 flex items-center justify-between text-sm">
                @if($logs->onFirstPage())<span class="rounded-full border border-slate-200 px-4 py-2 text-slate-300">&larr; Newer</span>@else<a href="{{ $logs->previousPageUrl() }}" class="rounded-full border border-slate-300 px-4 py-2 font-semibold text-navy-700 transition hover:bg-slate-50">&larr; Newer</a>@endif
                <span class="text-xs text-slate-400">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
                @if($logs->hasMorePages())<a href="{{ $logs->nextPageUrl() }}" class="rounded-full border border-slate-300 px-4 py-2 font-semibold text-navy-700 transition hover:bg-slate-50">Older &rarr;</a>@else<span class="rounded-full border border-slate-200 px-4 py-2 text-slate-300">Older &rarr;</span>@endif
            </div>
        @endif
    @endif
@endsection
