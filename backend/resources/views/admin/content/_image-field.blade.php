{{-- ImageField port from AdminContent.jsx: thumbnail preview + an Upload /
     Use link toggle — upload posts to /admin/uploads and fills the URL in;
     link shows the URL input directly. The URL input keeps the name either
     way, so the value always submits. Vars: $name (input name), $value,
     $fieldLabel, $wide (optional). Wiring: admin/content/_form-scripts. --}}
<div data-image-field>
    <span class="mb-1 block text-xs font-medium text-slate-500">{{ $fieldLabel }}</span>
    <div class="flex items-start gap-3">
        <div class="shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 {{ !empty($wide) ? 'h-16 w-32' : 'h-16 w-16' }}">
            <img data-image-preview src="{{ $value ?: '' }}" alt="" loading="lazy" decoding="async"
                class="h-full w-full object-cover {{ $value ? '' : 'hidden' }}">
            <div data-image-empty class="h-full w-full items-center justify-center text-[0.6rem] text-slate-400 {{ $value ? 'hidden' : 'flex' }}">
                no image
            </div>
        </div>
        <div class="min-w-0 flex-1">
            <div class="mb-1.5 flex items-center gap-2">
                <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 text-xs font-semibold">
                    <button type="button" data-image-mode="upload" class="bg-navy-800 px-3 py-1 text-white transition">Upload</button>
                    <button type="button" data-image-mode="link" class="bg-white px-3 py-1 text-navy-700 transition hover:bg-slate-50">Use link</button>
                </div>
                {{-- clears the URL input (hidden while there's no image) --}}
                <button type="button" data-image-remove class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50 {{ $value ? '' : 'hidden' }}">Remove</button>
            </div>
            <div data-image-pane="upload">
                <button type="button" data-image-upload
                    class="rounded-md bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-600 disabled:opacity-60">
                    Choose image…
                </button>
                <input type="file" accept="image/*" data-image-file class="hidden">
            </div>
            <div data-image-pane="link" class="hidden">
                <input type="text" name="{{ $name }}" value="{{ $value ?? '' }}" placeholder="Paste image URL" data-image-url
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            </div>
            <p data-image-error class="mt-1.5 hidden text-xs text-red-600"></p>
        </div>
    </div>
</div>
