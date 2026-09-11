<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecordsAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    use RecordsAuditLog;

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

        // Uploads happen before the editor form is saved, so they need their
        // own event to show staff exactly what entered the website.
        $this->audit($request, 'asset.uploaded', $file->getClientOriginalName(), 'Image added to the media library', [
            'File type' => $file->getMimeType(),
            'File size' => $this->humanFileSize((int) $file->getSize()),
        ]);

        return response()->json(['url' => $url]);
    }

    private function humanFileSize(int $bytes): string
    {
        return $bytes < 1024 * 1024
            ? number_format($bytes / 1024, 1).' KB'
            : number_format($bytes / (1024 * 1024), 1).' MB';
    }
}
