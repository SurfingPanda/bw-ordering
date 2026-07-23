<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    /** Craft a real HS256 JWT so verifyAccessToken()'s offline path accepts it. */
    private function makeJwt(array $payload, string $secret): string
    {
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = $b64(json_encode($payload));
        $sig = $b64(hash_hmac('sha256', "{$header}.{$body}", $secret, true));

        return "{$header}.{$body}.{$sig}";
    }

    public function test_redirect_route_sends_the_browser_to_supabase_authorize(): void
    {
        config(['supabase.url' => 'https://example.supabase.co']);

        $this->get('/auth/google/redirect')
            ->assertRedirect()
            ->assertRedirectContains('https://example.supabase.co/auth/v1/authorize')
            ->assertRedirectContains('provider=google');
    }

    public function test_unknown_provider_is_rejected(): void
    {
        config(['supabase.url' => 'https://example.supabase.co']);

        $this->get('/auth/github/redirect')->assertNotFound();
    }

    public function test_callback_page_renders_the_token_forwarder(): void
    {
        $this->get('/auth/callback')->assertOk()->assertSee('Signing you in…');
    }

    public function test_valid_forwarded_tokens_establish_the_session(): void
    {
        config(['supabase.jwt_secret' => 'test-secret']);
        $jwt = $this->makeJwt([
            'sub' => 'google-user-id',
            'email' => 'customer@example.com',
            'exp' => time() + 3600,
            'user_metadata' => ['full_name' => 'Google Customer', 'contact_number' => '09171234567'],
        ], 'test-secret');

        $response = $this->post('/auth/callback', [
            'access_token' => $jwt,
            'refresh_token' => 'refresh-123',
            'expires_in' => 3600,
        ]);

        // A direct server-side redirect — no client-side bridge page/CDN
        // dependency in the way of actually landing the user somewhere.
        $response->assertRedirect(route('menu'));
        $this->assertSame('customer@example.com', session('supabase_user.email'));
        $this->assertSame($jwt, session('supabase_access_token'));
        $this->assertSame('refresh-123', session('supabase_refresh_token'));
    }

    public function test_a_first_time_google_sign_up_without_a_number_goes_to_complete_profile(): void
    {
        config(['supabase.jwt_secret' => 'test-secret']);
        $jwt = $this->makeJwt([
            'sub' => 'google-user-id',
            'email' => 'newcustomer@example.com',
            'exp' => time() + 3600,
            // Google sign-ups never carry a contact_number in user_metadata.
            'user_metadata' => ['full_name' => 'New Customer'],
        ], 'test-secret');

        $this->post('/auth/callback', [
            'access_token' => $jwt,
            'refresh_token' => 'refresh-123',
            'expires_in' => 3600,
        ])->assertRedirect(route('complete-profile'));
    }

    public function test_logging_out_and_back_in_without_a_number_still_gates_on_complete_profile(): void
    {
        config(['supabase.jwt_secret' => 'test-secret']);
        $jwt = fn () => $this->makeJwt([
            'sub' => 'google-user-id',
            'email' => 'newcustomer@example.com',
            'exp' => time() + 3600,
            'user_metadata' => ['full_name' => 'New Customer'], // still no contact_number
        ], 'test-secret');

        // First sign-up: gated on complete-profile, same as the test above.
        $this->post('/auth/callback', [
            'access_token' => $jwt(), 'refresh_token' => 'refresh-1', 'expires_in' => 3600,
        ])->assertRedirect(route('complete-profile'));

        // They bail out via complete-profile's "Log out" escape hatch instead
        // of adding a number.
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        // Signing back in with Google again (still no number saved) must
        // re-gate on complete-profile — not silently let them through because
        // a previous request already redirected them there once.
        $this->post('/auth/callback', [
            'access_token' => $jwt(), 'refresh_token' => 'refresh-2', 'expires_in' => 3600,
        ])->assertRedirect(route('complete-profile'));
    }

    public function test_logging_out_and_back_in_after_adding_a_number_goes_straight_to_menu(): void
    {
        config(['supabase.jwt_secret' => 'test-secret']);

        $this->post('/auth/callback', [
            'access_token' => $this->makeJwt([
                'sub' => 'google-user-id', 'email' => 'newcustomer@example.com', 'exp' => time() + 3600,
                'user_metadata' => ['full_name' => 'New Customer'],
            ], 'test-secret'),
            'refresh_token' => 'refresh-1', 'expires_in' => 3600,
        ])->assertRedirect(route('complete-profile'));

        $this->post('/logout')->assertRedirect('/');

        // This time the token Supabase hands back carries the number they
        // added last session (simulating a real second GoTrue exchange).
        $this->post('/auth/callback', [
            'access_token' => $this->makeJwt([
                'sub' => 'google-user-id', 'email' => 'newcustomer@example.com', 'exp' => time() + 3600,
                'user_metadata' => ['full_name' => 'New Customer', 'contact_number' => '09171234567'],
            ], 'test-secret'),
            'refresh_token' => 'refresh-2', 'expires_in' => 3600,
        ])->assertRedirect(route('menu'));
    }

    public function test_a_garbage_token_never_signs_in(): void
    {
        config(['supabase.jwt_secret' => 'test-secret', 'supabase.url' => '', 'supabase.anon_key' => '']);

        $this->post('/auth/callback', [
            'access_token' => 'not-a-jwt',
            'refresh_token' => 'refresh-123',
        ])->assertRedirect(route('login', ['oauth' => 'failed']));

        $this->assertNull(session('supabase_user'));
    }
}
