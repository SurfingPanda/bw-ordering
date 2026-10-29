<?php

namespace Tests\Feature;

use App\Services\SupabaseAuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutProfileCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['supabase.url' => 'https://identity.example', 'supabase.anon_key' => 'test-key']);
        Cache::flush();
    }

    public function test_checkout_reuses_the_login_profile_without_an_extra_network_request(): void
    {
        Http::fake(['*' => Http::response([
            'access_token' => 'token-1', 'user' => ['id' => 'user-1', 'user_metadata' => ['addresses' => [['address' => 'Home']]]],
        ])]);
        $auth = app(SupabaseAuthService::class);
        $auth->passwordGrant('customer@example.com', 'password');
        $this->assertSame('Home', $auth->fetchCachedUser('token-1')['user_metadata']['addresses'][0]['address']);
        Http::assertSentCount(1);
    }

    public function test_repeated_checkout_uses_cache_and_refreshes_after_expiry(): void
    {
        Http::fake(['*' => Http::response(['id' => 'user-1', 'user_metadata' => []])]);
        $auth = app(SupabaseAuthService::class);
        $auth->fetchCachedUser('token-1');
        $auth->fetchCachedUser('token-1');
        Http::assertSentCount(1);
        $this->travel(6)->minutes();
        $auth->fetchCachedUser('token-1');
        Http::assertSentCount(2);
    }

    public function test_address_updates_immediately_replace_the_checkout_cache(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['id' => 'user-1', 'user_metadata' => ['addresses' => [['address' => 'Old']]]])
            ->push(['id' => 'user-1', 'user_metadata' => ['addresses' => [['address' => 'New']]]])]);
        $auth = app(SupabaseAuthService::class);
        $auth->fetchCachedUser('token-1');
        $auth->updateUser('token-1', ['data' => ['addresses' => [['address' => 'New']]]]);
        $this->assertSame('New', $auth->fetchCachedUser('token-1')['user_metadata']['addresses'][0]['address']);
        Http::assertSentCount(2);
    }

    public function test_profiles_are_cached_separately_for_each_access_token(): void
    {
        Http::fake(['*' => Http::sequence()->push(['id' => 'user-1'])->push(['id' => 'user-2'])]);
        $auth = app(SupabaseAuthService::class);
        $this->assertSame('user-1', $auth->fetchCachedUser('token-1')['id']);
        $this->assertSame('user-2', $auth->fetchCachedUser('token-2')['id']);
        Http::assertSentCount(2);
    }
}
