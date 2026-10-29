<?php

namespace App\Http\Controllers;

use App\Models\SiteRating;
use Illuminate\Http\Request;

class SiteRatingController extends Controller
{
    /** Store the short, voluntary site-experience rating from a visitor. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'page' => ['nullable', 'string', 'max:120'],
        ]);

        SiteRating::create([
            'user_id' => $this->optionalSupabaseUser($request)['id'] ?? null,
            'rating' => $validated['rating'],
            'page' => $validated['page'] ?? '/',
        ]);

        return response()->json(['message' => 'Thanks for your rating!'], 201);
    }
}
