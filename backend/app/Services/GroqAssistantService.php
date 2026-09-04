<?php

namespace App\Services;

use App\Http\Controllers\LandingController;
use App\Models\Product;
use App\Models\SiteContent;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The public shop assistant's connection to Groq's (free-tier) OpenAI-compatible
 * chat API. Grounds the model in the live menu / store list / company copy via
 * a system prompt built from the same cached tables the rest of the app reads,
 * so it can answer company questions and recommend menu items without inventing
 * products or prices. Config-gated by `services.groq.key` (see config/services.php).
 *
 * There is no RAG here on purpose: the catalogue is a few dozen rows and the
 * company copy is one CMS blob, so the whole thing fits comfortably in one
 * prompt. Recompute cost is nil — the context block is cached 10 min.
 */
class GroqAssistantService
{
    private const BASE = 'https://api.groq.com/openai/v1/chat/completions';

    /** Marker the model appends when it recommends specific items; parsed out below. */
    public const PRODUCT_MARKER = '@@PRODUCTS:';

    /** Hard-coded persona for v1 (Site Editor control comes later). */
    private const PERSONA = <<<'TXT'
        You are "Moymoy", the cheerful chef-boy mascot of bw Superbakeshop, a
        Philippine bakery with branches nationwide. You're warm, upbeat, and a
        little playful. You help visitors learn about the shop and pick items
        from the menu.

        Rules:
        - Only discuss bw Superbakeshop: its menu, products, prices, branches,
          ordering, delivery/pickup, custom cakes, and company background. If asked
          about anything else, gently steer back to how you can help with the bakeshop.
        - Never invent products, prices, branches, or facts. Use ONLY the data given
          below. If you don't have the answer, say so and point them to the Contact
          page or a branch.
        - All prices are in Philippine Peso (₱), VAT included.
        - Keep replies short and friendly — 2 to 4 sentences. Use a plain, natural
          tone; light use of an emoji is fine, don't overdo it.
        - When you recommend specific menu items, end your reply with a final line
          in exactly this format (names copied verbatim from the DATA, separated by
          " | "), and nothing after it:
          @@PRODUCTS: Exact Product Name | Another Exact Product Name
          Only include items that appear in the DATA. Omit the line entirely if you
          are not recommending anything.
        - For a custom / personalized cake order, direct them to the "Order a custom
          cake" page (/custom-cake).
        TXT;

    public function enabled(): bool
    {
        return ! empty(config('services.groq.key'));
    }

    /**
     * Send the running transcript to Groq and return
     * ['reply' => string, 'source' => 'groq'|'fallback', 'productNames' => string[]].
     * Never throws — a gateway error yields a canned, still-useful reply.
     *
     * @param  array<int, array{role:string, content:string}>  $history
     */
    public function reply(array $history): array
    {
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt()]],
            $history,
        );

        $primary = config('services.groq.model');
        $fallback = config('services.groq.fallback_model');

        $text = $this->call($primary, $messages);

        // One retry on the smaller/faster model if the primary is rate-limited
        // or errored — the free tier's per-model limits are separate.
        if ($text === null && $fallback && $fallback !== $primary) {
            $text = $this->call($fallback, $messages);
            if ($text !== null) {
                return $this->parse($text, 'fallback');
            }
        }

        if ($text === null) {
            return [
                'reply' => "Sorry — I'm having trouble thinking right now. You can browse the full menu, "
                    ."find a branch on our Stores page, or reach the team through the Contact page.",
                'source' => 'fallback',
                'productNames' => [],
            ];
        }

        return $this->parse($text, 'groq');
    }

    /** One Groq call. Returns the assistant text, or null on any failure. */
    private function call(string $model, array $messages): ?string
    {
        try {
            $resp = Http::withToken(config('services.groq.key'))
                ->timeout(20)
                ->acceptJson()
                ->post(self::BASE, [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.4,
                    // Generous: gpt-oss models spend part of the budget on
                    // (discarded) reasoning tokens before emitting `content`.
                    'max_tokens' => 800,
                ]);

            if ($resp->failed()) {
                Log::warning('Groq assistant call failed', [
                    'model' => $model,
                    'status' => $resp->status(),
                    'body' => Str::limit($resp->body(), 300),
                ]);

                return null;
            }

            $text = trim((string) $resp->json('choices.0.message.content', ''));

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Groq assistant call threw', ['model' => $model, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Split the trailing "@@PRODUCTS: A | B" marker line off the reply text and
     * return the visible reply plus the raw product names for the controller to
     * resolve against the catalogue.
     */
    private function parse(string $text, string $source): array
    {
        $names = [];
        $pos = strripos($text, self::PRODUCT_MARKER);

        if ($pos !== false) {
            $line = substr($text, $pos + strlen(self::PRODUCT_MARKER));
            $text = rtrim(substr($text, 0, $pos));
            $names = array_values(array_filter(array_map(
                'trim',
                explode('|', preg_replace('/\s+/', ' ', $line)),
            )));
        }

        return ['reply' => trim($text), 'source' => $source, 'productNames' => $names];
    }

    /** Persona + the live-data block (cached 10 min, same TTL as products.index). */
    public function systemPrompt(): string
    {
        return self::PERSONA."\n\n".Cache::remember(
            'assistant.context',
            now()->addMinutes(10),
            fn () => $this->dataBlock(),
        );
    }

    private function dataBlock(): string
    {
        return implode("\n\n", [
            "=== DATA: MENU ===\n".$this->menuBlock(),
            "=== DATA: BRANCHES ===\n".$this->storeBlock(),
            "=== DATA: ORDERING & DELIVERY ===\n".$this->deliveryBlock(),
            "=== DATA: ABOUT THE SHOP ===\n".$this->aboutBlock(),
        ]);
    }

    private function menuBlock(): string
    {
        $rows = Product::whereNull('archived_at')
            ->orderBy('category')->orderBy('name')
            ->get(['name', 'category', 'price', 'original_price', 'status', 'type', 'description', 'features', 'calorie_info']);

        if ($rows->isEmpty()) {
            return '(no products listed yet)';
        }

        return $rows->map(function (Product $p) {
            $bits = ['- '.$p->name];
            $bits[] = '₱'.rtrim(rtrim(number_format((float) $p->price, 2), '0'), '.');
            if ($p->original_price && $p->original_price > $p->price) {
                $bits[] = '(was ₱'.rtrim(rtrim(number_format((float) $p->original_price, 2), '0'), '.').')';
            }
            if ($p->category) {
                $bits[] = 'category: '.$p->category;
            }
            if ($p->status) {
                $bits[] = str_replace('_', ' ', $p->status);
            }
            $cal = $p->calorie_info[0] ?? null;
            if (is_array($cal) && isset($cal['amount'])) {
                $bits[] = trim($cal['amount'].' '.($cal['unit'] ?? 'cal'));
            }
            $allergens = array_filter((array) $p->features);
            if ($allergens) {
                $bits[] = 'contains: '.implode(', ', $allergens);
            }
            if ($p->description) {
                $bits[] = '— '.Str::limit(trim($p->description), 160);
            }

            return implode(' · ', $bits);
        })->implode("\n");
    }

    private function storeBlock(): string
    {
        $rows = Store::orderBy('region')->orderBy('name')->get();

        if ($rows->isEmpty()) {
            return '(no branches listed yet)';
        }

        return $rows->map(function (Store $s) {
            $serves = match ($s->fulfillment) {
                'delivery' => 'delivery only',
                'pickup' => 'pickup only',
                default => 'pickup & delivery',
            };

            return trim("- {$s->name}"
                .($s->region ? " ({$s->region})" : '')
                .($s->address ? " — {$s->address}" : '')
                .($s->hours ? " · hours: {$s->hours}" : '')
                .($s->phone ? " · tel: {$s->phone}" : '')
                ." · {$serves}");
        })->implode("\n");
    }

    private function deliveryBlock(): string
    {
        $fee = OrderCreationService::DELIVERY_FEE;
        $free = OrderCreationService::FREE_DELIVERY_MIN;
        $express = OrderCreationService::EXPRESS_DELIVERY_FEE;
        $vat = (int) round(OrderCreationService::VAT_RATE * 100);

        return implode("\n", [
            "- Order online for pickup or delivery; choose a branch at checkout.",
            "- Standard delivery is ₱{$fee}, free for orders of ₱".number_format($free).' or more.',
            "- Express delivery is a flat ₱{$express} (never free).",
            "- Cash payment is pickup only. Online payment (GCash / card / Maya / GrabPay) and QR Ph are available where enabled.",
            "- All prices shown include {$vat}% VAT.",
            "- Custom / personalized cakes are arranged through the custom cake page (/custom-cake); guests can submit a request without an account.",
        ]);
    }

    private function aboutBlock(): string
    {
        $content = SiteContent::find(1)?->data ?? [];
        $about = (array) ($content['about'] ?? []);
        $story = (array) ($about['story'] ?? []);
        $values = (array) (($about['values'] ?? [])['items'] ?? []);

        $lines = [];
        $lines[] = $story['paragraph1']
            ?? 'bw Superbakeshop started as a small neighborhood bakeshop and has grown into a trusted bakery brand with branches nationwide, still baking fresh every day.';
        if (! empty($story['paragraph2'])) {
            $lines[] = $story['paragraph2'];
        }
        foreach ($values as $v) {
            if (! empty($v['title'])) {
                $lines[] = '- '.$v['title'].(! empty($v['text']) ? ': '.$v['text'] : '');
            }
        }
        $lines[] = 'More on the About page (/about) and the Contact page (/contact).';

        return implode("\n", $lines);
    }
}
