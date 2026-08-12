<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SiteContentController as PublicSiteContentController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The Site Editor's "Menu Products" section — Blade port of the old
 * AdminContent.jsx products editor: one grid of product cards edited freely
 * and persisted in a single "Save changes" (bulk upsert; cards removed from
 * the grid are archived, never hard-deleted). Same semantics and
 * `products.index` cache key as the JSON API's ProductController::sync().
 */
class ProductController extends Controller
{
    private function authorize(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'products'), 403, 'Forbidden.');
    }

    /** Sidebar data for the Site Editor shell this page renders inside. */
    private function shell(Request $request): array
    {
        return [
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($this->supabaseUser($request)['email'] ?? null),
            'navAccess' => $this->editorNavAccess($this->supabaseUser($request)['email'] ?? null),
        ];
    }

    public function index(Request $request)
    {
        $this->authorize($request);

        $products = Product::whereNull('archived_at')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Category suggestions = categories in use + editor-declared ones,
        // mirroring the old editor's categoryOptions (shared <datalist> so
        // editors reuse names instead of creating Bread/Breads duplicates).
        $declared = (array) (((array) app(PublicSiteContentController::class)->cachedData())['menuCategories'] ?? []);
        $categoryOptions = $products->pluck('category')->filter()->merge($declared)->unique()->sort()->values();

        return view('admin.products.index', [
            'products' => $products,
            'categoryOptions' => $categoryOptions,
        ] + $this->shell($request));
    }

    /**
     * Bulk save the whole grid: rows with an id update, rows without insert,
     * and any product in `originalIds` that no longer appears was removed in
     * the editor → archive it. Blank new cards (no name) are ignored.
     */
    public function sync(Request $request)
    {
        $this->authorize($request);

        $rules = [
            'products' => 'nullable|array',
            'products.*.id' => 'nullable|string',
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.original_price' => 'nullable|numeric|min:0',
            // Parallel arrays (not nested-indexed objects) so the admin
            // repeater's "+ Add calories" button can append a bare pair of
            // inputs client-side without renumbering anything — see
            // _product-row.blade.php.
            'products.*.calorie_amounts' => 'nullable|array',
            'products.*.calorie_amounts.*' => 'nullable|integer|min:0',
            'products.*.calorie_units' => 'nullable|array',
            'products.*.calorie_units.*' => 'nullable|string|max:50',
            'products.*.net_weight' => 'nullable|string|max:100',
            'products.*.storage_condition' => 'nullable|string|max:150',
            'products.*.serving_note' => 'nullable|string|max:100',
            'products.*.status' => ['nullable', Rule::in(['new', 'best_seller', 'bundle', 'sold_out'])],
            'products.*.type' => ['nullable', Rule::in(['single', 'bundle'])],
            // bundle_product_ids arrives keyed by linked product id, valued by
            // quantity (e.g. bundle_product_ids[<uuid>]=2) — Laravel's `.*`
            // wildcard validates the values regardless of key.
            'products.*.bundle_product_ids' => 'nullable|array',
            'products.*.bundle_product_ids.*' => 'nullable|integer|min:0',
            'originalIds' => 'nullable|array',
            'originalIds.*' => 'string',
        ];
        // Product ID must be unique: `distinct` catches duplicates within the
        // submitted grid, and the per-row unique rule checks the table while
        // ignoring the row's own record (it may keep its existing code).
        foreach (array_keys((array) $request->input('products', [])) as $k) {
            $rules["products.$k.product_id"] = [
                'nullable', 'string', 'max:20', 'distinct',
                Rule::unique('products', 'product_id')->ignore($request->input("products.$k.id")),
            ];
        }
        $validator = Validator::make($request->all(), $rules, [
            'products.*.product_id.distinct' => 'Product ID :input is used by more than one product.',
            'products.*.product_id.unique' => 'Product ID :input is already taken by another product.',
            'products.*.product_id.max' => 'Product ID must be 20 characters or fewer.',
        ]);
        // A named card also needs a real price — the base 'nullable|min:0'
        // rule above would otherwise let a blank price silently save as ₱0
        // (only entirely-blank new cards are meant to be forgiving). Named in
        // the message (not a generic "Every product needs a price") so a
        // failure on some OTHER row a save touches in passing doesn't read as
        // if the row actually being edited lost its price.
        $validator->after(function ($validator) use ($request) {
            foreach ((array) $request->input('products', []) as $k => $p) {
                $p = (array) $p;
                $name = trim((string) ($p['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $price = $p['price'] ?? '';
                if ($price === '' || $price === null) {
                    $validator->errors()->add("products.$k.price", "\"{$name}\" needs a price.");
                } elseif (! is_numeric($price) || (float) $price < 0.01) {
                    $validator->errors()->add("products.$k.price", "\"{$name}\"'s price must be greater than ₱0.");
                }
            }
        });
        $validator->validate();

        // Product ID is assign-once: current codes, keyed by row id, so updates
        // can't change a code that's already set (the form field is read-only,
        // but enforce it here too). Blank codes (legacy rows) may be filled in.
        $existingCodes = Product::whereNotNull('product_id')->pluck('product_id', 'id');

        $keptIds = [];
        foreach ((array) $request->input('products', []) as $p) {
            $p = (array) $p;
            $name = trim((string) ($p['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $type = ($p['type'] ?? 'single') === 'bundle' ? 'bundle' : 'single';
            $attrs = [
                'product_id' => trim((string) ($p['product_id'] ?? '')) ?: null,
                'name' => $name,
                'category' => trim((string) ($p['category'] ?? '')) ?: null,
                'price' => (float) ($p['price'] ?? 0),
                'original_price' => ($p['original_price'] ?? '') === '' ? null : (float) $p['original_price'],
                'description' => trim((string) ($p['description'] ?? '')) ?: null,
                'image_path' => trim((string) ($p['image_path'] ?? '')) ?: null,
                'features' => $this->linesToArray($p['features'] ?? ''),
                'calorie_info' => $this->calorieInfo($p),
                'net_weight' => trim((string) ($p['net_weight'] ?? '')) ?: null,
                'storage_condition' => trim((string) ($p['storage_condition'] ?? '')) ?: null,
                'serving_note' => trim((string) ($p['serving_note'] ?? '')) ?: null,
                'is_featured' => ! empty($p['is_featured']),
                'status' => ($p['status'] ?? '') !== '' ? $p['status'] : null,
                'type' => $type,
                // Only a bundle actually carries linked products — switching
                // back to Single drops any previously-picked links rather
                // than leaving stale ones the UI no longer shows. Keyed by
                // linked product id => quantity; zero/blank rows are dropped.
                'bundle_product_ids' => $type === 'bundle'
                    ? collect((array) ($p['bundle_product_ids'] ?? []))
                        ->map(fn ($qty) => (int) $qty)
                        ->filter(fn ($qty) => $qty > 0)
                        ->all()
                    : null,
            ];

            if (! empty($p['id'])) {
                if ($existingCodes->has($p['id'])) {
                    $attrs['product_id'] = $existingCodes[$p['id']];
                }
                Product::where('id', $p['id'])->update($attrs);
                $keptIds[] = $p['id'];
            } else {
                $keptIds[] = Product::create($attrs)->id;
            }
        }

        $removed = array_diff((array) $request->input('originalIds', []), $keptIds);
        if (! empty($removed)) {
            Product::whereIn('id', array_values($removed))->update(['archived_at' => now()]);
        }

        Cache::forget('products.index');

        return redirect()->route('admin.products.index')->with('status', 'Products saved.');
    }

    /** "One per line" textarea → clean array (same rule as the content editor). */
    private function linesToArray($text): array
    {
        if (is_array($text)) {
            return array_values(array_filter(array_map('trim', $text)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
    }

    /**
     * The calorie repeater's parallel calorie_amounts[]/calorie_units[]
     * arrays → [['amount' => int, 'unit' => string], ...]. Rows with a blank
     * amount are dropped (an editor removing a row, or a stray blank one).
     */
    private function calorieInfo(array $p): ?array
    {
        $amounts = (array) ($p['calorie_amounts'] ?? []);
        $units = (array) ($p['calorie_units'] ?? []);

        $entries = [];
        foreach ($amounts as $i => $amount) {
            if ($amount === '' || $amount === null) {
                continue;
            }
            $entries[] = [
                'amount' => (int) $amount,
                'unit' => trim((string) ($units[$i] ?? '')) ?: 'piece',
            ];
        }

        return $entries ?: null;
    }
}
