@extends('layouts.site-editor')

@section('title', 'Site Ratings')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'ratings'])
@endsection

@section('content')
    @php
        $fmt = fn ($date) => $date ? $date->timezone('Asia/Manila')->format('M j, Y - g:i A') : '-';
    @endphp

    @if(session('status'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    <section class="mb-6 overflow-hidden rounded-2xl bg-navy-900 px-5 py-6 text-white shadow-lg sm:px-7">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-400">Customer feedback</p>
                <h2 class="mt-1 text-xl font-bold">Understand every visit at a glance</h2>
                <p class="mt-1 max-w-xl text-sm text-navy-50/70">Control the landing-page prompt and track the ratings your customers share.</p>
            </div>
            <div class="flex w-fit items-center gap-3 rounded-xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-500 text-lg" aria-hidden="true">&#9733;</span>
                <span><span class="block text-lg font-bold">{{ $total }}</span><span class="block text-xs text-navy-50/70">{{ \Illuminate\Support\Str::plural('rating', $total) }} received</span></span>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div><h2 class="text-base font-bold text-navy-800">Rating overview</h2><p class="mt-1 text-sm text-slate-500">A breakdown of feedback from the landing-page prompt.</p></div>
                <span class="rounded-full {{ $total ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-3 py-1.5 text-xs font-bold">{{ $total ? 'Collecting feedback' : 'Waiting for feedback' }}</span>
            </div>
            @if($total)
                <div class="mt-5 grid gap-5 sm:grid-cols-3 sm:items-center">
                    <div class="rounded-2xl bg-brand-50 p-5 text-center sm:col-span-1"><p class="text-4xl font-bold text-navy-800">{{ number_format($average, 1) }}</p><p class="mt-1 text-sm tracking-wide text-brand-500">&#9733;&#9733;&#9733;&#9733;&#9733;</p><p class="mt-1 text-xs font-medium text-slate-500">Average out of 5</p></div>
                    <div class="space-y-2.5 sm:col-span-2">
                        @for($star = 5; $star >= 1; $star--)
                            @php($count = (int) ($breakdown[$star] ?? 0))
                            <div class="flex items-center gap-3 text-sm"><span class="w-10 text-right font-semibold text-navy-700">{{ $star }} <span class="text-brand-500">&#9733;</span></span><div class="h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ round($count / $total * 100) }}%"></div></div><span class="w-12 text-right text-xs text-slate-500">{{ $count }} <span class="hidden sm:inline">ratings</span></span></div>
                        @endfor
                    </div>
                </div>
            @else
                <div class="flex min-h-48 flex-col items-center justify-center px-5 py-8 text-center"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-xl text-brand-500" aria-hidden="true">&#9733;</span><p class="mt-3 text-sm font-bold text-navy-800">Your feedback dashboard is ready</p><p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">Ratings will appear here after visitors respond to the popup on your landing page.</p></div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-1">
            <div class="flex items-start justify-between gap-3"><div><h2 class="text-base font-bold text-navy-800">Prompt settings</h2><p class="mt-1 text-sm text-slate-500">Landing page only</p></div><span class="rounded-full {{ $settings['enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-2.5 py-1 text-xs font-bold">{{ $settings['enabled'] ? 'Live' : 'Paused' }}</span></div>
            <form method="POST" action="{{ route('admin.ratings.settings') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3.5 transition hover:border-brand-100">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" @checked($settings['enabled']) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500">
                    <span><span class="block text-sm font-semibold text-navy-800">Show rating popup</span><span class="mt-0.5 block text-xs leading-5 text-slate-500">Shown at most once for each browser.</span></span>
                </label>
                <label class="block text-sm font-semibold text-navy-700">Delay before showing
                    <span class="mt-2 flex items-center gap-2"><input type="number" name="delaySeconds" min="0" max="60" value="{{ old('delaySeconds', $settings['delaySeconds']) }}" required class="w-20 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-navy-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"><span class="text-sm font-normal text-slate-500">seconds after opening</span></span>
                    @error('delaySeconds')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">Save changes</button>
            </form>
        </section>
    </div>

    <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:px-6"><div><h2 class="text-base font-bold text-navy-800">Recent ratings</h2><p class="mt-0.5 text-sm text-slate-500">Newest submissions first.</p></div><span class="text-xs font-medium text-slate-400">Last 25 shown</span></div>
        @if($ratings->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-lg text-slate-400" aria-hidden="true">&#9733;</span><p class="mt-3 text-sm font-semibold text-navy-800">No ratings yet</p><p class="mt-1 text-sm text-slate-500">Keep the prompt live to start collecting feedback.</p></div>
        @else
            <div class="overflow-x-auto"><table class="w-full min-w-[560px] text-left text-sm"><thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 sm:px-6">Rating</th><th class="px-5 py-3">Visitor</th><th class="px-5 py-3">Page</th><th class="px-5 py-3 sm:px-6">Submitted</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($ratings as $rating)<tr class="transition hover:bg-slate-50"><td class="px-5 py-4 font-bold text-brand-500 sm:px-6">{{ str_repeat('★', $rating->rating) }}<span class="ml-1 text-xs font-medium text-slate-400">{{ $rating->rating }}/5</span></td><td class="px-5 py-4 text-navy-700">{{ $rating->user_id ? 'Signed-in visitor' : 'Guest visitor' }}</td><td class="px-5 py-4 text-slate-500">{{ $rating->page }}</td><td class="px-5 py-4 text-slate-500 sm:px-6">{{ $fmt($rating->created_at) }}</td></tr>@endforeach</tbody></table></div>
            @if($ratings->hasPages())<div class="border-t border-slate-100 px-5 py-3 sm:px-6">{{ $ratings->links() }}</div>@endif
        @endif
    </section>
@endsection
