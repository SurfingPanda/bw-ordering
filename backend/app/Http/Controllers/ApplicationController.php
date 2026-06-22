<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    /**
     * Public: upload a resume/CV to the PRIVATE local disk. Returns the stored
     * filename, which the applicant then sends with submitApplication().
     * (Replaces the private Supabase "resumes" bucket.)
     */
    public function uploadResume(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:8192',
        ]);

        $file = $request->file('file');
        $name = Str::random(10).'-'.time().'.'.$file->getClientOriginalExtension();
        $file->storeAs('resumes', $name, 'local'); // private disk (storage/app/private)

        return response()->json(['path' => $name]);
    }

    /** Public: submit a job application. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'required|string|max:255',
            'cv_url' => 'nullable|string|max:255',
        ]);

        $application = Application::create($data);

        return response()->json($application, 201);
    }

    /** Admin/HR: list applications, newest first. */
    public function index(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isHr($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return Application::orderByDesc('created_at')->get();
    }

    /**
     * Admin/HR: mint a short-lived signed URL to download a private resume.
     * The signature gates access, so window.open() works without a header.
     */
    public function resumeUrl(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isHr($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate(['path' => 'required|string']);
        $path = basename($request->query('path'));

        $url = URL::temporarySignedRoute('resumes.download', now()->addHour(), ['path' => $path]);

        return response()->json(['url' => $url]);
    }

    /** Signed download of a private resume (validated by the 'signed' middleware). */
    public function download(string $path)
    {
        $path = basename($path);
        if (! Storage::disk('local')->exists('resumes/'.$path)) {
            abort(404);
        }

        return Storage::disk('local')->download('resumes/'.$path);
    }
}
