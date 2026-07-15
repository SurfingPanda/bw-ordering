<?php

namespace Tests\Feature;

use App\Models\CustomCakeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomCakesTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): array
    {
        return [
            'supabase_user' => ['id' => 'admin-id', 'email' => 'bw.redeem@gmail.com', 'name' => 'Admin'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    private function makeRequest(array $overrides = []): CustomCakeRequest
    {
        return CustomCakeRequest::create(array_merge([
            'user_id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Jane Baker',
            'email' => 'jane@example.com',
            'occasion' => 'Wedding',
            'needed_by' => now()->addWeek()->toDateString(),
            'flavor' => 'Chocolate',
            'size' => '3-Tier',
            'frosting_color' => 'Chocolate',
            'description' => 'Three chocolate tiers with gold leaf.',
        ], $overrides));
    }

    public function test_non_staff_cannot_open_the_panel(): void
    {
        $this->withSession([
            'supabase_user' => ['id' => 'x', 'email' => 'customer@example.com', 'contact_number' => '0917'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ])->get('/admin/custom-cakes')->assertForbidden();
    }

    public function test_staff_see_the_requests_with_details(): void
    {
        $cc = $this->makeRequest();

        $this->withSession($this->asAdmin())
            ->get('/admin/custom-cakes')
            ->assertOk()
            ->assertSee("Request #{$cc->id}")
            ->assertSee('Wedding')
            ->assertSee('jane@example.com')
            ->assertSee('Three chocolate tiers with gold leaf.');
    }

    public function test_status_filter_narrows_the_list(): void
    {
        $new = $this->makeRequest();
        $quoted = $this->makeRequest(['status' => 'quoted', 'description' => 'A quoted request for filtering.']);

        $this->withSession($this->asAdmin())
            ->get('/admin/custom-cakes?status=quoted')
            ->assertOk()
            ->assertSee("Request #{$quoted->id}")
            ->assertDontSee("Request #{$new->id}");
    }

    public function test_updating_the_status_reflects_on_the_customers_my_orders(): void
    {
        $cc = $this->makeRequest();

        $this->withSession($this->asAdmin())
            ->post("/admin/custom-cakes/{$cc->id}/status", ['status' => 'quoted'])
            ->assertRedirect(route('admin.custom-cakes'));

        $this->assertSame('quoted', $cc->fresh()->status);

        // The customer's badge flips from "Request received" to "Quote sent".
        $this->withSession([
            'supabase_user' => [
                'id' => '11111111-1111-1111-1111-111111111111',
                'email' => 'jane@example.com',
                'name' => 'Jane Baker',
                'contact_number' => '0917 123 4567',
            ],
            'supabase_access_token' => 'token-1',
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ])->get('/my-orders')->assertOk()->assertSee('Quote sent');
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $cc = $this->makeRequest();

        $this->withSession($this->asAdmin())
            ->post("/admin/custom-cakes/{$cc->id}/status", ['status' => 'baked'])
            ->assertSessionHasErrors('status');

        $this->assertSame('new', $cc->fresh()->status);
    }
}
