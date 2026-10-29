<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\NativePaymentService;
use App\Services\OrderCreationService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckoutPaymentController extends Controller
{
    public function __construct(private NativePaymentService $payments, private PayMongoService $paymongo) {}

    private function owned(Request $request, string $id): Order
    {
        $order = Order::where('user_id', $this->supabaseUser($request)['id'])->findOrFail($id);
        abort_unless($order->payment_method === 'paymongo' && $order->checkout_token, 404);
        abort_if($order->status === 'cancelled', 409, 'This order has been canceled.');
        return $order;
    }

    public function create(Request $request, OrderCreationService $orders)
    {
        $user = $this->supabaseUser($request);
        abort_if($this->effectiveRole($user['email'] ?? null) !== null, 403);
        abort_unless($this->paymongo->enabled(), 422, 'Online payment is currently unavailable. Please use cash.');
        $content = (array) app(SiteContentController::class)->cachedData();
        abort_unless(\App\Models\SiteContent::buttonState((array) ($content['buttons'] ?? []), 'menuCheckout') === 'on', 503, 'Checkout is temporarily unavailable.');
        $data = $request->validate(array_merge(OrderCreationService::rules(), [
            'payment_method' => 'required|in:paymongo',
            'payment_channel' => 'required|in:gcash,card,qrph',
            'checkout_token' => 'required|uuid',
            'customer_name' => 'required|string|max:120',
            'customer_email' => 'required|email|max:254',
            'address' => 'required_if:delivery_type,delivery|nullable|string|max:500',
        ]));
        abort_unless(in_array($data['payment_channel'], $this->paymongo->nativeMethods(), true), 422, 'This payment method is currently unavailable.');
        $order = Cache::lock('native-checkout:'.$data['checkout_token'], 15)->block(5, function () use ($data, $orders, $user) {
            $existing = Order::where('checkout_token', $data['checkout_token'])->first();
            if ($existing) {
                abort_unless($existing->user_id === $user['id'], 403);
                return $existing;
            }
            return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $orders, $user) {
                $order = $orders->create($data, array_merge($user, ['name' => $data['customer_name'], 'email' => $data['customer_email']]));
                $order->update(['checkout_token' => $data['checkout_token'], 'payment_channel' => $data['payment_channel']]);
                return $order;
            });
        });
        return response()->json(['payment_url' => route('checkout.payment.show', $order->id), 'order_id' => $order->id]);
    }

    public function show(Request $request, string $id)
    {
        $order = $this->owned($request, $id);
        if ($order->payment_status === 'paid') return redirect()->route('my-orders', ['placed' => $order->id]);
        return response()->view('checkout-payment', [
            'order' => $order, 'publicKey' => $this->paymongo->publicKey(), 'methods' => $this->paymongo->nativeMethods(),
        ])->header('Cache-Control', 'no-store');
    }

    public function status(Request $request, string $id)
    {
        $order = $this->owned($request, $id);
        abort_unless($this->paymongo->enabled(), 422, 'Online payment is unavailable.');
        try {
            $state = Cache::lock('native-payment:'.$order->id, 45)->block(5, fn () => $this->payments->state($order, $this->payments->intent($order)));
            if (app()->environment('local') && $order->payment_channel === 'qrph') {
                \Illuminate\Support\Facades\Log::debug('QR status diagnostic', [
                    'order' => substr(hash('sha256', $order->id), 0, 12),
                    'intent' => substr(hash('sha256', (string) $order->payment_ref), 0, 12),
                    'status' => $state['status'], 'has_qr' => ! empty($state['qr_url']),
                ]);
            }
            return response()->json($state)->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            report($this->safeError($e));
            return response()->json(['message' => 'Could not check payment. Please try again.'], 502);
        }
    }

    public function pay(Request $request, string $id)
    {
        $order = $this->owned($request, $id);
        $data = $request->validate(['channel' => 'required|in:gcash,card,qrph', 'payment_method_id' => ['nullable', 'string', 'regex:/^pm_[a-zA-Z0-9]+$/']]);
        abort_unless(in_array($data['channel'], $this->paymongo->nativeMethods(), true), 422, 'This payment method is unavailable.');
        // Only a token reaches this endpoint; raw card details must never be accepted or logged.
        abort_if($request->hasAny(['card_number', 'cvc', 'card', 'details']), 422, 'Card details must be sent directly to the payment provider.');
        try {
            return response()->json($this->payments->pay($order, $data['channel'], $data['payment_method_id'] ?? null));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { throw $e; }
        catch (\Illuminate\Http\Client\RequestException $e) {
            $providerError = (array) $e->response->json('errors.0', []);
            $message = \App\Services\PaymentErrorMessage::from($providerError);
            if ($e->response->clientError() && $message) {
                return response()->json(['message' => $message, 'payment_failed' => true], 422);
            }
            report($this->safeError($e));
            return response()->json(['message' => 'We could not confirm your payment. Check payment status before trying again.'], 502);
        }
        catch (\Throwable $e) {
            report($this->safeError($e));
            return response()->json(['message' => 'Could not start payment. No confirmation has been received. Check payment status before trying again.'], 502);
        }
    }

    private function safeError(\Throwable $e): \RuntimeException
    {
        // HTTP exceptions can include provider payloads/client keys; keep those out of app logs.
        return new \RuntimeException('PayMongo request failed ('.class_basename($e).').');
    }
}
