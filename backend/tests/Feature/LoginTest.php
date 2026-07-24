<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoTrue(array $responses): void
    {
        config(['supabase.url' => 'https://example.supabase.co', 'supabase.anon_key' => 'anon']);
        Http::fake($responses);
    }

    public function test_unconfirmed_email_gets_a_confirmation_prompt_not_invalid_credentials(): void
    {
        // GoTrue rejects a valid-but-unverified login with a distinct code.
        $this->fakeGoTrue([
            '*/auth/v1/token*' => Http::response(['error_code' => 'email_not_confirmed', 'msg' => 'Email not confirmed'], 400),
        ]);

        $this->from('/login')
            ->post('/login', ['email' => 'newbie@example.com', 'password' => 'secret123'])
            ->assertRedirect('/login')
            ->assertSessionHas('unconfirmed_email', 'newbie@example.com')
            ->assertSessionHasNoErrors();

        $this->assertNull(session('supabase_user'));
    }

    public function test_legacy_gotrue_not_confirmed_shape_is_also_detected(): void
    {
        // Older GoTrue reports it via error_description, not error_code.
        $this->fakeGoTrue([
            '*/auth/v1/token*' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Email not confirmed'], 400),
        ]);

        $this->from('/login')
            ->post('/login', ['email' => 'newbie@example.com', 'password' => 'secret123'])
            ->assertSessionHas('unconfirmed_email', 'newbie@example.com')
            ->assertSessionHasNoErrors();
    }

    public function test_wrong_password_still_shows_invalid_credentials(): void
    {
        $this->fakeGoTrue([
            '*/auth/v1/token*' => Http::response(['error_code' => 'invalid_credentials', 'msg' => 'Invalid login credentials'], 400),
        ]);

        $this->from('/login')
            ->post('/login', ['email' => 'someone@example.com', 'password' => 'wrongpass'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertNull(session('unconfirmed_email'));
    }

    public function test_resend_confirmation_hits_gotrue_and_reports_success(): void
    {
        $this->fakeGoTrue([
            '*/auth/v1/resend*' => Http::response([], 200),
        ]);

        $this->post('/resend-confirmation', ['email' => 'newbie@example.com'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status')
            ->assertSessionHas('unconfirmed_email', 'newbie@example.com');

        Http::assertSent(fn ($req) => str_contains($req->url(), '/auth/v1/resend')
            && $req['type'] === 'signup'
            && $req['email'] === 'newbie@example.com');
    }

    public function test_resend_confirmation_requires_a_valid_email(): void
    {
        Http::fake();

        $this->from('/login')
            ->post('/resend-confirmation', ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');

        Http::assertNothingSent();
    }
}
