<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderCreationService;
use App\Services\PayMongoService;
use App\Services\QrphService;
use App\Services\SupabaseAuthService;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Blade replacement for the deleted React Checkout.jsx. The cart itself is
 * still handed over via localStorage (`bw_checkout`, written by the menu
 * page) — this controller only ever sees it once the browser submits the
 * checkout form (hidden `items_json` field), and re-validates everything via
 * the same OrderCreationService the JSON API uses, so a tampered client can
 * never dictate prices/discounts/totals here either.
 */
class CheckoutController extends Controller
{
    public function __construct(private OrderCreationService $orders) {}

    /**
     * Renders the checkout page. Also handles the two query-string states the
     * old SPA handled client-side:
     *  - ?placed=<id>            just placed a non-PayMongo order → confirmation
     *  - ?payment=success|cancelled&order=<id>   returning from PayMongo's
     *    hosted checkout — reconciles via the same OrderCreationService logic
     *    OrderController::show() uses for the JSON API.
     */
    public function show(Request $request, PayMongoService $paymongo, SupabaseAuthService $auth)
    {
        $user = $this->supabaseUser($request);

        // Staff accounts manage orders — they don't place them.
        if ($this->effectiveRole($user['email'] ?? null) !== null) {
            return redirect()->route('menu', ['staff' => 'blocked']);
        }

        // The session user only keeps id/email/name — the contact number lives
        // in Supabase user_metadata, so fetch it to prefill "Mobile Number".
        $token = (string) $request->session()->get('supabase_access_token');
        $contactNumber = $token !== ''
            ? (string) data_get($auth->fetchUser($token), 'user_metadata.contact_number', '')
            : '';

        $step = 'form';
        $order = null;
        $error = null;

        $status = $request->query('payment');
        $paymentOrderId = $request->query('order');
        $placedId = $request->query('placed');

        if ($status && $paymentOrderId) {
            $found = Order::find($paymentOrderId);
            if ($found && ($found->user_id ?? null) === ($user['id'] ?? null)) {
                if ($status === 'cancelled') {
                    $step = 'payment';
                    $error = 'Payment was cancelled. You can try again.';
                } else {
                    $found = $this->orders->reconcile($found);
                    if ($found->payment_status === 'paid') {
                        // Confirmation lives on My Orders (?placed= shows the
                        // "Order placed" notice and clears the client cart).
                        return redirect()->route('my-orders', ['placed' => $found->id]);
                    }
                    $step = 'payment';
                    $error = 'We couldn\u{2019}t confirm your payment yet. If you were charged, it will update shortly.';
                }
            }
        } elseif ($placedId) {
            $found = Order::find($placedId);
            if ($found && ($found->user_id ?? null) === ($user['id'] ?? null)) {
                return redirect()->route('my-orders', ['placed' => $found->id]);
            }
        }

        $content = (array) app(SiteContentController::class)->cachedData();

        return view('checkout', [
            'step' => $step,
            'order' => $order,
            'error' => $error,
            'stores' => app(StoreController::class)->cachedList(),
            'paymongoEnabled' => $paymongo->enabled(),
            'qrPayload' => data_get($content, 'payment.qrPayload', ''),
            'vouchers' => app(VoucherController::class)->active(),
            'user' => $user,
            'contactNumber' => $contactNumber,
        ]);
    }

    /**
     * Places the order. `items_json` is a hidden field the page's JS fills in
     * from localStorage['bw_checkout'] right before submit (product_id/qty
     * only — the server, via OrderCreationService, is the only source of
     * truth for prices). This is a normal (non-fetch) browser form POST so
     * that a payment_method=paymongo order's 302 to PayMongo is a *real* HTTP
     * redirect the browser follows on its own, not a JS-driven navigation.
     */
    public function store(Request $request, PayMongoService $paymongo)
    {
        $user = $this->supabaseUser($request);

        // Same guard as show() — staff accounts can't place orders.
        if ($this->effectiveRole($user['email'] ?? null) !== null) {
            return redirect()->route('menu', ['staff' => 'blocked']);
        }

        $items = json_decode((string) $request->input('items_json', '[]'), true);
        $payload = array_merge(
            $request->except('items_json'),
            ['items' => is_array($items) ? $items : []],
        );

        $data = validator($payload, OrderCreationService::rules())->validate();

        $order = $this->orders->create($data, $user);

        if (($data['payment_method'] ?? null) === 'paymongo') {
            if (! $paymongo->enabled()) {
                return redirect()->route('checkout')
                    ->withErrors(['payment_method' => 'Online payment is not available.']);
            }

            $success = route('checkout', ['payment' => 'success', 'order' => $order->id]);
            $cancel = route('checkout', ['payment' => 'cancelled', 'order' => $order->id]);

            try {
                $session = $this->orders->startPayment($order, $success, $cancel);
            } catch (\Throwable $e) {
                Log::error('PayMongo checkout failed', ['order' => $order->id, 'error' => $e->getMessage()]);

                return redirect()->route('checkout', ['payment' => 'cancelled', 'order' => $order->id])
                    ->withErrors(['payment_method' => 'Could not start the payment. Please try again.']);
            }

            return redirect()->away($session['url']);
        }

        return redirect()->route('my-orders', ['placed' => $order->id]);
    }

    /**
     * Renders a QRPH (EMVCo) PNG for the given amount — the merchant's
     * dynamic QR Ph when the Site Editor has configured one
     * (content.payment.qrPayload), otherwise a demo QR. Generated server-side
     * (via QrphService + endroid/qr-code) because there's no JS bundler left
     * to run the original client-side `qrcode` + qrph.js payload builder.
     */
    public function qr(Request $request)
    {
        $amount = (float) $request->query('amount', 0);

        $content = (array) app(SiteContentController::class)->cachedData();

        // An editor-uploaded QR image (content.payment.qrImage) wins over the
        // payload — it's served as-is, so the amount can't be baked in.
        $qrImage = trim((string) data_get($content, 'payment.qrImage', ''));
        if ($qrImage !== '') {
            return redirect($qrImage);
        }

        $qrPayload = data_get($content, 'payment.qrPayload', '');

        $merchantPayload = $qrPayload ? QrphService::buildPayload($qrPayload, $amount) : null;
        $text = $merchantPayload ?: sprintf('QRPH|BW Superbakeshop|PHP %.2f', $amount);

        $result = (new Builder)->build(data: $text, size: 220, margin: 1);

        return response($result->getString(), 200)->header('Content-Type', $result->getMimeType());
    }
}
