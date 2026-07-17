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
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-navy-800">Edit product</h3>
                    <button type="button" data-modal-close aria-label="Close" class="flex h-8 w-8 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-navy-700">✕</button>
                </div>
                <div class="space-y-3">
                    @include('admin.content._image-field', ['name' => "products[$i][image_path]", 'value' => $product['image_path'] ?? '', 'fieldLabel' => 'Image'])
                    {{-- Product ID is assign-once: editable while blank (new rows,
                         legacy products without a code), read-only after it's saved.
                         The server enforces this too — see Admin\ProductController. --}}
                    @php($pidLocked = ($product['product_id'] ?? '') !== '')
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
                    <div data-bundle-products-wrap class="{{ $type === 'bundle' ? '' : 'hidden' }}">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Linked products &amp; quantity (auto-added with this bundle, included free)</span>
                        @php($linkedQtys = (array) ($product['bundle_product_ids'] ?? []))
                        <div class="max-h-48 space-y-0.5 overflow-y-auto rounded-lg border border-slate-300 p-2">
                            @forelse(($products ?? []) as $op)
                                @php($opId = is_array($op) ? ($op['id'] ?? null) : $op->id)
                                @continue($opId === null || $opId === ($product['id'] ?? null))
                                @php($opName = is_array($op) ? ($op['name'] ?? '') : $op->name)
                                @php($opQty = (int) ($linkedQtys[$opId] ?? 0))
                                <div class="flex items-center justify-between gap-2 rounded-md px-1.5 py-1 text-sm text-navy-800 transition hover:bg-slate-50">
                                    <span class="truncate">{{ $opName }}</span>
                                    <input type="number" min="0" step="1" placeholder="0"
                                        name="products[{{ $i }}][bundle_product_ids][{{ $opId }}]"
                                        value="{{ $opQty > 0 ? $opQty : '' }}"
                                        class="w-16 shrink-0 rounded-md border border-slate-300 px-2 py-1 text-xs text-right outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                                </div>
                            @empty
                                <p class="px-1.5 py-1 text-xs text-slate-400">No other products yet.</p>
                            @endforelse
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
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Calories (optional)</span>
                        <input type="number" min="0" name="products[{{ $i }}][calories]" value="{{ $product['calories'] ?? '' }}" class="{{ $input }}">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Description</span>
                        <textarea name="products[{{ $i }}][description]" rows="3" class="{{ $input }}">{{ $product['description'] ?? '' }}</textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-slate-500">Allergens (one per line)</span>
                        <textarea name="products[{{ $i }}][features]" rows="3" class="{{ $input }}">{{ implode("\n", (array) ($product['features'] ?? [])) }}</textarea>
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
