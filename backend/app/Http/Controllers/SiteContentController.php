<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;

class SiteContentController extends Controller
{
    /** Public: the single landing/franchise CMS blob (or {} if unset). */
    public function show()
    {
        $row = SiteContent::find(1);

        return response()->json($row?->data ?? (object) []);
    }

    /** Admin/editor: persist the CMS blob (single row, id = 1). */
    public function update(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isEditor($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->all();

        SiteContent::updateOrCreate(['id' => 1], ['data' => $data]);

        return response()->json(['ok' => true]);
    }
}
