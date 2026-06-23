<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over the PayMongo REST API for hosted Checkout Sessions.
 * Card data never touches our server — the customer pays on PayMongo's page and
 * is redirected back; a webhook (and a return-time check) reconcile the order.
 */
class PayMongoService
{
    private const BASE = 'https://api.paymongo.com/v1';

    public function enabled(): bool
    {
        return ! empty(config('services.paymongo.secret'));
    }

    private function http()
    {
        // PayMongo uses HTTP Basic auth with the secret key as the username.
        return Http::withBasicAuth(config('services.paymongo.secret'), '')
            ->acceptJson()
            ->asJson();
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
     * Returns true when no webhook secret is configured (dev convenience).
     */
    public function verifyWebhook(?string $header, string $rawBody): bool
    {
        $secret = config('services.paymongo.webhook_secret');
        if (empty($secret)) {
            return true;
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
        $sig = $parts['li'] ?? ($parts['te'] ?? ''); // li = live, te = test
        if ($ts === '' || $sig === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $ts.'.'.$rawBody, $secret);

        return hash_equals($expected, $sig);
    }
}
