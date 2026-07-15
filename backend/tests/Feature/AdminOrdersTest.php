<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function session(string $email): array
    {
        return [
            'supabase_user' => ['id' => 'test-id', 'email' => $email, 'name' => 'Test', 'contact_number' => '0917'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    private function makeOrder(): Order
    {
        return Order::create([
            'id' => (string) Str::uuid(),
            'customer_name' => 'Cara Customer',
            'customer_email' => 'customer@example.com',
            'items' => [['product_id' => 1, 'name' => 'Ube Chiffon Cake', 'qty' => 1, 'price' => 720]],
            'subtotal' => 720, 'total' => 806.40, 'status' => 'pending',
        ]);
    }

    public function test_customers_cannot_open_the_orders_queue(): void
    {
        $this->withSession($this->session('customer@example.com'))
            ->get('/admin/orders')->assertForbidden();
    }

    public function test_cashiers_see_the_queue_by_role_default(): void
    {
        UserRole::create(['email' => 'cashier@bwsuperbakeshop.com', 'role' => 'cashier']);
        $order = $this->makeOrder();

        $this->withSession($this->session('cashier@bwsuperbakeshop.com'))
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Order #'.strtoupper(substr($order->id, 0, 8)), false)
            ->assertSee('Ube Chiffon Cake');
    }

    public function test_an_editor_granted_orders_access_can_open_the_queue(): void
    {
        // Editor role alone is blocked…
        $this->withSession($this->session('editor@bwsuperbakeshop.com'))
            ->get('/admin/orders')->assertForbidden();

        // …until an admin grants the 'orders' section.
        UserRole::create(['email' => 'editor@bwsuperbakeshop.com', 'role' => 'editor', 'permissions' => ['orders']]);

        $this->withSession($this->session('editor@bwsuperbakeshop.com'))
            ->get('/admin/orders')->assertOk();
    }

    public function test_status_and_payment_updates_persist(): void
    {
        $order = $this->makeOrder();

        $this->withSession($this->session('bw.redeem@gmail.com'))
            ->post("/admin/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertRedirect(route('admin.orders'));
        $this->assertSame('preparing', $order->fresh()->status);

        $this->withSession($this->session('bw.redeem@gmail.com'))
            ->post("/admin/orders/{$order->id}/payment", ['payment_status' => 'paid'])
            ->assertRedirect(route('admin.orders'));
        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
