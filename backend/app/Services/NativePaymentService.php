<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;

class NativePaymentService
{
    public function __construct(private PayMongoService $paymongo) {}

    public function intent(Order $order): array
    {
        if ($order->payment_ref) {
            abort_unless(str_starts_with($order->payment_ref, 'pi_'), 409, 'This order uses a different payment flow.');
            $intent = $this->paymongo->getPaymentIntent($order->payment_ref);
            if (! $intent) throw new \RuntimeException('Payment provider unavailable');
        } else {
            $intent = $this->paymongo->createPaymentIntent($order);
            if (empty($intent['id']) || ! str_starts_with($intent['id'], 'pi_')) throw new \RuntimeException('Invalid payment intent');
            $order->update(['payment_ref' => $intent['id']]);
        }
        $this->assertMatches($order, $intent);
        return $intent;
    }

    private function assertMatches(Order $order, array $intent): void
    {
        if (($intent['id'] ?? null) !== $order->payment_ref
            || (int) data_get($intent, 'attributes.amount', -1) !== (int) round($order->total * 100)
            || data_get($intent, 'attributes.currency') !== 'PHP'
            || (string) data_get($intent, 'attributes.metadata.order_id') !== (string) $order->id) {
            throw new \RuntimeException('Payment does not match this order');
        }
    }

    public function state(Order $order, array $intent): array
    {
        $this->assertMatches($order, $intent);
        $status = data_get($intent, 'attributes.status');
        if ($status === 'succeeded' && $order->payment_status !== 'paid') {
            Order::whereKey($order->id)->where('payment_ref', $intent['id'])->update(['payment_status' => 'paid']);
            $order->refresh();
        }
        return [
            'status' => $status,
            'paid' => $order->payment_status === 'paid',
            'channel' => $order->payment_channel,
            'qr_url' => data_get($intent, 'attributes.next_action.code.image_url'),
            'authorization_url' => data_get($intent, 'attributes.next_action.redirect.url'),
            'error_message' => $status === 'awaiting_payment_method' && data_get($intent, 'attributes.last_payment_error')
                ? (PaymentErrorMessage::from((array) data_get($intent, 'attributes.last_payment_error')) ?? 'Payment was not completed. Try another payment method or contact your bank.') : null,
            'failed' => $status === 'awaiting_payment_method' && (bool) data_get($intent, 'attributes.last_payment_error'),
            'success_url' => route('my-orders', ['placed' => $order->id]),
        ];
    }

    public function pay(Order $order, string $channel, ?string $cardMethod): array
    {
        return Cache::lock('native-payment:'.$order->id, 45)->block(5, function () use ($order, $channel, $cardMethod) {
            $order->refresh();
            $intent = $this->intent($order);
            $status = data_get($intent, 'attributes.status');
            // Never create another charge while a wallet/QR/3DS action is still active.
            if ($order->payment_status === 'paid' || in_array($status, ['succeeded', 'processing', 'awaiting_next_action', 'cancelled', 'canceled'], true)) {
                return $this->state($order, $intent);
            }
            abort_unless($status === 'awaiting_payment_method', 409, 'This payment is not ready. Please check its status.');
            if ($channel === 'card') {
                abort_unless($cardMethod, 422, 'Please enter your card details.');
                $method = $this->paymongo->getPaymentMethod($cardMethod);
                abort_unless(data_get($method, 'attributes.type') === 'card', 422, 'Please use a valid card payment method.');
                $methodId = $cardMethod;
            } else {
                $methodId = $this->paymongo->createWalletMethod($order, $channel);
            }
            $order->update(['payment_channel' => $channel]);
            $intent = $this->paymongo->attachPaymentMethod($order->payment_ref, $methodId, route('checkout.payment.show', $order->id));
            return $this->state($order, $intent);
        });
    }

    public function reconcile(Order $order): Order
    {
        if ($order->payment_status !== 'paid') $this->state($order, $this->intent($order));
        return $order;
    }
}
