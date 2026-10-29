<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * PayMongo Payment Intents and legacy Checkout Sessions.
 * Card details are tokenized directly in the browser; the server only receives a token.
 */
class PayMongoService
{
    private const BASE = 'https://api.paymongo.com/v1';

    public function enabled(): bool
    {
        return ! empty(config('services.paymongo.secret'));
    }

    public function publicKey(): string
    {
        $key = (string) config('services.paymongo.public');
        return str_starts_with($key, 'pk_') ? $key : '';
    }

    public function nativeMethods(): array
    {
        return $this->enabled() ? array_values(array_filter(['gcash', 'card', 'qrph'],
            fn ($method) => $method !== 'card' || $this->publicKey() !== '')) : [];
    }

    private function http()
    {
        // PayMongo uses HTTP Basic auth with the secret key as the username.
        return Http::withBasicAuth(config('services.paymongo.secret'), '')
            ->acceptJson()
            ->asJson()->connectTimeout(5)->timeout(10);
    }

    public function createPaymentIntent(Order $order): array
    {
        $response = $this->http()->withHeaders(['Idempotency-Key' => 'bw-order-'.$order->id])
            ->post(self::BASE.'/payment_intents', ['data' => ['attributes' => [
                'amount' => (int) round($order->total * 100), 'currency' => 'PHP',
                'payment_method_allowed' => $this->nativeMethods(), 'capture_type' => 'automatic',
                'description' => 'BW Superbakeshop order '.$order->id,
                'metadata' => ['order_id' => (string) $order->id],
            ]]]);
        return $response->throw()->json('data');
    }

    public function getPaymentIntent(string $id): ?array
    {
        $response = $this->http()->get(self::BASE.'/payment_intents/'.rawurlencode($id));
        return $response->successful() ? $response->json('data') : null;
    }

    public function createWalletMethod(Order $order, string $channel): string
    {
        if (! in_array($channel, ['gcash', 'qrph'], true)) throw new \InvalidArgumentException('Unsupported wallet');
        $response = $this->http()->post(self::BASE.'/payment_methods', ['data' => ['attributes' => [
            'type' => $channel,
            'billing' => array_filter(['name' => $order->customer_name, 'email' => $order->customer_email, 'phone' => $order->customer_phone]),
        ]]]);
        return (string) $response->throw()->json('data.id');
    }

    public function getPaymentMethod(string $id): ?array
    {
        $response = $this->http()->get(self::BASE.'/payment_methods/'.rawurlencode($id));
        return $response->successful() ? $response->json('data') : null;
    }

    public function attachPaymentMethod(string $intentId, string $methodId, string $returnUrl): array
    {
        return $this->http()->post(self::BASE.'/payment_intents/'.rawurlencode($intentId).'/attach', ['data' => ['attributes' => [
            'payment_method' => $methodId, 'return_url' => $returnUrl,
        ]]])->throw()->json('data');
    }

    /**
     * Create a hosted Checkout Session for an order. Returns
     * ['id' => session id, 'url' => checkout page URL].
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $resp = $this->http()->post(self::BASE.'/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'payment_method_types' => array_values(config('services.paymongo.methods')),
                    'line_items' => [[
                        // PayMongo amounts are in centavos. One line item equal to the
                        // server-computed total guarantees the charge matches our books.
                        'name' => 'BW Superbakeshop order',
                        'amount' => (int) round($order->total * 100),
                        'currency' => 'PHP',
                        'quantity' => 1,
                    ]],
                    'description' => 'Order '.$order->id,
                    'reference_number' => (string) $order->id,
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'metadata' => ['order_id' => (string) $order->id],
                ],
            ],
        ]);

        $resp->throw();
        $data = $resp->json('data');

        return [
            'id' => $data['id'] ?? null,
            'url' => $data['attributes']['checkout_url'] ?? null,
        ];
    }

    /** Retrieve a Checkout Session (to verify payment on return). */
    public function getCheckoutSession(string $id): ?array
    {
        $resp = $this->http()->get(self::BASE.'/checkout_sessions/'.$id);
        if (! $resp->successful()) {
            return null;
        }

        return $resp->json('data');
    }

    /** True if a retrieved Checkout Session has a paid payment. */
    public function sessionIsPaid(?array $session): bool
    {
        $payments = $session['attributes']['payments'] ?? [];
        foreach ($payments as $p) {
            if (($p['attributes']['status'] ?? null) === 'paid') {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify a webhook signature. PayMongo sends "t=<ts>,te=<sig>,li=<sig>" in
     * the Paymongo-Signature header; the signed payload is "<ts>.<rawBody>".
     * Missing webhook configuration never counts as a verified payment.
     */
    public function verifyWebhook(?string $header, string $rawBody): bool
    {
        $secret = config('services.paymongo.webhook_secret');
        if (empty($secret)) {
            return false;
        }
        if (! $header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', $kv, 2), 2, '');
            $parts[trim($k)] = trim($v);
        }
        $ts = $parts['t'] ?? '';
        $mode = str_starts_with((string) config('services.paymongo.secret'), 'sk_live_') ? 'li' : 'te';
        $sig = $parts[$mode] ?? '';
        if (! ctype_digit($ts) || $sig === '' || abs(time() - (int) $ts) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $ts.'.'.$rawBody, $secret);

        return hash_equals($expected, $sig);
    }
}
