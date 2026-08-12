<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\SiteContent;
use Illuminate\Http\Request;

/**
 * Public "Contact Us" page — a message form (guests may send one too, no
 * login required) that lands in contact_messages for staff to review at
 * /admin/contact-messages. Same shape as CustomCakeController, minus the
 * multi-step wizard: this is a single simple form.
 */
class ContactController extends Controller
{
    public function show(Request $request)
    {
        $user = $this->optionalSupabaseUser($request);
        $content = SiteContent::find(1)?->data ?? [];

        return view('contact', [
            // Prefills the form for a signed-in visitor, same courtesy the
            // custom-cake wizard extends to logged-in users.
            'prefillName' => $user['name'] ?? '',
            'prefillEmail' => $user['email'] ?? '',
            'nav' => $this->navConfig($content),
            'footerContent' => array_merge(LandingController::DEFAULT_CONTENT['footer'], (array) ($content['footer'] ?? [])),
            'social' => (array) ($content['social'] ?? LandingController::DEFAULT_CONTENT['social']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'name.required' => 'Please tell us your name.',
            'email.email' => 'Please enter a valid email address.',
            'message.required' => "Let us know what's on your mind.",
            'message.min' => 'A few more details would help — at least 10 characters.',
        ]);

        ContactMessage::create($validated + [
            'user_id' => $this->optionalSupabaseUser($request)['id'] ?? null,
            'status' => 'new',
        ]);

        return redirect()->route('contact')->with('contact_success', true);
    }
}
