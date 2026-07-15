@extends('layouts.site-editor')

@section('title', 'Products')

@section('editor-nav')
    @include('admin.content._editor-nav', ['activeSection' => 'products'])
@endsection

@section('preview-label', 'Live preview — saved content (updates on save)')
@section('preview')
    @include('admin.content._preview', ['url' => route('menu', absolute: false)])
@endsection

@section('header-actions')
    {{-- Hidden until a card is actually edited/added/removed. --}}
    @include('admin.content._save-reset', ['formId' => 'products-form'])
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

    <form id="products-form" method="POST" action="{{ route('admin.products.sync') }}">
        @csrf
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-navy-800">Menu Products</h2>
            <p class="mb-5 mt-0.5 text-sm text-slate-500">The shared product catalogue shown on /menu and used for order pricing. Removing a product archives it (soft-delete).</p>

            <datalist id="bw-categories">
                @foreach($categoryOptions as $c)
                    <option value="{{ $c }}"></option>
                @endforeach
            </datalist>

            {{-- The ids loaded into the editor: anything here that's missing
                 from the submitted cards was removed → archived on save. --}}
            @foreach($products as $product)
                <input type="hidden" name="originalIds[]" value="{{ $product->id }}">
            @endforeach

            {{-- Search the catalogue (it can get long). Filters cards client-side. --}}
            <div class="relative mb-4 max-w-md">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                {{-- data-no-dirty: searching is not an edit, so it must not
                     surface the Save/Reset buttons. --}}
                <input type="search" id="product-search" data-no-dirty placeholder="Search products by name or category…"
                    class="w-full rounded-full border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
            </div>
            <p id="product-search-count" class="mb-3 hidden text-xs text-slate-500"></p>
            <p id="products-empty" class="hidden py-8 text-center text-sm text-slate-500"></p>

            <div data-repeater>
                <div data-rows class="space-y-2">
                    @foreach($products as $i => $product)
                        @include('admin.products._product-row', ['i' => $i, 'product' => $product->toArray()])
                    @endforeach
                </div>
                <template>@include('admin.products._product-row', ['i' => '__IDX__', 'product' => []])</template>
                <button type="button" data-add class="mt-4 w-full rounded-xl border-2 border-dashed border-slate-300 py-3 text-sm font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-600">
                    + Add product
                </button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    @include('admin.content._form-scripts')
    <script>
        // ---- product list rows + edit popup --------------------------------
        // Each [data-row] is a compact summary line (thumbnail + name +
        // category · price); its full field set lives in a [data-modal] popup
        // inside the same row (still inside the form, so Save changes submits
        // every product, open or not). Same pattern as the Stores editor.
        const productsForm = document.getElementById('products-form')

        function fieldValue(row, key) {
            const el = row.querySelector(`[name$="[${key}]"]`)
            return el ? el.value.trim() : ''
        }

        function syncSummary(row) {
            row.querySelector('[data-product-name]').textContent = fieldValue(row, 'name') || 'New product'
            const price = fieldValue(row, 'price')
            row.querySelector('[data-product-meta]').textContent =
                [fieldValue(row, 'category'), price ? '₱' + price : ''].filter(Boolean).join(' · ')
            const thumb = row.querySelector('[data-product-thumb]')
            const empty = row.querySelector('[data-product-thumb-empty]')
            const image = fieldValue(row, 'image_path')
            thumb.src = image || ''
            thumb.classList.toggle('hidden', !image)
            empty.classList.toggle('hidden', !!image)
            empty.classList.toggle('flex', !image)
        }

        function openModal(row) {
            row.querySelector('[data-modal]').classList.remove('hidden')
            document.body.classList.add('overflow-hidden')
        }

        function closeModal(modal) {
            modal.classList.add('hidden')
            document.body.classList.remove('overflow-hidden')
            // Closing a brand-new row that was left completely empty discards
            // it — otherwise every "+ Add product" + close leaves a blank
            // "New product" behind. Existing products (with an id) are kept.
            const row = modal.closest('[data-row]')
            const isNew = !row.querySelector('[name$="[id]"]').value
            const empty = ['product_id', 'name', 'category', 'price', 'description', 'image_path']
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
            if (e.target.closest('#products-form [data-add]')) {
                searchInput.value = '' // an active filter would hide the new row
                applyProductSearch()
                const rows = productsForm.querySelectorAll('[data-row]')
                if (rows.length) openModal(rows[rows.length - 1])
            }
        })

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return
            productsForm.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeModal)
        })

        // ---- search filter --------------------------------------------------
        // Filters by the name/category inputs' current values (so unsaved
        // edits still match). Hides non-matching rows only — hidden rows still
        // submit, so filtering never affects what Save changes writes.
        const searchInput = document.getElementById('product-search')
        const searchCount = document.getElementById('product-search-count')
        const emptyMsg = document.getElementById('products-empty')
        const productRows = () => Array.from(productsForm.querySelectorAll('[data-row]'))
            .filter((row) => row.querySelector('input[name$="[name]"]'))

        function applyProductSearch() {
            const q = searchInput.value.trim().toLowerCase()
            const rows = productRows()
            let shown = 0
            rows.forEach((row) => {
                const name = (row.querySelector('input[name$="[name]"]')?.value || '').toLowerCase()
                const category = (row.querySelector('input[name$="[category]"]')?.value || '').toLowerCase()
                const match = !q || name.includes(q) || category.includes(q)
                row.classList.toggle('hidden', !match)
                if (match) shown++
            })
            searchCount.classList.toggle('hidden', !q)
            searchCount.textContent = `Showing ${shown} of ${rows.length} products`
            emptyMsg.classList.toggle('hidden', !(q && shown === 0))
            emptyMsg.textContent = `No products match “${searchInput.value.trim()}”.`
        }

        searchInput.addEventListener('input', applyProductSearch)
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') e.preventDefault() // don't submit the form
        })

        // Keep the summary line in sync while editing in the popup.
        productsForm.addEventListener('input', (e) => {
            const row = e.target.closest('[data-row]')
            if (row) syncSummary(row)
        })
        productsForm.addEventListener('change', (e) => {
            const row = e.target.closest('[data-row]')
            if (row) syncSummary(row)
        })
    </script>
@endsection
