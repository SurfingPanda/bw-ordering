<?php

namespace App\Http\Controllers;

use App\Models\AssistantMessage;
use App\Models\Product;
use App\Services\GroqAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Backs the public shop-assistant widget (partials/assistant-widget.blade.php),
 * mounted on every marketing page. POST /assistant/chat takes the running
 * transcript the widget holds client-side and returns the next assistant reply
 * plus any recommended products (rendered as add-to-cart cards).
 *
 * A small rule-based fast layer answers the most common, unambiguous questions
 * straight from the database with NO Groq call — this keeps the free-tier daily
 * quota for the open-ended conversations that actually need it. Everything else
 * goes to GroqAssistantService, which itself degrades to a canned reply if the
 * gateway is down. Config-gated: no GROQ_API_KEY ⇒ this route 404s and the
 * widget never renders.
 */
class AssistantController extends Controller
{
    public function __construct(private readonly GroqAssistantService $groq) {}

    public function chat(Request $request)
    {
        abort_unless($this->groq->enabled(), 404);
        // Editors can switch the assistant off site-wide (Site Editor → Buttons
        // → "Moymoy AI Assistant"). Keep the endpoint in lockstep with the
        // widget so "disabled" isn't just "hidden".
        $settings = (array) app(SiteContentController::class)->cachedData();
        abort_if(($settings['assistant']['enabled'] ?? true) === false, 404);

        $data = $request->validate([
            'conversation_id' => ['nullable', 'string', 'alpha_dash', 'max:40'],
            'messages' => ['required', 'array', 'min:1', 'max:24'],
            'messages.*.role' => ['required', 'string', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:2000'],
        ]);

        // Normalise: trim, keep only the recent window, ensure the last turn is
        // a non-empty user message (the thing we're actually answering).
        $history = collect($data['messages'])
            ->map(fn ($m) => ['role' => $m['role'], 'content' => trim($m['content'])])
            ->filter(fn ($m) => $m['content'] !== '')
            ->values()
            ->take(-16)
            ->all();

        $last = end($history) ?: null;
        if (! $last || $last['role'] !== 'user') {
            return response()->json(['message' => 'No question to answer.'], 422);
        }

        $conversationId = $data['conversation_id'] ?? Str::random(32);
        $user = $this->optionalSupabaseUser($request);

        // 1. Rule-based fast path (no API call).
        $result = $this->ruleAnswer($last['content']);

        // 2. Otherwise ask Groq.
        if ($result === null) {
            $r = $this->groq->reply($history);
            $result = [
                'reply' => $r['reply'],
                'products' => $this->resolveProducts($r['productNames']),
                'source' => $r['source'],
            ];
        }

        $this->log($conversationId, $user, $last['content'], $result);

        return response()->json([
            'conversation_id' => $conversationId,
            'reply' => $result['reply'],
            'products' => $result['products'],
            'source' => $result['source'],
        ]);
    }

    /**
     * Answer the handful of high-frequency, low-ambiguity questions directly.
     * Returns null when nothing matches (⇒ hand off to Groq). Only fires on
     * short messages so a nuanced question still reaches the model.
     *
     * @return array{reply:string, products:array, source:string}|null
     */
    private function ruleAnswer(string $text): ?array
    {
        $t = Str::lower($text);
        if (Str::length($t) > 140) {
            return null;
        }

        $has = fn (string $re) => (bool) preg_match($re, $t);

        // Branches / hours / where are you.
        if ($has('/\b(branch|branches|store location|store hours|opening hours|what time.*(open|close)|nearest (branch|store)|where.*(located|your (store|shop|branch)))\b/')) {
            return $this->canned(
                "We have branches nationwide — you can search for the one nearest you on our Stores page (/stores), "
                ."with each branch's address and opening hours. Want online delivery or pickup instead?"
            );
        }

        // Delivery fee / shipping.
        if ($has('/\b(delivery fee|delivery charge|shipping fee|how much.*(deliver|delivery|shipping)|free delivery|delivery cost)\b/')) {
            return $this->canned(
                "Standard delivery is ₱79, and it's free for orders of ₱1,000 or more. Express delivery is a flat ₱149. "
                ."Cash payment is pickup only. All prices include 12% VAT."
            );
        }

        // Payment methods.
        if ($has('/\b(payment method|how (can|do) i pay|mode of payment|do you accept (gcash|card|cash|maya)|payment option)\b/')) {
            return $this->canned(
                "You can pay online with GCash, card, Maya, or GrabPay, or by QR Ph, where those are enabled. "
                ."Cash is available for pickup orders. Everything is confirmed at checkout."
            );
        }

        // Custom cakes.
        if ($has('/\b(custom cake|customi[sz]ed cake|personali[sz]ed cake|design my own cake|cake for.*(birthday|wedding|occasion).*order)\b/')) {
            return $this->canned(
                "For a custom cake, head to our custom cake page (/custom-cake) — you can pick the occasion, flavour, "
                ."size and design, and send a reference photo. Guests can submit a request without an account."
            );
        }

        // "Show me <keyword>" style menu filter — only with a concrete keyword.
        if ($has('/\b(show me|do you (have|sell|make)|looking for|any )\b/')) {
            $products = $this->keywordProducts($t);
            if ($products->isNotEmpty()) {
                return [
                    'reply' => 'Here are a few from our menu that might fit — tap one to add it to your cart:',
                    'products' => $products->all(),
                    'source' => 'rules',
                ];
            }
        }

        return null;
    }

    private function canned(string $reply): array
    {
        return ['reply' => $reply, 'products' => [], 'source' => 'rules'];
    }

    /**
     * Products matching a "show me <keyword>" query. A query word that names a
     * menu category — singular or plural, e.g. "cakes", "cupcake", "pastries" —
     * is treated as a category browse and matches ONLY that category, so
     * "show me your cakes" returns Cake-category items and can no longer
     * substring-hit "Chocolate Cupcakes". Any other word is matched as a whole
     * word (optional trailing "s") against the name / category / description —
     * never a bare substring, which is what used to leak "cakes" into "cup·cakes".
     */
    private function keywordProducts(string $lowerText)
    {
        $stop = ['show', 'me', 'do', 'you', 'have', 'sell', 'make', 'looking', 'for', 'any', 'the', 'a', 'an', 'some',
            'with', 'and', 'or', 'please', 'want', 'need', 'your', 'got', 'that', 'this'];
        $words = array_values(array_filter(
            preg_split('/[^a-z]+/', $lowerText),
            fn ($w) => strlen($w) >= 4 && ! in_array($w, $stop, true),
        ));
        if (! $words) {
            return collect();
        }

        $catalogue = $this->cachedProducts();

        // Known categories, reduced to singular form for matching.
        $catSingulars = $catalogue->pluck('category')
            ->filter()
            ->map(fn ($c) => Str::singular(Str::lower($c)))
            ->unique();
        $wantCategories = collect($words)
            ->map(fn ($w) => Str::singular($w))
            ->filter(fn ($w) => $catSingulars->contains($w))
            ->unique()
            ->values();

        return $catalogue
            ->filter(function (Product $p) use ($words, $wantCategories) {
                if ($wantCategories->isNotEmpty()) {
                    return $wantCategories->contains(Str::singular(Str::lower((string) $p->category)));
                }
                $hay = Str::lower($p->name.' '.$p->category.' '.$p->description);
                foreach ($words as $w) {
                    if (preg_match('/\b'.preg_quote($w, '/').'s?\b/', $hay)) {
                        return true;
                    }
                }

                return false;
            })
            ->take(4)
            ->map(fn (Product $p) => $this->card($p))
            ->values();
    }

    /**
     * Turn model-emitted product names into add-to-cart cards. Exact
     * (case-insensitive) match wins; falls back to a contains match so a
     * near-miss still resolves. Capped at 4.
     *
     * @param  array<int, string>  $names
     */
    private function resolveProducts(array $names): array
    {
        if (! $names) {
            return [];
        }
        $catalogue = $this->cachedProducts();
        $out = collect();

        foreach ($names as $name) {
            $needle = Str::lower(trim($name));
            if ($needle === '') {
                continue;
            }
            $match = $catalogue->first(fn (Product $p) => Str::lower($p->name) === $needle)
                ?? $catalogue->first(fn (Product $p) => str_contains(Str::lower($p->name), $needle));
            if ($match && ! $out->contains('name', $match->name)) {
                $out->push($this->card($match));
            }
            if ($out->count() >= 4) {
                break;
            }
        }

        return $out->values()->all();
    }

    private function card(Product $p): array
    {
        return [
            // The widget adds straight into the `bw_cart` localStorage map
            // (keyed by product id, same shape /menu writes) so a shopper can
            // keep chatting without leaving the conversation.
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->price,
            'image' => $p->image_path,
            // Fallback path for older cached cards without an id — reuses
            // /menu's ?add=<name> deep-add (see menu.blade.php).
            'url' => '/menu?add='.rawurlencode($p->name),
        ];
    }

    private function cachedProducts()
    {
        return Cache::remember(
            'assistant.products',
            now()->addMinutes(10),
            fn () => Product::whereNull('archived_at')
                ->get(['id', 'name', 'category', 'description', 'price', 'image_path']),
        );
    }

    /** Persist the visitor's message and the assistant's answer. Never fatal. */
    private function log(string $conversationId, ?array $user, string $question, array $result): void
    {
        try {
            $base = [
                'conversation_id' => $conversationId,
                'user_id' => $user['id'] ?? null,
                'user_email' => $user['email'] ?? null,
            ];
            AssistantMessage::insert([
                $base + ['role' => 'user', 'content' => Str::limit($question, 2000, ''), 'source' => null,
                    'created_at' => now(), 'updated_at' => now()],
                $base + ['role' => 'assistant', 'content' => Str::limit($result['reply'], 4000, ''), 'source' => $result['source'],
                    'created_at' => now(), 'updated_at' => now()],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
