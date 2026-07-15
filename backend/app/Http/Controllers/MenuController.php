<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SiteContent;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Public product catalogue + cart. Reuses ProductController's cached,
     * non-archived product list (same `products.index` cache key as the API,
     * so admin/editor saves via ProductController::sync() bust this too). The
     * cart itself lives client-side in localStorage (`bw_cart`) — see
     * resources/views/menu.blade.php — OrderController/CheckoutController
     * re-validate everything server-side at submit time regardless.
     */
    public function index(Request $request, ProductController $products)
    {
        $user = $this->optionalSupabaseUser($request);
        $email = $user['email'] ?? null;
        // Site Editor live preview (?preview=1, editor session) shows the
        // unsaved draft; everyone else sees the saved blob.
        $content = $this->previewDraft($request) ?? (SiteContent::find(1)?->data ?? []);

        return view('menu', [
            'products' => $products->cachedList(),
            // Not gated by supabase.session (this page is public) — read
            // whatever session the login flow already established, if any,
            // purely to toggle the header between the account dropdown and
            // "back to home", and to show the admin/editor/cashier badge —
            // mirrors frontend/src/pages/Menu.jsx's MenuHeader.
            'user' => $user,
            'isAdmin' => $this->isAdmin($email),
            'isEditor' => $this->isEditor($email),
            'isCashier' => $this->isCashier($email),
            // Staff browse but don't buy — hides the checkout button (the
            // CheckoutController enforces the same rule server-side).
            'isStaffAccount' => $this->effectiveRole($email) !== null,
            // Editor-controlled "What's New" promo banner + category badge
            // images (content.menuPromo / content.menuCategoryImages) —
            // mirrors Menu.jsx's MenuPromoBanner and CategorySidebar.
            'menuPromo' => $content['menuPromo'] ?? ['enabled' => true, 'slides' => []],
            'categoryImages' => $content['menuCategoryImages'] ?? [],
            // Declared categories (Site Editor → Menu Categories) so the
            // sidebar also lists ones no product uses yet — same as the
            // landing grid (see LandingController::categoriesFrom).
            'declaredCategories' => array_values(array_filter((array) ($content['menuCategories'] ?? []))),
            // Badge on the account dropdown's "My Orders" — active = the same
            // pending/preparing set the My Orders page's Active tab uses.
            'activeOrders' => ($user['id'] ?? null)
                ? Order::where('user_id', $user['id'])->whereIn('status', ['pending', 'preparing'])->count()
                : 0,
        ]);
    }
}
