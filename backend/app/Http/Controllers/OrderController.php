<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderCreationService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(private OrderCreationService $orders) {}

    /**
     * Create an order for the signed-in user. The client sends only product
     * ids + quantities + an optional voucher code; every money field is
     * recomputed by OrderCreationService from the trusted products +
     * vouchers tables, so a tampered client can never dictate prices,
     * discounts, or totals. Shared byte-for-byte with the Blade checkout
     * (CheckoutController::store) via OrderCreationService.
     */
    public function store(Request $request)
    {
        $user = $this->supabaseUser($request);
        $data = $request->validate(OrderCreationService::rules());

        $order = $this->orders->create($data, $user);

        return response()->json($order, 201);
    }

    /** Public: which online payment methods are available (drives the UI). */
    public function paymentConfig(PayMongoService $paymongo)
    {
        return response()->json(['paymongo' => $paymongo->enabled()]);
    }

    /**
     * Start a PayMongo hosted checkout for an order the user owns. Returns the
     * checkout page URL for the frontend to redirect to.
     */
    public function pay(Request $request, string $id, PayMongoService $paymongo)
    {
        $user = $this->supabaseUser($request);
        $order = Order::findOrFail($id);

        if (($order->user_id ?? null) !== ($user['id'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        if (! $paymongo->enabled()) {
            return response()->json(['message' => 'Online payment is not available.'], 422);
        }
        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'This order is already paid.'], 422);
        }

        // Both success and cancel land back on the Blade checkout page (the
        // React checkout that used to live at frontend_url/checkout is gone).
        $success = route('checkout', ['payment' => 'success', 'order' => $order->id]);
        $cancel = route('checkout', ['payment' => 'cancelled', 'order' => $order->id]);

        try {
            $session = $this->orders->startPayment($order, $success, $cancel);
        } catch (\Throwable $e) {
            Log::error('PayMongo checkout failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Could not start the payment. Please try again.'], 502);
        }

        return response()->json(['checkout_url' => $session['url']]);
    }

    /**
     * Fetch a single order (owner or admin). For a pending PayMongo order this
     * also reconciles payment with the gateway — covers localhost where the
     * webhook can't reach us.
     */
    public function show(Request $request, string $id, PayMongoService $paymongo)
    {
        $user = $this->supabaseUser($request);
        $order = Order::findOrFail($id);

        $owner = ($order->user_id ?? null) === ($user['id'] ?? null);
        if (! $owner && ! $this->isStaff($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $this->orders->reconcile($order);
    }

    /** PayMongo webhook — marks the order paid when the gateway confirms. */
    public function paymongoWebhook(Request $request, PayMongoService $paymongo)
    {
        if (! $paymongo->verifyWebhook($request->header('Paymongo-Signature'), $request->getContent())) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $type = $request->json('data.attributes.type');
        $resource = $request->json('data.attributes.data');

        if (in_array($type, ['checkout_session.payment.paid', 'payment.paid'], true)) {
            $orderId = data_get($resource, 'attributes.metadata.order_id')
                ?? data_get($resource, 'attributes.reference_number');
            if ($orderId) {
                Order::where('id', $orderId)->update(['payment_status' => 'paid']);
            }
        }

        return response()->json(['received' => true]);
    }

    /** Current user's own orders, newest first. */
    public function mine(Request $request)
    {
        $user = $this->supabaseUser($request);

        return Order::where('user_id', $user['id'] ?? null)
            ->orderByDesc('created_at')
            ->get();
    }

    /** Staff (admin or cashier): every order, newest first. */
    public function index(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isStaff($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return Order::orderByDesc('created_at')->get();
    }

    /** Staff (admin or cashier): update an order's status. */
    public function updateStatus(Request $request, string $id)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isStaff($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,preparing,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $data['status']]);

        return $order;
    }

    /** Staff (admin or cashier): manually set an order's payment status (e.g. cash collected). */
    public function updatePaymentStatus(Request $request, string $id)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isStaff($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'payment_status' => 'required|in:paid,pending',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['payment_status' => $data['payment_status']]);

        return $order;
    }
}
