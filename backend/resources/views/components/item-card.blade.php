{{-- ItemCard port from AdminContent.jsx: numbered repeater card with move
     up/down + Remove controls (`hide-move` drops the arrows, e.g. products,
     which the server orders by category). The row number and the moves'
     disabled states are maintained by the shared repeater JS
     (admin/content/_form-scripts.blade.php). --}}
@props(['hideMove' => false])
<div data-row class="flex flex-col rounded-xl border border-slate-200 bg-slate-50/60 p-4">
    <div class="mb-3 flex items-center justify-between">
        <span data-row-number class="text-xs font-semibold uppercase tracking-wide text-slate-400">#</span>
        <div class="flex items-center gap-1">
            @unless($hideMove)
                <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
                <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
            @endunless
            <button type="button" data-remove class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
        </div>
    </div>
    <div class="space-y-3">{{ $slot }}</div>
</div>
