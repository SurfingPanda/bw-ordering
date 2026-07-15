<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompleteProfileTest extends TestCase
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
                'contact_number' => null,
            ], $userOverrides),
            'supabase_access_token' => 'token-1',
            'supabase_refresh_token' => 'refresh-1',
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    public function test_a_phoneless_customer_is_bounced_to_complete_profile(): void
    {
        $this->withSession($this->customerSession())
            ->get('/my-orders')
            ->assertRedirect(route('complete-profile'));
    }

    public function test_the_intake_page_renders_for_a_phoneless_customer(): void
    {
        $this->withSession($this->customerSession())
            ->get('/complete-profile')
            ->assertOk()
            ->assertSee('One last step')
            ->assertSee('Cara Customer');
    }

    public function test_a_customer_with_a_number_is_not_bounced(): void
    {
        $this->withSession($this->customerSession(['contact_number' => '0917 123 4567']))
            ->get('/my-orders')
            ->assertOk();

        // …and has nothing to do on the intake page itself.
        $this->withSession($this->customerSession(['contact_number' => '0917 123 4567']))
            ->get('/complete-profile')
            ->assertRedirect(route('menu'));
    }

    public function test_sessions_from_before_the_gate_are_left_alone(): void
    {
        $session = $this->customerSession();
        unset($session['supabase_user']['contact_number']);

        $this->withSession($session)->get('/my-orders')->assertOk();
    }

    public function test_admin_pages_are_exempt_from_the_gate(): void
    {
        $this->withSession($this->customerSession(['email' => 'bw.redeem@gmail.com']))
            ->get('/admin')
            ->assertOk();
    }

    public function test_saving_a_number_unblocks_the_customer(): void
    {
        config(['supabase.url' => 'https://example.supabase.co', 'supabase.service_role_key' => 'svc-key']);
        Http::fake([
            '*/rest/v1/profiles*' => Http::response(null, 201),
            '*/auth/v1/user' => Http::response(['id' => 'user-1'], 200),
        ]);

        $this->withSession($this->customerSession())
            ->post('/complete-profile', ['contact_number' => '0917 123 4567'])
            ->assertRedirect(route('menu'));

        $this->assertSame('0917 123 4567', session('supabase_user.contact_number'));

        // The gate lets them through now.
        $this->get('/my-orders')->assertOk();
    }

    public function test_a_taken_number_shows_the_uniqueness_error(): void
    {
        config(['supabase.url' => 'https://example.supabase.co', 'supabase.service_role_key' => 'svc-key']);
        Http::fake(['*/rest/v1/profiles*' => Http::response(null, 409)]);

        $this->withSession($this->customerSession())
            ->from('/complete-profile')
            ->post('/complete-profile', ['contact_number' => '0917 123 4567'])
            ->assertRedirect('/complete-profile')
            ->assertSessionHasErrors('contact_number');

        $this->assertNull(session('supabase_user.contact_number'));
    }

    public function test_an_invalid_number_is_rejected(): void
    {
        $this->withSession($this->customerSession())
            ->post('/complete-profile', ['contact_number' => '123'])
            ->assertSessionHasErrors('contact_number');
    }
}
