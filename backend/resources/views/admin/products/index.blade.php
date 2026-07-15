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

            {{-- The ids loaded into the editor: anything here that's missing
                 from the submitted cards was removed → archived on save. --}}
            @foreach($products as $product)
                <input type="hidden" name="originalIds[]" value="{{ $product->id }}">
            @endforeach

            {{-- Search + filters (all client-side; data-no-dirty so filtering
                 never surfaces the Save/Reset buttons). --}}
            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1 lg:max-w-md">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <input type="search" id="product-search" data-no-dirty placeholder="Search products by name or category…"
                        class="w-full rounded-full border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                </div>

                <select id="product-status-filter" data-no-dirty aria-label="Filter by status"
                    class="rounded-full border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                    <option value="all">All statuses</option>
                    <option value="new">New</option>
                    <option value="best_seller">Best seller</option>
                    <option value="bundle">Bundle</option>
                    <option value="sold_out">Sold out</option>
                    <option value="none">None</option>
                </select>

                <div class="flex items-center gap-2">
                    <select id="product-category-filter" data-no-dirty aria-label="Filter by category"
                        class="rounded-full border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <option value="">All categories</option>
                        @foreach($categoryOptions as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                    {{-- opens the Categories modal (add/delete the declared list;
                         delete reassigns that category's products to none) --}}
                    <button type="button" id="category-manage" data-no-dirty title="Edit categories" aria-label="Edit categories"
                        class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-navy-700 transition hover:border-brand-400 hover:text-brand-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" />
                        </svg>
                    </button>
                </div>
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

    {{-- Categories manager — opened by the toolbar's Edit button. Add or
         delete categories in one place. Outside #products-form so its inputs
         never submit with (or dirty) the products grid. Add/delete each POST
         and reload, so the list is always server-rendered fresh. --}}
    <div id="category-manage-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy-900/50" data-cat-modal-close></div>
        <div class="relative flex max-h-[85vh] w-full max-w-sm flex-col rounded-2xl bg-white p-5 shadow-xl">
            <div class="mb-1 flex items-center justify-between">
                <h3 class="text-base font-bold text-navy-800">Categories</h3>
                <button type="button" data-cat-modal-close aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-navy-700">✕</button>
            </div>
            <p class="mb-3 text-sm text-slate-500">Add or delete the categories used in the product dropdowns and /menu filters.</p>
            <div class="mb-3 flex gap-2">
                <input type="text" id="category-add-name" maxlength="50" placeholder="New category name…" class="{{ $input }}">
                <button type="button" id="category-add-confirm" class="shrink-0 rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-navy-700">Add</button>
            </div>
            <ul class="min-h-0 flex-1 divide-y divide-slate-100 overflow-y-auto">
                @forelse($categoryOptions as $c)
                    <li class="flex items-center justify-between gap-2 py-2">
                        <span class="truncate text-sm font-medium text-navy-800">{{ $c }}</span>
                        <button type="button" data-category-remove="{{ $c }}" title="Delete {{ $c }}" aria-label="Delete {{ $c }}"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><line x1="10" y1="11" x2="10" y2="17" /><line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                        </button>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No categories yet — add one above.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div id="category-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy-900/50" data-cat-modal-close></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
            <h3 class="text-base font-bold text-navy-800">Delete category</h3>
            <p class="mb-4 mt-0.5 text-sm text-slate-500">Delete <span data-category-name class="font-semibold text-navy-800"></span>? Its products keep existing but lose the category.</p>
            <div class="flex justify-end gap-2">
                <button type="button" data-cat-modal-close class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-navy-700 transition hover:bg-slate-50">Cancel</button>
                <button type="button" id="category-delete-confirm" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>
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

        // Labels/colors must match $statusBadges in _product-row.blade.php.
        const STATUS_BADGES = {
            new: ['New', 'bg-emerald-100 text-emerald-700'],
            best_seller: ['Best seller', 'bg-amber-100 text-amber-700'],
            bundle: ['Bundle', 'bg-blue-100 text-blue-700'],
            sold_out: ['Sold out', 'bg-red-100 text-red-700'],
        }
        const BADGE_BASE = 'shrink-0 rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide'

        function syncSummary(row) {
            row.querySelector('[data-product-name]').textContent = fieldValue(row, 'name') || 'New product'
            const badgeEl = row.querySelector('[data-product-status]')
            const badge = STATUS_BADGES[fieldValue(row, 'status')]
            badgeEl.className = BADGE_BASE + (badge ? ' ' + badge[1] : ' hidden')
            badgeEl.textContent = badge ? badge[0] : ''
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
            closeCategoryModals()
        })

        // ---- search + status/category filters -------------------------------
        // Filters by the rows' current input values (so unsaved edits still
        // match). Hides non-matching rows only — hidden rows still submit, so
        // filtering never affects what Save changes writes.
        const searchInput = document.getElementById('product-search')
        const statusFilter = document.getElementById('product-status-filter')
        const categoryFilter = document.getElementById('product-category-filter')
        const searchCount = document.getElementById('product-search-count')
        const emptyMsg = document.getElementById('products-empty')
        const productRows = () => Array.from(productsForm.querySelectorAll('[data-row]'))
            .filter((row) => row.querySelector('input[name$="[name]"]'))

        function applyProductSearch() {
            const q = searchInput.value.trim().toLowerCase()
            const wantStatus = statusFilter.value
            const wantCategory = categoryFilter.value.toLowerCase()
            const rows = productRows()
            let shown = 0
            rows.forEach((row) => {
                const name = (row.querySelector('input[name$="[name]"]')?.value || '').toLowerCase()
                const category = (row.querySelector('select[name$="[category]"]')?.value || '').toLowerCase()
                const status = row.querySelector('select[name$="[status]"]')?.value || ''
                const byText = !q || name.includes(q) || category.includes(q)
                const byStatus = wantStatus === 'all' || (wantStatus === 'none' ? status === '' : status === wantStatus)
                const byCategory = !wantCategory || category === wantCategory
                const match = byText && byStatus && byCategory
                row.classList.toggle('hidden', !match)
                if (match) shown++
            })
            const filtering = q || wantStatus !== 'all' || wantCategory
            searchCount.classList.toggle('hidden', !filtering)
            searchCount.textContent = `Showing ${shown} of ${rows.length} products`
            emptyMsg.classList.toggle('hidden', !(filtering && shown === 0))
            emptyMsg.textContent = 'No products match the current filters.'
        }

        searchInput.addEventListener('input', applyProductSearch)
        statusFilter.addEventListener('change', applyProductSearch)
        categoryFilter.addEventListener('change', applyProductSearch)
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') e.preventDefault() // don't submit the form
        })

        // ---- quick category add / delete ------------------------------------
        // Both post via a detached form (this page's markup already lives
        // inside #products-form; forms can't nest). Delete uses the existing
        // Menu Categories endpoint, so its products are reassigned safely.
        function postTo(action, fields) {
            const form = document.createElement('form')
            form.method = 'POST'
            form.action = action
            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input')
                input.type = 'hidden'
                input.name = name
                input.value = value
                form.appendChild(input)
            })
            document.body.appendChild(form)
            form.submit()
        }

        const categoryManageModal = document.getElementById('category-manage-modal')
        const categoryAddName = document.getElementById('category-add-name')
        const categoryDeleteModal = document.getElementById('category-delete-modal')
        const categoryModals = [categoryManageModal, categoryDeleteModal]
        let categoryToDelete = ''

        function showCategoryModal(modal) {
            closeCategoryModals()
            modal.classList.remove('hidden')
            modal.classList.add('flex')
        }

        function closeCategoryModals() {
            categoryModals.forEach((m) => { m.classList.add('hidden'); m.classList.remove('flex') })
        }

        document.getElementById('category-manage').addEventListener('click', () => {
            categoryAddName.value = ''
            showCategoryModal(categoryManageModal)
            categoryAddName.focus()
        })

        function confirmAddCategory() {
            const name = categoryAddName.value.trim()
            if (!name) { categoryAddName.focus(); return }
            postTo('{{ route('admin.content.categories.add') }}', {
                _token: '{{ csrf_token() }}',
                name,
            })
        }
        document.getElementById('category-add-confirm').addEventListener('click', confirmAddCategory)
        categoryAddName.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); confirmAddCategory() }
        })

        document.getElementById('category-delete-confirm').addEventListener('click', () => {
            if (!categoryToDelete) return
            postTo('{{ url('admin/content/categories') }}/' + encodeURIComponent(categoryToDelete) + '/delete', {
                _token: '{{ csrf_token() }}',
            })
        })

        document.addEventListener('click', (e) => {
            const remove = e.target.closest('[data-category-remove]')
            if (remove) {
                categoryToDelete = remove.dataset.categoryRemove
                categoryDeleteModal.querySelector('[data-category-name]').textContent = categoryToDelete
                showCategoryModal(categoryDeleteModal)
                return
            }
            if (e.target.closest('[data-cat-modal-close]')) closeCategoryModals()
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
