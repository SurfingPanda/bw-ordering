@extends('layouts.site-editor')

@section('title', 'Find a Store')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'stores'])
@endsection

@section('preview-label', 'Live preview — saved content (updates on save)')
@section('preview')
    @include('admin.content._preview', ['url' => route('stores', absolute: false)])
@endsection

@section('header-actions')
    @include('admin.content._save-reset', ['formId' => 'stores-form'])
@endsection

@section('content')
@php($input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20')

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="stores-form" method="POST" action="{{ route('admin.stores.sync') }}">
        @csrf
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-navy-800">Find a Store</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">Branches shown on the “Find a Store” page and map. To place the map pin, paste the branch's Google Maps embed (Share → Embed a map) into “Map location” — the latitude/longitude fill in automatically. Click “Save changes” to publish.</p>

            {{-- The ids loaded into the editor: anything here that's missing
                 from the submitted cards was removed → deleted on save. --}}
            @foreach($stores as $store)
                <input type="hidden" name="originalIds[]" value="{{ $store->id }}">
            @endforeach

            {{-- Client-side filter over the list rows (no name → not submitted). --}}
            <input type="search" id="store-search" placeholder="Search by name, region, or address…" autocomplete="off" class="{{ $input }} mb-4">

            <div data-repeater>
                <div data-rows class="space-y-2">
                    @foreach($stores as $i => $store)
                        @include('admin.stores._store-row', ['i' => $i, 'store' => $store->toArray()])
                    @endforeach
                </div>
                <template>@include('admin.stores._store-row', ['i' => '__IDX__', 'store' => []])</template>
                <button type="button" data-add class="mt-4 w-full rounded-xl border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">
                    + Add store
                </button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    @include('admin.content._form-scripts')
    <script>
        // ---- store list rows + edit popup ---------------------------------
        // Each [data-row] is a compact summary line; its full field set lives
        // in a [data-modal] popup inside the same row (still inside the form,
        // so Save changes submits every store, open or not).
        const storesForm = document.getElementById('stores-form')

        function fieldValue(row, key) {
            const el = row.querySelector(`[name$="[${key}]"]`)
            return el ? el.value.trim() : ''
        }

        function syncSummary(row) {
            row.querySelector('[data-store-name]').textContent = fieldValue(row, 'name') || 'New store'
            row.querySelector('[data-store-meta]').textContent =
                [fieldValue(row, 'region'), fieldValue(row, 'address')].filter(Boolean).join(' · ')
        }

        function openModal(row) {
            row.querySelector('[data-modal]').classList.remove('hidden')
            document.body.classList.add('overflow-hidden')
        }

        function closeModal(modal) {
            modal.classList.add('hidden')
            document.body.classList.remove('overflow-hidden')
            // Closing a brand-new row that was left completely empty discards
            // it — otherwise every "+ Add store" + close leaves a blank
            // "New store" behind. Existing stores (with an id) are kept.
            const row = modal.closest('[data-row]')
            const isNew = !row.querySelector('[name$="[id]"]').value
            const empty = ['name', 'address', 'hours', 'phone', 'latitude', 'longitude']
                .every((k) => fieldValue(row, k) === '')
            if (isNew && empty) row.querySelector('[data-remove]').click()
        }

        // Registered after _form-scripts' click handler, so by the time the
        // [data-add] branch runs the new row already exists — open its popup.
        document.addEventListener('click', (e) => {
            const edit = e.target.closest('[data-edit]')
            if (edit) { openModal(edit.closest('[data-row]')); return }
            const close = e.target.closest('[data-modal-close]')
            if (close) { closeModal(close.closest('[data-modal]')); return }
            if (e.target.closest('#stores-form [data-add]')) {
                search.value = '' // an active filter would hide the new row
                applyFilter()
                const rows = storesForm.querySelectorAll('[data-row]')
                if (rows.length) openModal(rows[rows.length - 1])
            }
        })

        // ---- search filter -------------------------------------------------
        // Hides non-matching rows only — hidden rows still submit, so
        // filtering never affects what Save changes writes.
        const search = document.getElementById('store-search')

        function applyFilter() {
            const q = search.value.trim().toLowerCase()
            storesForm.querySelectorAll('[data-row]').forEach((row) => {
                const text = (row.querySelector('[data-store-name]').textContent + ' '
                    + row.querySelector('[data-store-meta]').textContent).toLowerCase()
                row.classList.toggle('hidden', q !== '' && !text.includes(q))
            })
        }

        search.addEventListener('input', applyFilter)
        search.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') e.preventDefault() // don't submit the form
        })

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return
            storesForm.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeModal)
        })

        // ---- map embed → latitude/longitude --------------------------------
        // The "Map location" box takes a Google Maps embed (iframe/link) and
        // extracts the coordinates into the real latitude/longitude inputs —
        // the pasted code itself has no name attribute and is never saved.
        function parseEmbed(box) {
            const row = box.closest('[data-row]')
            const status = row.querySelector('[data-map-embed-status]')
            const text = box.value.trim()
            if (!text) { status.classList.add('hidden'); return }
            // Embed URLs carry "!2d<lng>!3d<lat>"; share/place links carry "@<lat>,<lng>".
            const lng = text.match(/!2d(-?\d+(?:\.\d+)?)/)
            const lat = text.match(/!3d(-?\d+(?:\.\d+)?)/)
            const at = text.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/)
            const pin = lat && lng ? [lat[1], lng[1]] : at ? [at[1], at[2]] : null
            status.classList.remove('hidden')
            if (pin) {
                row.querySelector('[name$="[latitude]"]').value = (+pin[0]).toFixed(6)
                row.querySelector('[name$="[longitude]"]').value = (+pin[1]).toFixed(6)
                status.textContent = `Pin set: ${(+pin[0]).toFixed(6)}, ${(+pin[1]).toFixed(6)}`
                status.classList.remove('text-red-600')
                status.classList.add('text-emerald-600')
            } else {
                status.textContent = 'No coordinates found — paste the full embed code or map link.'
                status.classList.remove('text-emerald-600')
                status.classList.add('text-red-600')
            }
        }

        // Keep the summary line in sync while editing in the popup.
        storesForm.addEventListener('input', (e) => {
            if (e.target.matches('[data-map-embed]')) parseEmbed(e.target)
            const row = e.target.closest('[data-row]')
            if (row) syncSummary(row)
        })
        storesForm.addEventListener('change', (e) => {
            const row = e.target.closest('[data-row]')
            if (row) syncSummary(row)
        })
    </script>
@endsection
