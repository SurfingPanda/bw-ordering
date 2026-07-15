<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Baker',
            'email' => 'jane@example.com',
            'contact_number' => '0917 123 4567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $overrides);
    }

    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee('Create your account');
    }

    public function test_signed_in_users_are_redirected_away(): void
    {
        $this->withSession(['supabase_user' => ['id' => 'x', 'email' => 'a@b.c']])
            ->get('/register')->assertRedirect(route('menu'));
    }

    public function test_successful_registration_signs_up_and_returns_to_login(): void
    {
        config(['supabase.url' => 'https://example.supabase.co', 'supabase.service_role_key' => 'svc-key']);
        Http::fake([
            '*/rest/v1/rpc/contact_number_taken' => Http::response('false', 200),
            '*/auth/v1/signup' => Http::response(['id' => 'new-user-1', 'email' => 'jane@example.com'], 200),
            '*/rest/v1/profiles*' => Http::response(null, 201),
        ]);

        $this->post('/register', $this->validPayload())
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Account created! Please sign in.');

        Http::assertSent(fn ($req) => str_contains($req->url(), '/auth/v1/signup')
            && $req['data']['full_name'] === 'Jane Baker'
            && $req['data']['contact_number'] === '0917 123 4567');
        // The normalized number is claimed in profiles under the new user id.
        Http::assertSent(fn ($req) => str_contains($req->url(), '/rest/v1/profiles')
            && $req[0]['id'] === 'new-user-1'
            && $req[0]['contact_number'] === '09171234567');
    }

    public function test_a_taken_contact_number_is_rejected_before_signup(): void
    {
        config(['supabase.url' => 'https://example.supabase.co']);
        Http::fake(['*/rest/v1/rpc/contact_number_taken' => Http::response('true', 200)]);

        $this->from('/register')->post('/register', $this->validPayload())
            ->assertRedirect('/register')
            ->assertSessionHasErrors('contact_number');

        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/auth/v1/signup'));
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->post('/register', $this->validPayload([
            'name' => 'J',
            'contact_number' => '123',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]))->assertSessionHasErrors(['name', 'password']);

        // A too-short phone number is caught by the digit-count rule.
        Http::fake();
        $this->post('/register', $this->validPayload(['contact_number' => '123']))
            ->assertSessionHasErrors('contact_number');
    }
}
