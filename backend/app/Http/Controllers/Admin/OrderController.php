<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Orders queue in the Site Editor shell's Admin group — the Blade home for
 * what the JSON OrderController exposes to the old SPA (list every order,
 * update fulfillment/payment status). Open to admins, cashiers (role
 * default), and anyone granted the 'orders' section from Users & Roles.
 */
class OrderController extends Controller
{
    public const STATUSES = ['pending', 'preparing', 'completed', 'cancelled'];

    private function authorize(Request $request): void
    {
        $email = $this->supabaseUser($request)['email'] ?? null;
        abort_unless($this->canAccess($email, 'orders'), 403, 'Forbidden.');
    }

    public function index(Request $request)
    {
        $this->authorize($request);
        $email = $this->supabaseUser($request)['email'] ?? null;

        $filter = $request->query('status');
        $counts = Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')->all();

        $orders = Order::when(
            in_array($filter, self::STATUSES, true),
            fn ($q) => $q->where('status', $filter)
        )
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filter' => in_array($filter, self::STATUSES, true) ? $filter : null,
            'counts' => $counts,
            // Site Editor shell.
            'navCounts' => SiteContentController::navCounts(),
            'isAdminUser' => $this->isAdmin($email),
            'navAccess' => $this->editorNavAccess($email),
        ]);
    }

    /** Same rules as the JSON OrderController::updateStatus, with redirects. */
    public function updateStatus(Request $request, string $id)
    {
        $this->authorize($request);

        $data = $request->validate([
            'status' => 'required|in:'.implode(',', self::STATUSES),
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $data['status']]);

        return redirect()->route('admin.orders', array_filter(['status' => $request->input('filter')]))
            ->with('status', 'Order #'.strtoupper(substr($order->id, 0, 8))." marked {$data['status']}.");
    }

    /** Manually set payment (e.g. cash collected) — mirrors the JSON rules. */
    public function updatePayment(Request $request, string $id)
    {
        $this->authorize($request);

        $data = $request->validate([
            'payment_status' => 'required|in:paid,pending',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['payment_status' => $data['payment_status']]);

        return redirect()->route('admin.orders', array_filter(['status' => $request->input('filter')]))
            ->with('status', 'Order #'.strtoupper(substr($order->id, 0, 8)).' payment set to '.$data['payment_status'].'.');
    }
}
