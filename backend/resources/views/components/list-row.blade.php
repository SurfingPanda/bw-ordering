{{-- List-row variant of x-item-card: a compact summary line (title + meta,
     synced from inputs marked data-summary-title / data-summary-meta) with
     move/Edit/Remove controls; the slot (the fields) lives in a [data-modal]
     popup that stays INSIDE the form so its inputs submit with "Save changes".
     Wiring (open/close, summary sync, discard-empty-new-rows) is the
     "list rows + edit popups" script in admin/content/index.blade.php;
     numbering + move/remove come from the shared repeater JS. --}}
@props(['heading' => 'Edit item', 'emptyLabel' => 'New item', 'hideMove' => false])
<div data-row data-empty-label="{{ $emptyLabel }}" class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
    <div class="flex items-center gap-2">
        <span data-row-number class="text-xs font-semibold uppercase tracking-wide text-slate-400">#</span>
        <div class="min-w-0 flex-1">
            <p data-item-title class="truncate text-sm font-semibold text-navy-800">{{ $emptyLabel }}</p>
            <p data-item-meta class="truncate text-xs text-slate-500"></p>
        </div>
        @unless($hideMove)
            <button type="button" data-move="-1" aria-label="Move up" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↑</button>
            <button type="button" data-move="1" aria-label="Move down" class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 bg-white text-sm text-navy-700 transition hover:border-brand-400 disabled:opacity-40">↓</button>
        @endunless
        <button type="button" data-edit class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">Edit</button>
        <button type="button" data-remove class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
    </div>

    <div data-modal class="hidden">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy-900/50"></div>
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-navy-800">{{ $heading }}</h3>
                    <button type="button" data-modal-close aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-navy-700">✕</button>
                </div>
                <div class="space-y-3">{{ $slot }}</div>
                <button type="button" data-modal-close class="mt-5 w-full rounded-lg bg-navy-800 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-700">Done</button>
            </div>
        </div>
    </div>
</div>
