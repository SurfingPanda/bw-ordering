{{-- One product ($i = index or repeater token, $product = row as array): a
     compact list row (thumbnail + name + category · price) with Edit/Remove;
     the full field set — matching the old AdminContent.jsx products card
     field-for-field — lives in a popup opened by Edit. The popup stays INSIDE
     the form so its inputs submit with "Save changes". Wiring lives in
     products/index.blade.php. --}}
@php($product = (array) ($product ?? []))
<div data-row class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
    <input type="hidden" name="products[{{ $i }}][id]" value="{{ $product['id'] ?? '' }}">

    <div class="flex items-center gap-3">
        <span data-row-number class="text-xs font-semibold uppercase tracking-wide text-slate-400">#</span>
        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
            <img data-product-thumb src="{{ $product['image_path'] ?? '' }}" alt="" loading="lazy" decoding="async"
                class="h-full w-full object-cover {{ empty($product['image_path']) ? 'hidden' : '' }}">
            <div data-product-thumb-empty class="h-full w-full items-center justify-center text-center text-[0.5rem] leading-tight text-slate-400 {{ empty($product['image_path']) ? 'flex' : 'hidden' }}">no image</div>
        </div>
        <div class="min-w-0 flex-1">
            <p class="flex items-center gap-2">
                <span data-product-name class="truncate text-sm font-semibold text-navy-800">{{ ($product['name'] ?? '') !== '' ? $product['name'] : 'New product' }}</span>
                {{-- Status badge — label/colors must match STATUS_BADGES in products/index.blade.php --}}
                @php($statusBadges = [
                    'new' => ['New', 'bg-emerald-100 text-emerald-700'],
                    'best_seller' => ['Best seller', 'bg-amber-100 text-amber-700'],
                    'bundle' => ['Bundle', 'bg-blue-100 text-blue-700'],
                    'sold_out' => ['Sold out', 'bg-red-100 text-red-700'],
                ])
                @php($badge = $statusBadges[$product['status'] ?? ''] ?? null)
                <span data-product-status class="shrink-0 rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide {{ $badge ? $badge[1] : 'hidden' }}">{{ $badge[0] ?? '' }}</span>
            </p>
            <p data-product-meta class="truncate text-xs text-slate-500">{{ collect([$product['category'] ?? null, ($product['price'] ?? '') !== '' ? '₱' . $product['price'] : null])->filter()->implode(' · ') }}</p>
        </div>
        <button type="button" data-edit class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-navy-700 transition hover:border-brand-400 hover:text-brand-600">Edit</button>
        <button type="button" data-remove class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">Remove</button>
    </div>

    <div data-modal class="hidden">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy-900/50"></div>
            <div class="scrollbar-slim relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-navy-800">Edit product</h3>
                    <button type="button" data-modal-close aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-navy-700">✕</button>
                </div>
                <div class="space-y-3">
                    @include('admin.content._image-field', ['name' => "products[$i][image_path]", 'value' => $product['image_path'] ?? '', 'fieldLabel' => 'Image'])
                    {{-- Product ID is assign-once: editable while blank (new rows,
                         legacy products without a code), read-only after it's saved.
                         The server enforces this too — see Admin\ProductController.
                         Locked based on the row's ACTUAL saved code in the DB, not
                         whatever is currently in the field — otherwise re-rendering
                         a failed save's old() input would lock a brand-new row the
                         moment its (possibly duplicate, not-yet-saved) typed code
                         got flashed back into the field. --}}
                    @php($productsById = collect($products ?? [])->keyBy(fn ($op) => is_array($op) ? ($op['id'] ?? null) : $op->id))
                    @php($savedProduct = ! empty($product['id']) ? $productsById->get($product['id']) : null)
                    @php($savedProductId = $savedProduct ? (is_array($savedProduct) ? ($savedProduct['product_id'] ?? null) : $savedProduct->product_id) : null)
                    @php($pidLocked = ! empty($savedProductId))
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Product ID {{ $pidLocked ? '' : '(unique, max 20 — set once)' }}</span>
                        <input type="text" name="products[{{ $i }}][product_id]" value="{{ $product['product_id'] ?? '' }}" maxlength="20" placeholder="e.g. BRD-001"
                            @if($pidLocked) readonly @endif class="{{ $input }} {{ $pidLocked ? 'cursor-not-allowed bg-slate-100 text-slate-500' : '' }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Name</span>
                        <input type="text" name="products[{{ $i }}][name]" value="{{ $product['name'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Type</span>
                        @php($type = ($product['type'] ?? 'single') === 'bundle' ? 'bundle' : 'single')
                        <select name="products[{{ $i }}][type]" data-product-type class="{{ $input }} bg-white">
                            <option value="single" @selected($type === 'single')>Single</option>
                            <option value="bundle" @selected($type === 'bundle')>Bundle</option>
                        </select>
                    </label>
                    {{-- Bundle only: which other products get auto-added to the
                         cart alongside this one, and how many of each (see
                         menu.blade.php's add()), priced at ₱0 up to the
                         matching bundle quantity (see OrderCreationService).
                         Blank/0 = not included. --}}
                    {{-- Only already-linked products render real <input>s here —
                         with 70+ products in the catalogue, rendering one qty
                         field per OTHER product on EVERY row (bundle or not)
                         used to blow past PHP's max_input_vars (1000) on save,
                         silently dropping fields — including other rows'
                         prices. Search results are built on demand by JS
                         instead (see applyBundleFilter in index.blade.php),
                         so the form only ever carries fields for products
                         actually being linked. --}}
                    <div data-bundle-products-wrap data-row-index="{{ $i }}" data-self-id="{{ $product['id'] ?? '' }}" class="{{ $type === 'bundle' ? '' : 'hidden' }}">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Linked products &amp; quantity (auto-added with this bundle, included free)</span>
                        @php($linkedQtys = (array) ($product['bundle_product_ids'] ?? []))
                        <input type="text" data-bundle-search data-no-dirty placeholder="Search products by name or ID…"
                            class="mb-1.5 w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-xs outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        <div data-bundle-list class="max-h-48 space-y-0.5 overflow-y-auto rounded-lg border border-slate-300 p-2">
                            @forelse($linkedQtys as $opId => $opQty)
                                @continue((int) $opQty <= 0 || ! $productsById->has($opId))
                                @php($op = $productsById[$opId])
                                @php($opName = is_array($op) ? ($op['name'] ?? '') : $op->name)
                                @php($opCode = is_array($op) ? ($op['product_id'] ?? '') : $op->product_id)
                                <div data-bundle-item data-id="{{ $opId }}" data-search="{{ strtolower(trim($opName.' '.$opCode)) }}"
                                    class="flex items-center justify-between gap-2 rounded-md px-1.5 py-1 text-sm text-navy-800 transition hover:bg-slate-50">
                                    <span class="min-w-0 flex-1 truncate">
                                        @if($opCode)<span class="text-slate-400">{{ $opCode }}</span> @endif{{ $opName }}
                                    </span>
                                    <input type="number" min="0" step="1" placeholder="0" data-bundle-qty
                                        name="products[{{ $i }}][bundle_product_ids][{{ $opId }}]"
                                        value="{{ (int) $opQty }}"
                                        class="w-16 shrink-0 rounded-md border border-slate-300 px-2 py-1 text-xs text-right outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                    <button type="button" data-bundle-remove aria-label="Remove {{ $opName }} from bundle"
                                        class="shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <line x1="6" y1="6" x2="18" y2="18" /><line x1="6" y1="18" x2="18" y2="6" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <p data-bundle-empty class="px-1.5 py-1 text-xs text-slate-400">No products linked yet — search below to add some.</p>
                            @endforelse
                            <p data-bundle-no-match class="hidden px-1.5 py-1 text-xs text-slate-400">No products match your search.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Category</span>
                            {{-- Strict picker: new categories are created with the ＋
                                 button in the Products toolbar, not by typing here. --}}
                            @php($cat = $product['category'] ?? '')
                            <select name="products[{{ $i }}][category]" class="{{ $input }} bg-white">
                                <option value="">No category</option>
                                @foreach($categoryOptions ?? [] as $c)
                                    <option value="{{ $c }}" @selected($cat === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Status</span>
                            @php($status = $product['status'] ?? '')
                            <select name="products[{{ $i }}][status]" class="{{ $input }} bg-white">
                                <option value="" @selected($status === '' || $status === null)>None</option>
                                <option value="new" @selected($status === 'new')>New</option>
                                <option value="best_seller" @selected($status === 'best_seller')>Best seller</option>
                                <option value="bundle" @selected($status === 'bundle')>Bundle</option>
                                <option value="sold_out" @selected($status === 'sold_out')>Sold out</option>
                            </select>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Price (₱)</span>
                            <input type="number" step="0.01" min="0.01" required name="products[{{ $i }}][price]" value="{{ $product['price'] ?? '' }}" class="{{ $input }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Was (₱, optional)</span>
                            <input type="number" step="0.01" min="0" name="products[{{ $i }}][original_price]" value="{{ $product['original_price'] ?? '' }}" class="{{ $input }}">
                        </label>
                    </div>
                    {{-- A failed save flashes back the raw calorie_amounts[]/calorie_units[]
                         the repeater actually submitted; a DB-loaded row instead carries
                         calorie_info (see Admin\ProductController::calorieInfo). Build one
                         normalized list either way, as a single expression (not a multi-line
                         PHP block directive) — mixing directive styles in this file confuses
                         Blade's compiler and corrupts everything rendered after it. --}}
                    @php($calorieEntries = (isset($product['calorie_amounts']) || isset($product['calorie_units']))
                        ? array_map(fn ($amount, $unit) => ['amount' => $amount, 'unit' => $unit], (array) ($product['calorie_amounts'] ?? []), (array) ($product['calorie_units'] ?? []))
                        : (array) ($product['calorie_info'] ?? []))
                    {{-- Deliberately NOT the shared [data-repeater]/[data-row]
                         machinery from _form-scripts.blade.php: every entry
                         here sits inside a product's own [data-row], and that
                         same attribute is what syncSummary()/productRows()/the
                         remove-confirm handler in index.blade.php use to find
                         the enclosing PRODUCT card. Reusing it on these
                         sub-rows would make e.g. typing in a calorie field
                         resolve "the row" to the calorie entry instead of the
                         product, crashing syncSummary(). Distinct
                         data-calorie-* attributes (wired in index.blade.php)
                         keep this fully separate. --}}
                    <div>
                        <span class="mb-1 block text-xs font-medium text-slate-500">Calories (optional)</span>
                        <p class="mb-1.5 text-xs text-slate-400">One entry per serving size — e.g. per piece, per whole.</p>
                        <div data-calorie-repeater>
                            <div data-calorie-rows class="space-y-2">
                                @foreach($calorieEntries as $entry)
                                    <div data-calorie-entry class="flex items-center gap-2">
                                        <input type="number" min="0" placeholder="Calories" name="products[{{ $i }}][calorie_amounts][]" value="{{ $entry['amount'] ?? '' }}" class="{{ $input }}">
                                        <input type="text" maxlength="50" placeholder="e.g. piece, whole" name="products[{{ $i }}][calorie_units][]" value="{{ $entry['unit'] ?? '' }}" class="{{ $input }}">
                                        <button type="button" data-calorie-remove aria-label="Remove calorie entry" class="shrink-0 rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18" /><line x1="6" y1="18" x2="18" y2="6" /></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <template>
                                <div data-calorie-entry class="flex items-center gap-2">
                                    <input type="number" min="0" placeholder="Calories" name="products[{{ $i }}][calorie_amounts][]" class="{{ $input }}">
                                    <input type="text" maxlength="50" placeholder="e.g. piece, whole" name="products[{{ $i }}][calorie_units][]" class="{{ $input }}">
                                    <button type="button" data-calorie-remove aria-label="Remove calorie entry" class="shrink-0 rounded-md p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18" /><line x1="6" y1="18" x2="18" y2="6" /></svg>
                                    </button>
                                </div>
                            </template>
                            <button type="button" data-calorie-add class="mt-1.5 text-xs font-semibold text-brand-600 transition hover:text-brand-700">+ Add calories</button>
                        </div>
                    </div>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
                        <textarea name="products[{{ $i }}][description]" rows="3" class="{{ $input }}">{{ $product['description'] ?? '' }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Allergens (one per line)</span>
                        <textarea name="products[{{ $i }}][features]" rows="3" class="{{ $input }}">{{ implode("\n", (array) ($product['features'] ?? [])) }}</textarea>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Net weight (optional)</span>
                            <input type="text" maxlength="100" placeholder="e.g. 250g" name="products[{{ $i }}][net_weight]" value="{{ $product['net_weight'] ?? '' }}" class="{{ $input }}">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-500">Storage condition (optional)</span>
                            <input type="text" maxlength="150" placeholder="e.g. Refrigerate after opening" name="products[{{ $i }}][storage_condition]" value="{{ $product['storage_condition'] ?? '' }}" class="{{ $input }}">
                        </label>
                    </div>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Serving note (optional)</span>
                        <input type="text" maxlength="100" placeholder="e.g. Best served when hot" name="products[{{ $i }}][serving_note]" value="{{ $product['serving_note'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="flex items-center justify-between pt-1">
                        <span class="text-xs font-medium text-slate-500">Featured</span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center">
                            <input type="checkbox" name="products[{{ $i }}][is_featured]" value="1" class="peer sr-only" @checked(! empty($product['is_featured']))>
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-brand-500"></span>
                            <span class="relative ml-0.5 inline-block h-5 w-5 transform rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                </div>
                <button type="button" data-modal-close class="mt-5 w-full rounded-lg bg-navy-800 py-2.5 text-sm font-semibold text-white transition hover:bg-navy-700">Done</button>
            </div>
        </div>
    </div>
</div>
