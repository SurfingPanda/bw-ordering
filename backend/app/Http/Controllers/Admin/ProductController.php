<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SiteContentController as PublicSiteContentController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'products.*.calories' => 'nullable|integer|min:0',
            'products.*.status' => ['nullable', Rule::in(['new', 'best_seller', 'bundle', 'sold_out'])],
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
        $request->validate($rules, [
            'products.*.product_id.distinct' => 'Product ID :input is used by more than one product.',
            'products.*.product_id.unique' => 'Product ID :input is already taken by another product.',
            'products.*.product_id.max' => 'Product ID must be 20 characters or fewer.',
        ]);

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

            $attrs = [
                'product_id' => trim((string) ($p['product_id'] ?? '')) ?: null,
                'name' => $name,
                'category' => trim((string) ($p['category'] ?? '')) ?: null,
                'price' => (float) ($p['price'] ?? 0),
                'original_price' => ($p['original_price'] ?? '') === '' ? null : (float) $p['original_price'],
                'description' => trim((string) ($p['description'] ?? '')) ?: null,
                'image_path' => trim((string) ($p['image_path'] ?? '')) ?: null,
                'features' => $this->linesToArray($p['features'] ?? ''),
                'calories' => ($p['calories'] ?? '') === '' ? null : (int) $p['calories'],
                'is_featured' => ! empty($p['is_featured']),
                'status' => ($p['status'] ?? '') !== '' ? $p['status'] : null,
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
}
