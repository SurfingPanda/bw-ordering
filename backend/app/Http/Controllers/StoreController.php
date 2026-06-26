<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * List all store branches for the locator. Public.
     */
    public function index()
    {
        return Store::orderBy('region')->orderBy('name')->get();
    }

    /**
     * Admin/editor save (Site Editor). Body: { stores: [...], originalIds: [...] }.
     * Updates rows with an id, inserts new ones, and deletes any removed id.
     */
    public function sync(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isEditor($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'stores' => 'present|array',
            'stores.*.id' => 'nullable',
            'stores.*.name' => 'required|string|max:255',
            'stores.*.region' => 'required|string|max:100',
            'stores.*.fulfillment' => 'nullable|in:both,pickup,delivery',
            'stores.*.address' => 'required|string|max:500',
            'stores.*.hours' => 'nullable|string|max:255',
            'stores.*.phone' => 'nullable|string|max:100',
            'stores.*.latitude' => 'required|numeric|between:-90,90',
            'stores.*.longitude' => 'required|numeric|between:-180,180',
            'originalIds' => 'nullable|array',
        ]);

        $keptIds = [];
        foreach ($data['stores'] as $s) {
            $attrs = [
                'name' => trim($s['name']),
                'region' => $s['region'],
                'fulfillment' => $s['fulfillment'] ?? 'both',
                'address' => trim($s['address']),
                'hours' => $s['hours'] ?? null,
                'phone' => $s['phone'] ?? null,
                'latitude' => $s['latitude'],
                'longitude' => $s['longitude'],
            ];
            if (! empty($s['id'])) {
                Store::where('id', $s['id'])->update($attrs);
                $keptIds[] = $s['id'];
            } else {
                $created = Store::create($attrs);
                $keptIds[] = $created->id;
            }
        }

        $removed = array_diff($data['originalIds'] ?? [], $keptIds);
        if (! empty($removed)) {
            Store::whereIn('id', array_values($removed))->delete();
        }

        return Store::orderBy('region')->orderBy('name')->get();
    }
}
