<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;
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
            'payment_method' => 'nullable|in:qrph,cash',
            'delivery_type' => 'nullable|in:delivery,pickup',
            'delivery_speed' => 'nullable|in:standard,express',
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
        // QRPH is paid online up front; cash is collected at pickup.
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

    /** Current user's own orders, newest first. */
    public function mine(Request $request)
    {
        $user = $this->supabaseUser($request);

        return Order::where('user_id', $user['id'] ?? null)
            ->orderByDesc('created_at')
            ->get();
    }

    /** Admin: every order, newest first. */
    public function index(Request $request)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isAdmin($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return Order::orderByDesc('created_at')->get();
    }

    /** Admin: update an order's status. */
    public function updateStatus(Request $request, string $id)
    {
        $user = $this->supabaseUser($request);
        if (! $this->isAdmin($user['email'] ?? null)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:pending,preparing,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $data['status']]);

        return $order;
    }
}
