<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    public function test_the_page_shows_saved_address_member_since_and_sign_in_method(): void
    {
        Http::fake(['*/auth/v1/user' => Http::response([
            'id' => 'user-1',
            'email' => 'customer@example.com',
            'created_at' => '2025-03-14T10:00:00Z',
            'user_metadata' => [
                'full_name' => 'Cara Customer',
                'contact_number' => '0917 123 4567',
                'address' => '123 Main St, Angeles City, Pampanga',
            ],
            'identities' => [
                ['provider' => 'google'],
            ],
        ], 200)]);

        $this->withSession($this->customerSession())
            ->get('/profile')
            ->assertOk()
            ->assertSee('123 Main St, Angeles City, Pampanga')
            ->assertSee('Member since March 2025')
            ->assertSee('Google');
    }

    public function test_the_page_renders_with_no_address_saved_yet(): void
    {
        Http::fake(['*/auth/v1/user' => Http::response([
            'id' => 'user-1',
            'email' => 'customer@example.com',
            'user_metadata' => ['full_name' => 'Cara Customer', 'contact_number' => '0917 123 4567'],
        ], 200)]);

        $this->withSession($this->customerSession())
            ->get('/profile')
            ->assertOk()
            ->assertSee('Delivery address');
    }

    public function test_saving_account_details_includes_the_address_in_the_supabase_update(): void
    {
        Http::fake([
            '*/rest/v1/profiles*' => Http::response(null, 201),
            '*/auth/v1/user' => Http::response(['id' => 'user-1'], 200),
        ]);

        $this->withSession($this->customerSession())
            ->post('/profile/info', [
                'name' => 'Cara Customer',
                'contact_number' => '0917 123 4567',
                'address' => '456 Second St, Baguio City',
            ])
            ->assertRedirect()
            ->assertSessionHas('info_success');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/auth/v1/user')
                && $request->method() === 'PUT'
                && $request['data']['address'] === '456 Second St, Baguio City';
        });
    }

    public function test_checkout_prefills_the_delivery_address_from_the_saved_profile(): void
    {
        Http::fake(['*/auth/v1/user' => Http::response([
            'id' => 'user-1',
            'user_metadata' => ['contact_number' => '0917 123 4567', 'address' => '789 Third Ave, Cebu City'],
        ], 200)]);

        $this->withSession($this->customerSession())
            ->get('/checkout')
            ->assertOk()
            ->assertSee('789 Third Ave, Cebu City');
    }
}
