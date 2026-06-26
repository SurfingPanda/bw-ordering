<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Voucher;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    // Pricing rules — kept in sync with frontend src/pages/Menu.jsx.
    private const VAT_RATE = 0.12;
    private const DELIVERY_FEE = 79;
    private const FREE_DELIVERY_MIN = 1000;

    /**
     * Create an order for the signed-in user. The client sends only product
     * ids + quantities + an optional voucher code; every money field is
     * recomputed here from the trusted products + vouchers tables, so a
     * tampered client can never dictate prices, discounts, or totals.
     */
    public function store(Request $request)
    {
        $user = $this->supabaseUser($request);

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'voucher' => 'nullable|string',
            'payment_method' => 'nullable|in:qrph,cash,paymongo',
            'delivery_type' => 'nullable|in:delivery,pickup',
            'delivery_speed' => 'nullable|in:standard,express',
            'pickup_store_id' => 'nullable|integer',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $deliveryType = $data['delivery_type'] ?? 'delivery';
        $payMethod = $data['payment_method'] ?? 'qrph';

        // Cash is only allowed for pickup orders.
        if ($payMethod === 'cash' && $deliveryType !== 'pickup') {
            throw ValidationException::withMessages([
                'payment_method' => 'Cash is only available for pickup orders.',
            ]);
        }

        // Pickup orders must choose a branch to collect from. Resolve it against
        // the trusted stores table and denormalize its name + address onto the
        // order so it stays displayable even if the store later changes.
        $pickupStoreId = null;
        $pickupBranch = null;
        if ($deliveryType === 'pickup') {
            $store = ! empty($data['pickup_store_id'])
                ? Store::find($data['pickup_store_id'])
                : null;
            if (! $store) {
                throw ValidationException::withMessages([
                    'pickup_store_id' => 'Please choose a branch to pick up from.',
                ]);
            }
            $pickupStoreId = $store->id;
            $pickupBranch = $store->name.' — '.$store->address;
        }
        // Manual QRPH is treated as paid up front; cash is collected at pickup;
        // PayMongo stays pending until the gateway confirms (webhook / return check).
        $payStatus = $payMethod === 'qrph' ? 'paid' : 'pending';

        // Trusted product lookup (non-archived only).
        $ids = collect($data['items'])->pluck('product_id')->unique()->all();
        $products = Product::whereIn('id', $ids)->whereNull('archived_at')->get()->keyBy('id');

        $items = [];
        $subtotal = 0;
        foreach ($data['items'] as $line) {
            $product = $products->get($line['product_id']);
            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products are no longer available.',
                ]);
            }
            if ($product->status === 'sold_out') {
                throw ValidationException::withMessages([
                    'items' => "“{$product->name}” is sold out.",
                ]);
            }
            $qty = (int) $line['qty'];
            $price = (float) $product->price;
            $subtotal += $price * $qty;
            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'qty' => $qty,
                'price' => $price,
            ];
        }

        // Voucher discount (validated against the DB — forged codes are ignored).
        $discount = 0;
        $voucherCode = null;
        $freeDelivery = $subtotal >= self::FREE_DELIVERY_MIN;
        if (! empty($data['voucher'])) {
            $voucher = Voucher::where('code', strtoupper(trim($data['voucher'])))
                ->where('active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
                })
                ->first();
            if ($voucher) {
                $voucherCode = $voucher->code;
                if ($voucher->type === 'percent') {
                    $discount = $subtotal * ($voucher->value / 100);
                } elseif ($voucher->type === 'amount') {
                    $discount = min($voucher->value, $subtotal);
                } elseif ($voucher->type === 'freedel') {
                    $freeDelivery = true;
                }
            }
        }

        $discounted = $subtotal - $discount;
        // Pickup orders never pay a delivery fee.
        $delivery = ($deliveryType === 'pickup' || $freeDelivery) ? 0 : self::DELIVERY_FEE;
        $vat = $discounted * self::VAT_RATE;
        $total = $discounted + $vat + $delivery;

        $order = Order::create([
            'user_id' => $user['id'] ?? null,
            'customer_name' => $user['name'] ?? ($user['email'] ? explode('@', $user['email'])[0] : 'Customer'),
            'customer_email' => $user['email'] ?? null,
            'customer_phone' => $data['phone'] ?? null,
            'items' => $items,
            'voucher' => $voucherCode,
            'payment_method' => $payMethod,
            'payment_status' => $payStatus,
            'delivery_type' => $deliveryType,
            'delivery_speed' => $deliveryType === 'pickup' ? null : ($data['delivery_speed'] ?? 'standard'),
            'pickup_store_id' => $pickupStoreId,
            'pickup_branch' => $pickupBranch,
            'address' => $deliveryType === 'pickup' ? null : ($data['address'] ?? null),
            'notes' => $data['notes'] ?? null,
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'delivery' => round($delivery, 2),
            'vat' => round($vat, 2),
            'total' => round($total, 2),
            'status' => 'pending',
        ]);

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

        $front = rtrim(config('services.frontend_url'), '/');
        $success = $front.'/checkout?payment=success&order='.$order->id;
        $cancel = $front.'/checkout?payment=cancelled&order='.$order->id;

        try {
            $session = $paymongo->createCheckoutSession($order, $success, $cancel);
        } catch (\Throwable $e) {
            Log::error('PayMongo checkout failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Could not start the payment. Please try again.'], 502);
        }

        $order->update(['payment_ref' => $session['id'], 'payment_method' => 'paymongo']);

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

        if ($order->payment_status !== 'paid' && $order->payment_ref && $paymongo->enabled()) {
            $session = $paymongo->getCheckoutSession($order->payment_ref);
            if ($paymongo->sessionIsPaid($session)) {
                $order->update(['payment_status' => 'paid']);
            }
        }

        return $order;
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
