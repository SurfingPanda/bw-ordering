<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class XenditService
{
    private const BASE = 'https://api.xendit.co/v2';

    public function enabled(): bool
    {
        return ! empty(config('services.xendit.secret'));
    }

    private function http()
    {
        return Http::withBasicAuth(config('services.xendit.secret'), '')
            ->acceptJson()
            ->asJson();
    }

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): array
    {
        $payload = [
            'external_id' => (string) $order->id,
            'amount' => round($order->total, 2),
            'currency' => 'PHP',
            'description' => 'BW Superbakeshop order '.$order->id,
            'success_redirect_url' => $successUrl,
            'failure_redirect_url' => $cancelUrl,
            'metadata' => ['order_id' => (string) $order->id],
        ];

        if (! empty($order->customer_email)) {
            $payload['payer_email'] = $order->customer_email;
        }

        $methods = array_values(config('services.xendit.methods'));
        if ($methods) {
            $payload['payment_methods'] = $methods;
        }

        $resp = $this->http()->post(self::BASE.'/invoices', $payload);
        $resp->throw();

        return [
            'id' => $resp->json('id'),
            'url' => $resp->json('invoice_url'),
        ];
    }

    public function getInvoice(string $id): ?array
    {
        $resp = $this->http()->get(self::BASE.'/invoices/'.$id);
        if (! $resp->successful()) {
            return null;
        }

        return $resp->json();
    }

    public function invoiceIsPaid(?array $invoice): bool
    {
        return in_array($invoice['status'] ?? null, ['PAID', 'SETTLED'], true);
    }

    public function verifyWebhook(?string $token): bool
    {
        $expected = config('services.xendit.webhook_token');
        if (empty($expected)) {
            return true;
        }

        return is_string($token) && hash_equals($expected, $token);
    }
}
