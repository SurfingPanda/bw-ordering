<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyOrdersTest extends TestCase
{
    use RefreshDatabase;

    /** A session as EnsureSupabaseSession/SessionController would build it. */
    private function customerSession(array $userOverrides = []): array
    {
        return [
            'supabase_user' => array_merge([
                'id' => 'user-1',
                'email' => 'customer@example.com',
                'name' => 'Cara Customer',
                'contact_number' => '0917 123 4567',
            ], $userOverrides),
            'supabase_access_token' => 'token-1',
            'supabase_refresh_token' => 'refresh-1',
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => 'user-1',
            'customer_name' => 'Cara Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '0917 123 4567',
            'items' => [['product_id' => 'p1', 'name' => 'Pullman Loaf', 'qty' => 2, 'price' => 69]],
            'voucher' => null,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'payment_ref' => null,
            'delivery_type' => 'pickup',
            'delivery_speed' => null,
            'fulfillment_branch' => 'BW Superbakeshop — Baguio',
            'address' => null,
            'subtotal' => 138,
            'discount' => 0,
            'delivery' => 0,
            'vat' => 16.56,
            'total' => 154.56,
            'status' => 'pending',
        ], $overrides));
    }

    public function test_the_price_breakdown_and_contact_details_are_shown(): void
    {
        $this->makeOrder([
            'voucher' => 'WELCOME10',
            'discount' => 13.8,
            'payment_ref' => 'pay_abc123',
            'delivery_type' => 'delivery',
            'delivery_speed' => 'express',
            'address' => '123 Main St, Angeles City',
        ]);

        $this->withSession($this->customerSession())
            ->get('/my-orders')
            ->assertOk()
            ->assertSee('Subtotal')
            ->assertSee('₱138.00')
            ->assertSee('Voucher (WELCOME10)')
            ->assertSee('₱16.56') // VAT
            ->assertSee('Express delivery')
            ->assertSee('pay_abc123')
            ->assertSee('Cara Customer')
            ->assertSee('0917 123 4567')
            ->assertSee('123 Main St, Angeles City');
    }

    public function test_pickup_orders_show_a_dash_for_delivery_fee_not_free(): void
    {
        $this->makeOrder(['delivery_type' => 'pickup', 'delivery' => 0]);

        $this->withSession($this->customerSession())
            ->get('/my-orders')
            ->assertOk()
            ->assertDontSee('FREE');
    }

    public function test_free_delivery_is_labeled_free_not_a_dash(): void
    {
        $this->makeOrder(['delivery_type' => 'delivery', 'delivery' => 0, 'delivery_speed' => 'standard', 'address' => 'Somewhere']);

        $this->withSession($this->customerSession())
            ->get('/my-orders')
            ->assertOk()
            ->assertSee('FREE');
    }

    public function test_more_than_a_page_of_orders_all_render_server_side_for_client_pagination(): void
    {
        // Client-side pagination only hides DOM nodes via JS — the server
        // must still render every order so instant filter/search/"load
        // more" all work without another request.
        for ($i = 0; $i < 8; $i++) {
            $this->makeOrder();
        }

        $response = $this->withSession($this->customerSession())->get('/my-orders');
        $response->assertOk();
        // 'class="order-card' (not just 'order-card') to count actual card
        // elements, not the JS's `.order-card` selector references too.
        $this->assertSame(8, substr_count($response->getContent(), 'class="order-card'));
        $response->assertSee('id="load-more"', false);
    }
}
