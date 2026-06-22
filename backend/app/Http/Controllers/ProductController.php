<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    /**
     * Public menu / editor list: every non-archived product. Returns rows in
     * the same snake_case shape the frontend's normalizeProduct() expects.
     */
    public function index()
    {
        return Product::whereNull('archived_at')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Editor save (admin/editor only). Body: { products: [...], originalIds: [...] }.
     * Updates rows that carry an id, inserts those without one (DB assigns the
     * UUID), and archives any originalId no longer present — never hard-deletes.
     */
    public function sync(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isEditor($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'products' => 'present|array',
            'products.*.id' => 'nullable|string',
            'products.*.name' => 'required|string',
            'products.*.category' => 'nullable|string',
            'products.*.price' => 'nullable|numeric',
            'products.*.original_price' => 'nullable|numeric',
            'products.*.description' => 'nullable|string',
            'products.*.image_path' => 'nullable|string',
            'products.*.features' => 'nullable|array',
            'products.*.calories' => 'nullable|integer',
            'products.*.is_featured' => 'nullable|boolean',
            'products.*.status' => 'nullable|string',
            'originalIds' => 'nullable|array',
            'originalIds.*' => 'string',
        ]);

        $keptIds = [];
        foreach ($data['products'] as $p) {
            $attrs = [
                'name' => $p['name'],
                'category' => $p['category'] ?? null,
                'price' => $p['price'] ?? 0,
                'original_price' => $p['original_price'] ?? null,
                'description' => $p['description'] ?? null,
                'image_path' => $p['image_path'] ?? null,
                'features' => $p['features'] ?? [],
                'calories' => $p['calories'] ?? null,
                'is_featured' => $p['is_featured'] ?? false,
                'status' => $p['status'] ?? null,
            ];

            if (! empty($p['id'])) {
                Product::where('id', $p['id'])->update($attrs);
                $keptIds[] = $p['id'];
            } else {
                $created = Product::create($attrs);
                $keptIds[] = $created->id;
            }
        }

        $removed = array_diff($data['originalIds'] ?? [], $keptIds);
        if (! empty($removed)) {
            Product::whereIn('id', array_values($removed))
                ->update(['archived_at' => Carbon::now()]);
        }

        return $this->index();
    }
}
