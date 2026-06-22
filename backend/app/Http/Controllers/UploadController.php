<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Admin/editor: upload a CMS/product image to the public disk and return
     * its absolute URL (replaces the old Supabase "site-images" bucket).
     */
    public function store(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isEditor($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'file' => 'required|file|image|max:8192', // 8 MB
        ]);

        $file = $request->file('file');
        $name = Str::random(8).'-'.time().'.'.$file->getClientOriginalExtension();
        $file->storeAs('uploads', $name, 'public');

        // Absolute URL so the frontend (different origin/port) can load it.
        $url = $request->getSchemeAndHttpHost().Storage::url('uploads/'.$name);

        return response()->json(['url' => $url]);
    }
}
