<?php

namespace Tests\Feature;

use App\Models\CustomCakeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomCakePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_cake_detail_fields_are_required(): void
    {
        $this->post('/custom-cake', [
            'name' => 'Jane Baker',
            'email' => 'jane@example.com',
            'description' => 'A two-tier ube cake with gold accents.',
        ])->assertSessionHasErrors(['occasion', 'needed_by', 'flavor', 'size', 'frosting_color', 'delivery_type']);

        $this->assertSame(0, CustomCakeRequest::count());
    }

    public function test_delivery_requests_need_an_address_and_pickup_needs_a_branch(): void
    {
        $this->post('/custom-cake', array_merge($this->validRequest(), [
            'delivery_type' => 'delivery',
            'address' => '',
        ]))->assertSessionHasErrors('address');

        $this->post('/custom-cake', array_merge($this->validRequest(), [
            'delivery_type' => 'pickup',
            'fulfillment_branch' => '',
        ]))->assertSessionHasErrors('fulfillment_branch');

        $this->assertSame(0, CustomCakeRequest::count());
    }

    public function test_a_delivery_request_drops_any_stray_branch(): void
    {
        $this->post('/custom-cake', array_merge($this->validRequest(), [
            'delivery_type' => 'delivery',
            'address' => '12 Mabini St, Quezon City',
            'fulfillment_branch' => 'BW Superbakeshop — Makati',
        ]));

        $cc = CustomCakeRequest::first();
        $this->assertSame('delivery', $cc->delivery_type);
        $this->assertSame('12 Mabini St, Quezon City', $cc->address);
        $this->assertNull($cc->fulfillment_branch);
    }

    public function test_a_complete_request_is_stored(): void
    {
        $this->post('/custom-cake', $this->validRequest())
            ->assertRedirect(route('custom-cake'))
            ->assertSessionHas('cc_success', true);

        $this->assertSame(1, CustomCakeRequest::count());
        $this->assertNull(CustomCakeRequest::first()->user_id); // guest
    }

    public function test_a_signed_in_request_shows_on_my_orders(): void
    {
        $session = [
            'supabase_user' => [
                'id' => '11111111-1111-1111-1111-111111111111',
                'email' => 'jane@example.com',
                'name' => 'Jane Baker',
                'contact_number' => '0917 123 4567',
            ],
            'supabase_access_token' => 'token-1',
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];

        $this->withSession($session)->post('/custom-cake', $this->validRequest());

        $this->assertSame('11111111-1111-1111-1111-111111111111', CustomCakeRequest::first()->user_id);

        $this->withSession($session)->get('/my-orders')
            ->assertOk()
            ->assertSee('Custom Cake Requests')
            ->assertSee('Cake Request #'.CustomCakeRequest::first()->id)
            ->assertSee('Request received');
    }

    public function test_saved_addresses_show_as_a_dropdown_for_returning_customers(): void
    {
        \Illuminate\Support\Facades\Http::fake(); // page prefills the phone via Supabase

        $session = [
            'supabase_user' => [
                'id' => '11111111-1111-1111-1111-111111111111',
                'email' => 'jane@example.com',
                'name' => 'Jane Baker',
                'contact_number' => '0917 123 4567',
            ],
            'supabase_access_token' => 'token-1',
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];

        // First visit: no dropdown, nothing saved yet.
        $this->withSession($session)->get('/custom-cake')->assertOk()->assertDontSee('Saved addresses');

        // A past delivery request seeds the address book.
        $this->withSession($session)->post('/custom-cake', array_merge($this->validRequest(), [
            'delivery_type' => 'delivery',
            'address' => '12 Mabini St, Quezon City',
            'fulfillment_branch' => '',
        ]));

        $this->withSession($session)->get('/custom-cake')
            ->assertOk()
            ->assertSee('Saved addresses')
            ->assertSee('12 Mabini St, Quezon City');
    }

    private function validRequest(): array
    {
        return [
            'name' => 'Jane Baker',
            'email' => 'jane@example.com',
            'occasion' => 'Birthday',
            'needed_by' => now()->addWeek()->toDateString(),
            'flavor' => 'Ube',
            'size' => '2-Tier',
            'frosting_color' => 'Purple',
            'delivery_type' => 'pickup',
            'fulfillment_branch' => 'BW Superbakeshop — Makati',
            'description' => 'A two-tier ube cake with gold accents.',
        ];
    }
}
