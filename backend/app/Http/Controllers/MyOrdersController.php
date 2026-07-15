<?php

namespace App\Http\Controllers;

use App\Models\CustomCakeRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class MyOrdersController extends Controller
{
    /**
     * Customer-facing order history. Behind `supabase.session` (see
     * routes/web.php), so `supabaseUser()` is always present here. Tab
     * filtering (All/Active/Completed/Cancelled) and search are done
     * client-side in the Blade view — a customer's own order count is small
     * enough that a server round trip per filter isn't worth it.
     */
    public function index(Request $request, ProductController $products)
    {
        $user = $this->supabaseUser($request);

        $orders = Order::where('user_id', $user['id'] ?? null)
            ->orderByDesc('created_at')
            ->get();

        // Custom cake inquiries also belong here — matched by the id captured
        // at submission, plus an email fallback for requests sent while
        // signed out (the wizard is public).
        $cakeRequests = CustomCakeRequest::where(function ($q) use ($user) {
            $q->where('user_id', $user['id'] ?? '');
            if (! empty($user['email'])) {
                $q->orWhere('email', $user['email']);
            }
        })
            ->orderByDesc('created_at')
            ->get();

        // Order line items only store product_id/name/qty/price (no image), so
        // build a lookup back to the current product photo for display —
        // matches the original MyOrders.jsx `imgMap` (by id, falling back to
        // a lowercased name match for older orders placed before an id was
        // captured).
        $imgMap = [];
        foreach ($products->cachedList() as $product) {
            $imgMap[$product->id] = $product->image_path;
            $imgMap[strtolower($product->name)] = $product->image_path;
        }

        return view('my-orders', [
            'orders' => $orders,
            'imgMap' => $imgMap,
            'cakeRequests' => $cakeRequests,
        ]);
    }
}
