<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\SiteContent;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

/**
 * The single, authoritative order-pricing/creation path — shared by the
 * legacy JSON API (OrderController::store, used by routes/api.php) and the
 * Blade checkout (CheckoutController::store), so the two never drift.
 *
 * The client (JSON body or web form) only ever supplies product ids +
 * quantities and a handful of fulfillment choices; every money field is
 * recomputed here from the trusted products + vouchers tables, so a tampered
 * client can never dictate prices, discounts, or totals.
 */
class OrderCreationService
{
    // Pricing rules — kept in one place so the API and the Blade checkout can
    // never disagree on money math.
    public const VAT_RATE = 0.12;

    public const DELIVERY_FEE = 79;

    public const EXPRESS_DELIVERY_FEE = 149;

    public const FREE_DELIVERY_MIN = 1000;

    public function __construct(private PayMongoService $paymongo) {}

    /** Validation rules shared by every entry point that creates an order. */
    public static function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            // A line is either a plain product or a promo bundle (the linked
            // product ids of a saved Menu Promo slide — see resolveBundle()).
            'items.*.product_id' => 'required_without:items.*.bundle_products|string',
            'items.*.bundle_products' => 'sometimes|array|min:1',
            'items.*.bundle_products.*' => 'string',
            'items.*.qty' => 'required|integer|min:1',
            'voucher' => 'nullable|string',
            'payment_method' => 'nullable|in:qrph,cash,paymongo',
            'delivery_type' => 'nullable|in:delivery,pickup',
            'delivery_speed' => 'nullable|in:standard,express',
            'fulfillment_store_id' => 'nullable|integer',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Create an order for a (possibly guest) user from already-validated data
     * (see rules() above). Throws ValidationException on any business-rule
     * violation (sold out, unknown branch, cash-for-delivery, etc.) — callers
     * let it propagate so Laravel renders it the normal way for their context
     * (422 JSON for the API, redirect-back-with-errors for the web form).
     */
    public function create(array $data, ?array $user): Order
    {
        $deliveryType = $data['delivery_type'] ?? 'delivery';
        $payMethod = $data['payment_method'] ?? 'qrph';
        $deliverySpeed = $deliveryType === 'pickup' ? null : ($data['delivery_speed'] ?? 'standard');

        // Cash is only allowed for pickup orders.
        if ($payMethod === 'cash' && $deliveryType !== 'pickup') {
            throw ValidationException::withMessages([
                'payment_method' => 'Cash is only available for pickup orders.',
            ]);
        }

        // Every order must choose a fulfilling branch: the branch to pick up from,
        // or the branch that delivers. Resolve it against the trusted stores table,
        // enforce that the branch actually serves the chosen mode, and denormalize
        // its name + address onto the order so it stays displayable even if the
        // store later changes.
        $store = ! empty($data['fulfillment_store_id'])
            ? Store::find($data['fulfillment_store_id'])
            : null;
        if (! $store) {
            throw ValidationException::withMessages([
                'fulfillment_store_id' => $deliveryType === 'pickup'
                    ? 'Please choose a branch to pick up from.'
                    : 'Please choose a branch to deliver from.',
            ]);
        }
        $serves = $store->fulfillment ?? 'both';
        if ($serves !== 'both' && $serves !== $deliveryType) {
            throw ValidationException::withMessages([
                'fulfillment_store_id' => "\u{201c}{$store->name}\u{201d} doesn\u{2019}t offer {$deliveryType}.",
            ]);
        }
        $fulfillmentStoreId = $store->id;
        $fulfillmentBranch = $store->name.' — '.$store->address;
        // Manual QRPH is treated as paid up front; cash is collected at pickup;
        // PayMongo stays pending until the gateway confirms (webhook / return check).
        $payStatus = $payMethod === 'qrph' ? 'paid' : 'pending';

        // Trusted product lookup (non-archived only) — covers plain lines and
        // every product inside a bundle line.
        $ids = collect($data['items'])
            ->flatMap(fn ($l) => ! empty($l['bundle_products']) ? $l['bundle_products'] : [$l['product_id']])
            ->unique()->all();
        $products = Product::whereIn('id', $ids)->whereNull('archived_at')->get()->keyBy('id');

        $items = [];
        $subtotal = 0;
        foreach ($data['items'] as $line) {
            $qty = (int) $line['qty'];

            if (! empty($line['bundle_products'])) {
                $bundle = $this->resolveBundle((array) $line['bundle_products'], $products);
                $subtotal += $bundle['price'] * $qty;
                $items[] = $bundle + ['qty' => $qty];

                continue;
            }

            $product = $products->get($line['product_id']);
            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products are no longer available.',
                ]);
            }
            if ($product->status === 'sold_out') {
                throw ValidationException::withMessages([
                    'items' => "\u{201c}{$product->name}\u{201d} is sold out.",
                ]);
            }
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

        // Pickup orders never pay a delivery fee. Express delivery is a flat fee
        // regardless of the free-delivery threshold; standard delivery is free at
        // the threshold (or with a freedel voucher). (Previously the API's
        // delivery-fee calc ignored `delivery_speed` entirely and always charged
        // the standard ₱79 fee for any delivery order — fixed here so express
        // orders are actually billed the ₱149 express fee the checkout UI quotes.)
        if ($deliveryType === 'pickup') {
            $delivery = 0;
        } elseif ($deliverySpeed === 'express') {
            $delivery = self::EXPRESS_DELIVERY_FEE;
        } else {
            $delivery = $freeDelivery ? 0 : self::DELIVERY_FEE;
        }
        $vat = $discounted * self::VAT_RATE;
        $total = $discounted + $vat + $delivery;

        return Order::create([
            'user_id' => $user['id'] ?? null,
            'customer_name' => $user['name'] ?? ($user['email'] ? explode('@', $user['email'])[0] : 'Customer'),
            'customer_email' => $user['email'] ?? null,
            'customer_phone' => $data['phone'] ?? null,
            'items' => $items,
            'voucher' => $voucherCode,
            'payment_method' => $payMethod,
            'payment_status' => $payStatus,
            'delivery_type' => $deliveryType,
            'delivery_speed' => $deliverySpeed,
            'fulfillment_store_id' => $fulfillmentStoreId,
            'fulfillment_branch' => $fulfillmentBranch,
            'address' => $deliveryType === 'pickup' ? null : ($data['address'] ?? null),
            'notes' => $data['notes'] ?? null,
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'delivery' => round($delivery, 2),
            'vat' => round($vat, 2),
            'total' => round($total, 2),
            'status' => 'pending',
        ]);
    }

    /**
     * Verify a promo-bundle line against the *saved* Menu Promo content and
     * price it from there — the client only ever names the product ids, so a
     * tampered request can't invent a bundle or its discount. The submitted id
     * set must exactly match a saved slide's linked products (a promo edited
     * or removed since the cart was filled no longer applies), every component
     * must still be purchasable, and the charged price is the slide's saved
     * bundlePrice (falling back to the components' regular total when blank).
     *
     * @param  \Illuminate\Support\Collection<string, Product>  $products
     * @return array{product_id: null, bundle: true, name: string, price: float, products: array}
     */
    private function resolveBundle(array $ids, $products): array
    {
        $menuPromo = (array) ((SiteContent::find(1)?->data ?? [])['menuPromo'] ?? []);
        $wanted = collect($ids)->map(fn ($id) => (string) $id)->sort()->values()->all();

        $slide = ($menuPromo['enabled'] ?? true)
            ? collect((array) ($menuPromo['slides'] ?? []))->first(function ($s) use ($wanted) {
                $linked = collect((array) (((array) $s)['products'] ?? []))->map(fn ($id) => (string) $id)->sort()->values()->all();

                return $linked !== [] && $linked === $wanted;
            })
            : null;
        if (! $slide) {
            throw ValidationException::withMessages([
                'items' => 'That promo bundle is no longer available.',
            ]);
        }
        $slide = (array) $slide;

        $components = [];
        $regularTotal = 0.0;
        foreach ($ids as $id) {
            $product = $products->get($id);
            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'A product in the promo bundle is no longer available.',
                ]);
            }
            if ($product->status === 'sold_out') {
                throw ValidationException::withMessages([
                    'items' => "\u{201c}{$product->name}\u{201d} is sold out.",
                ]);
            }
            $regularTotal += (float) $product->price;
            $components[] = ['product_id' => $product->id, 'name' => $product->name];
        }

        $bundlePrice = (float) ($slide['bundlePrice'] ?? 0);

        return [
            'product_id' => null,
            'bundle' => true,
            'name' => trim((string) ($slide['title'] ?? '')) ?: 'Promo bundle',
            'price' => $bundlePrice > 0 ? $bundlePrice : $regularTotal,
            'products' => $components,
        ];
    }

    /**
     * Start a PayMongo hosted checkout for an order and remember the session
     * ref. Callers are responsible for their own authorization/availability
     * guards (ownership, PayMongo enabled, not already paid) beforehand, so
     * each entry point (JSON API vs. web redirect) can keep its own
     * response shape for those failures instead of this shared step dictating
     * one for both.
     */
    public function startPayment(Order $order, string $successUrl, string $cancelUrl): array
    {
        $session = $this->paymongo->createCheckoutSession($order, $successUrl, $cancelUrl);
        $order->update(['payment_ref' => $session['id'], 'payment_method' => 'paymongo']);

        return $session;
    }

    /**
     * Reconcile a pending PayMongo order with the gateway — covers
     * environments where the webhook can't reach us (e.g. localhost). Safe to
     * call on any order; a no-op unless it's unpaid with a PayMongo ref.
     */
    public function reconcile(Order $order): Order
    {
        if ($order->payment_status !== 'paid' && $order->payment_ref && $this->paymongo->enabled()) {
            $session = $this->paymongo->getCheckoutSession($order->payment_ref);
            if ($this->paymongo->sessionIsPaid($session)) {
                $order->update(['payment_status' => 'paid']);
            }
        }

        return $order;
    }
}
