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

    {{-- On a validation failure Laravel flashes the submitted `products`
         array back via old() automatically — but until now the page ignored
         it and re-rendered fresh from the DB, silently discarding every edit
         (including brand-new, not-yet-saved rows) just because one row had a
         bad value. $oldProducts, when present, is rendered instead below so
         the editor's work survives and the row(s) that failed keep the exact
         values that triggered the error. --}}
    @php($oldProducts = old('products'))

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="mb-1 font-semibold">Couldn't save — fix the highlighted field{{ $errors->count() > 1 ? 's' : '' }} below and save again.</p>
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        {{-- Field names with errors (e.g. "products.3.price"), for the script
             below to open the right row's popup and highlight the field. --}}
        <script id="product-form-error-fields" type="application/json">{!! json_encode($errors->keys()) !!}</script>

        {{-- Popup version of the banner above, so a failed save can't be
             missed (without it the redirect back looks like a successful
             save at a glance). Same shell as the Reset confirm modal;
             z-[90] sits above the row edit popups (z-50). --}}
        <div id="save-error-modal" class="fixed inset-0 z-[90] flex items-center justify-center bg-navy-900/60 p-4 backdrop-blur-sm" role="alertdialog" aria-modal="true" aria-label="Couldn't save">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl" data-modal-card>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-navy-800">Couldn't save your changes</h3>
                </div>
                <ul class="mt-4 max-h-48 list-inside list-disc space-y-1 overflow-y-auto rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <p class="mt-3 text-sm leading-relaxed text-slate-500">Nothing was saved yet — your edits are still here. The first problem field is highlighted for you.</p>
                <div class="mt-6 flex justify-end">
                    <button type="button" data-error-dismiss class="rounded-full bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-500/30 transition hover:from-brand-600 hover:to-brand-600">
                        Show me
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- novalidate: every row's fields live inside a closed (display:none)
         popup, and native browser validation can't focus an invalid hidden
         control — Chrome then blocks the submit with only a console warning,
         so Save silently does nothing (e.g. any legacy ₱0-price row). The
         server validates instead, and the error-locating script below opens
         the offending row's popup and highlights the field. --}}
    <form id="products-form" method="POST" action="{{ route('admin.products.sync') }}" novalidate>
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
                    {{-- categories are managed in one place: the Menu
                         Categories tab (add, photo, rename/merge, delete) --}}
                    <a href="{{ route('admin.content', ['section' => 'menuCategories']) }}" title="Edit categories" aria-label="Edit categories"
                        class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full border border-slate-300 bg-white text-navy-700 transition hover:border-brand-400 hover:text-brand-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z" />
                        </svg>
                    </a>
                </div>
            </div>
            <p id="product-search-count" class="mb-3 hidden text-xs text-slate-500"></p>
            <p id="products-empty" class="hidden py-8 text-center text-sm text-slate-500"></p>

            <div data-repeater>
                <div data-rows class="space-y-2">
                    {{-- Same keys the validator's error bag used ("products.<i>.field"),
                         so a failed row re-renders with exactly what was typed,
                         new rows and all — see $oldProducts above. --}}
                    @foreach($oldProducts ?? $products->all() as $i => $product)
                        @include('admin.products._product-row', ['i' => $i, 'product' => is_array($product) ? $product : $product->toArray()])
                    @endforeach
                </div>
                {{-- Client-side pagination (10/20/50/100 rows per page), applied
                     after the filters. Like filtering, paging only hides rows —
                     every row still submits, so it never affects what Save
                     changes writes. --}}
                <div id="products-pagination" class="mt-4 hidden flex-wrap items-center justify-between gap-3"></div>
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
    {{-- Lightweight catalogue for the bundle linked-products search — id/name/
         product_id only, so search results can be built on demand by JS
         instead of every row pre-rendering an <input> per other product
         (see the comment on data-bundle-products-wrap in _product-row). --}}
    <script id="all-products-data" type="application/json">{!! $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'product_id' => $p->product_id])->values()->toJson() !!}</script>
    <script>
        // ---- product list rows + edit popup --------------------------------
        // Each [data-row] is a compact summary line (thumbnail + name +
        // category · price); its full field set lives in a [data-modal] popup
        // inside the same row (still inside the form, so Save changes submits
        // every product, open or not). Same pattern as the Stores editor.
        const productsForm = document.getElementById('products-form')
        const ALL_PRODUCTS = JSON.parse(document.getElementById('all-products-data')?.textContent || '[]')

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

        // Confirm before removing a product that's already saved (has an id) —
        // matches the confirm() Menu Categories delete already uses. Runs in
        // the capture phase so it sees the click before _form-scripts' bubble
        // handler removes the row; declining stops it from ever running.
        // Blank/new rows (including the auto-discard in closeModal() above)
        // have no id yet and are removed without a prompt.
        productsForm.addEventListener('click', (e) => {
            const remove = e.target.closest('[data-remove]')
            if (!remove) return
            const row = remove.closest('[data-row]')
            if (!fieldValue(row, 'id')) return
            const name = fieldValue(row, 'name') || 'this product'
            if (!confirm(`Remove "${name}"? It will be archived once you save changes.`)) {
                e.stopImmediatePropagation()
                e.preventDefault()
            }
        }, true)

        // Registered after _form-scripts' click handler, so by the time the
        // [data-add] branch runs the new row already exists — open its popup.
        document.addEventListener('click', (e) => {
            const edit = e.target.closest('[data-edit]')
            if (edit) { openModal(edit.closest('[data-row]')); return }
            const close = e.target.closest('[data-modal-close]')
            if (close) { closeModal(close.closest('[data-modal]')); return }
            const bundleRemove = e.target.closest('[data-bundle-remove]')
            if (bundleRemove) {
                const item = bundleRemove.closest('[data-bundle-item]')
                const qtyInput = item.querySelector('[data-bundle-qty]')
                qtyInput.value = ''
                qtyInput.dispatchEvent(new Event('input', { bubbles: true }))
                applyBundleFilter(item.closest('[data-bundle-products-wrap]'))
                return
            }
            // Calorie entries: a lightweight repeater kept deliberately
            // separate from the shared [data-repeater] machinery above (see
            // the comment on data-calorie-repeater in _product-row.blade.php)
            // — plain-array field names (calorie_amounts[]/calorie_units[])
            // mean a clone never needs index substitution.
            const calorieAdd = e.target.closest('[data-calorie-add]')
            if (calorieAdd) {
                const wrap = calorieAdd.closest('[data-calorie-repeater]')
                wrap.querySelector('[data-calorie-rows]').insertAdjacentHTML('beforeend', wrap.querySelector('template').innerHTML)
                return
            }
            const calorieRemove = e.target.closest('[data-calorie-remove]')
            if (calorieRemove) { calorieRemove.closest('[data-calorie-entry]').remove(); return }
            // A removed row leaves a gap on the current page — re-page to fill it.
            if (e.target.closest('#products-form [data-remove]')) { applyProductSearch(); return }
            if (e.target.closest('#products-form [data-add]')) {
                // Clear the filters (they'd hide the new blank row) and jump to
                // the last page, where the new row is appended.
                searchInput.value = ''
                statusFilter.value = 'all'
                categoryFilter.value = ''
                productPage = Infinity
                applyProductSearch()
                const rows = productsForm.querySelectorAll('[data-row]')
                if (rows.length) openModal(rows[rows.length - 1])
            }
        })

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return
            productsForm.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeModal)
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

        // ---- pagination (page size selectable, after the filters) ------------
        const PAGE_SIZES = [10, 20, 50, 100]
        const pager = document.getElementById('products-pagination')
        let pageSize = PAGE_SIZES[0]
        let productPage = 1

        function applyProductSearch() {
            const q = searchInput.value.trim().toLowerCase()
            const wantStatus = statusFilter.value
            const wantCategory = categoryFilter.value.toLowerCase()
            const rows = productRows()
            const matches = []
            rows.forEach((row) => {
                const name = (row.querySelector('input[name$="[name]"]')?.value || '').toLowerCase()
                const category = (row.querySelector('select[name$="[category]"]')?.value || '').toLowerCase()
                const status = row.querySelector('select[name$="[status]"]')?.value || ''
                const byText = !q || name.includes(q) || category.includes(q)
                const byStatus = wantStatus === 'all' || (wantStatus === 'none' ? status === '' : status === wantStatus)
                const byCategory = !wantCategory || category === wantCategory
                if (byText && byStatus && byCategory) matches.push(row)
                else row.classList.add('hidden')
            })
            // Page the matches: clamp the current page (filters may have shrunk
            // the list), show its rows, hide the rest.
            const pages = Math.max(1, Math.ceil(matches.length / pageSize))
            productPage = Math.min(Math.max(productPage, 1), pages)
            matches.forEach((row, i) => {
                row.classList.toggle('hidden', Math.floor(i / pageSize) + 1 !== productPage)
            })
            const filtering = q || wantStatus !== 'all' || wantCategory
            searchCount.classList.toggle('hidden', !filtering)
            searchCount.textContent = `Showing ${matches.length} of ${rows.length} products`
            emptyMsg.classList.toggle('hidden', !(filtering && matches.length === 0))
            emptyMsg.textContent = 'No products match the current filters.'
            renderPager(matches.length, pages)
        }

        function renderPager(total, pages) {
            // Keep the bar (and its page-size select) visible whenever there's
            // more than the smallest page size, even if everything fits on one
            // page at the current size — otherwise "100 per page" would hide
            // the select and there'd be no way back.
            const show = total > PAGE_SIZES[0]
            pager.classList.toggle('hidden', !show)
            pager.classList.toggle('flex', show)
            if (!show) { pager.innerHTML = ''; return }
            const from = (productPage - 1) * pageSize + 1
            const to = Math.min(productPage * pageSize, total)
            // Windowed page numbers: 1 … current±1 … last.
            const nums = []
            for (let n = 1; n <= pages; n++) {
                if (n === 1 || n === pages || Math.abs(n - productPage) <= 1) nums.push(n)
                else if (nums[nums.length - 1] !== '…') nums.push('…')
            }
            const base = 'h-9 min-w-9 rounded-full border px-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-40'
            const idle = `${base} border-slate-300 bg-white text-navy-700 hover:border-brand-400 hover:text-brand-600`
            const active = `${base} border-brand-500 bg-brand-500 text-white`
            pager.innerHTML = `
                <label class="flex items-center gap-2 text-xs text-slate-500">
                    Show
                    <select data-page-size data-no-dirty aria-label="Products per page"
                        class="rounded-full border border-slate-300 bg-white px-2 py-1.5 text-sm font-medium text-navy-800 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        ${PAGE_SIZES.map((s) => `<option value="${s}" ${s === pageSize ? 'selected' : ''}>${s}</option>`).join('')}
                    </select>
                    per page · ${from}–${to} of ${total}
                </label>
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" data-page="${productPage - 1}" ${productPage <= 1 ? 'disabled' : ''} aria-label="Previous page" class="${idle}">‹</button>
                    ${nums.map((n) => n === '…'
                        ? '<span class="px-1 text-sm text-slate-400">…</span>'
                        : `<button type="button" data-page="${n}" ${n === productPage ? 'aria-current="page"' : ''} class="${n === productPage ? active : idle}">${n}</button>`).join('')}
                    <button type="button" data-page="${productPage + 1}" ${productPage >= pages ? 'disabled' : ''} aria-label="Next page" class="${idle}">›</button>
                </div>`
        }

        pager.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-page]')
            if (!btn || btn.disabled) return
            productPage = Number(btn.dataset.page)
            applyProductSearch()
        })

        pager.addEventListener('change', (e) => {
            if (!e.target.matches('[data-page-size]')) return
            pageSize = Number(e.target.value)
            productPage = 1
            applyProductSearch()
        })

        searchInput.addEventListener('input', () => { productPage = 1; applyProductSearch() })
        statusFilter.addEventListener('change', () => { productPage = 1; applyProductSearch() })
        categoryFilter.addEventListener('change', () => { productPage = 1; applyProductSearch() })
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') e.preventDefault() // don't submit the form
        })

        // Linked-products picker: already-linked (qty > 0) rows are the only
        // ones the server renders (see _product-row's comment on
        // data-bundle-products-wrap — one <input> per OTHER product per row
        // blew past PHP's max_input_vars with a 70+ item catalogue). Typing
        // in the search box instead builds matching rows here, from the
        // shared ALL_PRODUCTS list, only for products actually being looked
        // at — so the form only ever carries the fields it needs.
        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]))
        }
        function bundleItemHtml(rowIndex, product, qty) {
            const code = product.product_id ? `<span class="text-slate-400">${escapeHtml(product.product_id)}</span> ` : ''
            return `<div data-bundle-item data-id="${product.id}" data-search="${escapeHtml((product.name + ' ' + (product.product_id || '')).toLowerCase())}" class="flex items-center justify-between gap-2 rounded-md px-1.5 py-1 text-sm text-navy-800 transition hover:bg-slate-50">
                <span class="min-w-0 flex-1 truncate">${code}${escapeHtml(product.name)}</span>
                <input type="number" min="0" step="1" placeholder="0" data-bundle-qty name="products[${rowIndex}][bundle_product_ids][${product.id}]" value="${qty > 0 ? qty : ''}" class="w-16 shrink-0 rounded-md border border-slate-300 px-2 py-1 text-xs text-right outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                <button type="button" data-bundle-remove aria-label="Remove ${escapeHtml(product.name)} from bundle" class="shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
                </button>
            </div>`
        }
        function applyBundleFilter(wrap) {
            const q = (wrap.querySelector('[data-bundle-search]')?.value || '').trim().toLowerCase()
            const list = wrap.querySelector('[data-bundle-list]')
            const rowIndex = wrap.dataset.rowIndex
            const selfId = wrap.dataset.selfId

            let anyVisible = false
            list.querySelectorAll('[data-bundle-item]').forEach((item) => {
                const qty = Number(item.querySelector('[data-bundle-qty]').value) || 0
                const show = qty > 0 || (q && item.dataset.search.includes(q))
                item.classList.toggle('hidden', !show)
                if (show) anyVisible = true
            })

            if (q) {
                const existingIds = new Set(Array.from(list.querySelectorAll('[data-bundle-item]')).map((el) => el.dataset.id))
                ALL_PRODUCTS.forEach((p) => {
                    if (p.id === selfId || existingIds.has(p.id)) return
                    const hay = (p.name + ' ' + (p.product_id || '')).toLowerCase()
                    if (!hay.includes(q)) return
                    list.insertAdjacentHTML('beforeend', bundleItemHtml(rowIndex, p, 0))
                    anyVisible = true
                })
            }

            const emptyMsg = list.querySelector('[data-bundle-empty]')
            if (emptyMsg) emptyMsg.classList.toggle('hidden', anyVisible)
            const noMatch = wrap.querySelector('[data-bundle-no-match]')
            if (noMatch) noMatch.classList.toggle('hidden', anyVisible || !q)
        }

        // Keep the summary line in sync while editing in the popup.
        productsForm.addEventListener('input', (e) => {
            const row = e.target.closest('[data-row]')
            if (row) syncSummary(row)
            if (e.target.matches('[data-bundle-search]')) {
                applyBundleFilter(e.target.closest('[data-bundle-products-wrap]'))
            }
        })
        productsForm.addEventListener('change', (e) => {
            const row = e.target.closest('[data-row]')
            if (row) syncSummary(row)
            if (e.target.matches('[data-product-type]')) {
                row.querySelector('[data-bundle-products-wrap]')?.classList.toggle('hidden', e.target.value !== 'bundle')
            }
        })

        // Initial render: apply page 1 (all rows arrive visible from the server).
        applyProductSearch()

        // ---- validation-error locating --------------------------------------
        // On a failed save, the server flashes which fields were invalid
        // ("products.<i>.field" — see the JSON blob above). With 70+ products
        // possibly spread across filters/pages and collapsed inside popups,
        // finding row <i> by eye is exactly the kind of thing worth automating:
        // clear whatever's hiding it, open its popup, and focus the bad field.
        const errorFieldsEl = document.getElementById('product-form-error-fields')
        if (errorFieldsEl) {
            const errorNames = JSON.parse(errorFieldsEl.textContent || '[]')
                .map((key) => key.split('.').map((part, i) => (i === 0 ? part : `[${part}]`)).join(''))
            const errorFields = errorNames
                .map((name) => productsForm.querySelector(`[name="${CSS.escape(name)}"]`))
                .filter(Boolean)

            errorFields.forEach((field) => {
                field.classList.add('!border-red-400', 'ring-2', 'ring-red-500/30')
                field.closest('[data-row]')?.classList.add('ring-2', 'ring-red-400')
            })

            const firstField = errorFields[0]
            const firstRow = firstField?.closest('[data-row]')
            if (firstRow) {
                searchInput.value = ''
                statusFilter.value = 'all'
                categoryFilter.value = ''
                applyProductSearch()
                const index = productRows().indexOf(firstRow)
                if (index >= 0) productPage = Math.floor(index / pageSize) + 1
                applyProductSearch()
                openModal(firstRow)
                firstField.scrollIntoView({ block: 'center' })
                firstField.focus()
            }
        }

        // ---- "Couldn't save" popup ------------------------------------------
        // Rendered (visible) only when the server bounced the save back with
        // validation errors. Dismiss via the button, the backdrop, or Escape —
        // underneath, the offending row's popup is already open with the bad
        // field highlighted (see above).
        const saveErrorModal = document.getElementById('save-error-modal')
        if (saveErrorModal) {
            const dismissSaveError = () => saveErrorModal.classList.add('hidden')
            saveErrorModal.querySelector('[data-error-dismiss]').addEventListener('click', dismissSaveError)
            saveErrorModal.addEventListener('click', (e) => {
                if (!e.target.closest('[data-modal-card]')) dismissSaveError()
            })
            // Capture phase + stopPropagation so the first Escape closes only
            // this popup, not also the row editor the script above opened.
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape' || saveErrorModal.classList.contains('hidden')) return
                e.stopPropagation()
                dismissSaveError()
            }, true)
        }
    </script>
@endsection
