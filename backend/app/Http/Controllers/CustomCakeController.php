<?php

namespace App\Http\Controllers;

use App\Models\CustomCakeRequest;
use App\Models\Order;
use App\Models\SiteContent;
use Illuminate\Http\Request;

/**
 * Public "Customize Your Cake" inquiry page — a 3-step wizard (cake details →
 * your info → review). Submissions are stored for the team to follow up with
 * a quote; no payment happens here. Guests may submit; a signed-in session
 * just prefills the "Your Info" step.
 */
class CustomCakeController extends Controller
{
    /**
     * The wizard's editable content (heading + the option lists), managed in
     * the Site Editor under content.customCakeForm. Public so the editor
     * pre-fills its form from the same defaults this page renders.
     */
    public const DEFAULT_FORM = [
        'eyebrow' => 'Customize',
        'title' => 'Customize Your Cake',
        'subtitle' => 'Tell us your dream cake — flavor, size, design — and we\'ll bake it to perfection. Fill in what you can; our team will follow up with a quote.',
        'occasions' => ['Birthday', 'Wedding', 'Anniversary', 'Graduation', 'Baby Shower', 'Other'],
        'flavors' => ['Chocolate', 'Ube', 'Mocha', 'Vanilla', 'Red Velvet', 'Pandan', 'Strawberry', 'Other'],
        'sizes' => ['6" Round (serves 6–8)', '8" Round (serves 10–12)', '10" Round (serves 16–20)', '2-Tier', '3-Tier'],
        'colors' => [
            ['name' => 'White', 'hex' => '#ffffff'],
            ['name' => 'Pink', 'hex' => '#f8a5c2'],
            ['name' => 'Red', 'hex' => '#d64545'],
            ['name' => 'Yellow', 'hex' => '#f5d76e'],
            ['name' => 'Mint', 'hex' => '#a8e6c1'],
            ['name' => 'Blue', 'hex' => '#7fb3f5'],
            ['name' => 'Purple', 'hex' => '#b39ddb'],
            ['name' => 'Chocolate', 'hex' => '#7b4a2d'],
        ],
    ];

    public function show(Request $request, \App\Services\SupabaseAuthService $auth)
    {
        $user = $this->optionalSupabaseUser($request);

        // The contact number lives in Supabase user_metadata (the session copy
        // can be stale or absent), so fetch it to prefill "Mobile Number" —
        // same as CheckoutController::show. Guests simply get an empty field.
        $token = (string) $request->session()->get('supabase_access_token');
        $contactNumber = $token !== ''
            ? (string) data_get($auth->fetchUser($token), 'user_metadata.contact_number', '')
            : '';

        // Site Editor live preview (?preview=1, editor session) shows the
        // unsaved draft; everyone else sees the saved blob.
        $content = $this->previewDraft($request) ?? (SiteContent::find(1)?->data ?? []);
        $form = array_merge(self::DEFAULT_FORM, (array) ($content['customCakeForm'] ?? []));
        // Guard the lists: saved-but-emptied lists fall back to the defaults
        // (an empty <select> would make the wizard useless), and colors are
        // normalized to name+hex rows.
        foreach (['occasions', 'flavors', 'sizes', 'colors'] as $list) {
            $form[$list] = array_values(array_filter((array) $form[$list])) ?: self::DEFAULT_FORM[$list];
        }
        $form['colors'] = array_map(fn ($c) => [
            'name' => (string) (((array) $c)['name'] ?? ''),
            'hex' => (string) (((array) $c)['hex'] ?? '#fbe3c4'),
        ], $form['colors']);

        // Addresses the customer has already used (past delivery orders +
        // cake requests) — offered as a dropdown so they don't retype.
        $savedAddresses = [];
        if (! empty($user['id'])) {
            $savedAddresses = Order::where('user_id', $user['id'])
                ->orderByDesc('created_at')->pluck('address')
                ->merge(CustomCakeRequest::where('user_id', $user['id'])
                    ->orderByDesc('created_at')->pluck('address'))
                ->map(fn ($a) => trim((string) $a))
                ->filter()->unique()->take(5)->values()->all();
        }

        return view('custom-cake', [
            'user' => $user,
            'nav' => $this->navConfig($content),
            'form' => $form,
            'contactNumber' => $contactNumber,
            'savedAddresses' => $savedAddresses,
            // Branch picker on the "Your Info" step — same cached list the
            // checkout page uses.
            'stores' => app(StoreController::class)->cachedList(),
            'editable' => $this->isEditablePreview($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:40',
            'occasion' => 'required|string|max:60',
            'needed_by' => 'required|date|after_or_equal:today',
            'flavor' => 'required|string|max:120',
            'size' => 'required|string|max:60',
            'frosting_color' => 'required|string|max:30',
            'delivery_type' => 'required|in:delivery,pickup',
            'address' => 'required_if:delivery_type,delivery|nullable|string|max:500',
            'fulfillment_branch' => 'required_if:delivery_type,pickup|nullable|string|max:120',
            'description' => 'required|string|min:10|max:5000',
            'reference_link' => 'nullable|url|max:1024',
            'reference_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ], [
            'occasion.required' => 'Please select an occasion.',
            'needed_by.required' => 'Please pick the date you need the cake by.',
            'flavor.required' => 'Please select a flavor.',
            'size.required' => 'Please select a size.',
            'frosting_color.required' => 'Please pick a frosting color.',
            'delivery_type.required' => 'Please choose delivery or pickup.',
            'address.required_if' => 'Please enter your delivery address.',
            'fulfillment_branch.required_if' => 'Please choose the branch to pick up at.',
            'description.required' => 'Please describe your dream cake so we can quote it.',
            'description.min' => 'Please tell us a little more about the cake (at least 10 characters).',
            'needed_by.after_or_equal' => 'The "needed by" date can\'t be in the past.',
            'reference_image.max' => 'The reference image must be 10MB or smaller.',
        ]);

        // Values from the mode not chosen shouldn't linger (typed an address,
        // then switched to pickup — or picked a branch, then switched back).
        if ($data['delivery_type'] === 'pickup') {
            $data['address'] = null;
        } else {
            $data['fulfillment_branch'] = null;
        }

        // Stored on the private disk — staff-only material, never web-served.
        $path = $request->file('reference_image')?->store('custom-cakes');

        CustomCakeRequest::create([
            ...collect($data)->except('reference_image')->all(),
            'reference_path' => $path,
            // Tie the request to the signed-in customer so it shows on
            // /my-orders; guest submissions stay null.
            'user_id' => $this->optionalSupabaseUser($request)['id'] ?? null,
        ]);

        return redirect()->route('custom-cake')->with('cc_success', true);
    }
}
