<?php

namespace Tests\Feature;

use App\Services\SupabaseAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutAddressesTest extends TestCase
{
    use RefreshDatabase;

    private function customerSession(): array
    {
        return ['supabase_user' => ['id' => 'user-1', 'email' => 'customer@example.com'], 'supabase_access_token' => 'token-1'];
    }

    public function test_address_is_appended_to_the_signed_in_accounts_existing_addresses(): void
    {
        $this->mock(SupabaseAuthService::class, function ($mock) {
            $mock->shouldReceive('fetchUser')->with('token-1')->once()->andReturn([
                'id' => 'user-1', 'user_metadata' => ['address' => 'Old home'],
            ]);
            $mock->shouldReceive('updateUser')->with('token-1', ['data' => ['addresses' => [
                ['label' => '', 'address' => 'Old home'],
                ['label' => 'Work', 'address' => 'New office'],
            ]]])->once()->andReturn([['id' => 'user-1'], null]);
        });
        $this->withSession($this->customerSession())->postJson('/profile/addresses', [
            'label' => 'Work', 'address' => ' New office ', 'user_id' => 'another-user',
        ])->assertOk()->assertJsonPath('selected', 1)->assertJsonPath('addresses.1.address', 'New office');
    }

    public function test_duplicate_addresses_are_reused_without_overwriting_the_account(): void
    {
        $this->mock(SupabaseAuthService::class, function ($mock) {
            $mock->shouldReceive('fetchUser')->once()->andReturn([
                'id' => 'user-1', 'user_metadata' => ['addresses' => [['label' => 'Home', 'address' => 'My home']]],
            ]);
            $mock->shouldNotReceive('updateUser');
        });
        $this->withSession($this->customerSession())->postJson('/profile/addresses', ['address' => 'my home'])
            ->assertOk()->assertJsonPath('selected', 0)->assertJsonCount(1, 'addresses');
    }

    public function test_a_failed_account_save_is_reported_as_an_error(): void
    {
        $this->mock(SupabaseAuthService::class, function ($mock) {
            $mock->shouldReceive('fetchUser')->once()->andReturn(['id' => 'user-1']);
            $mock->shouldReceive('updateUser')->once()->andReturn([null, 'Unavailable']);
        });
        $this->withSession($this->customerSession())->postJson('/profile/addresses', ['address' => 'My home'])->assertStatus(502);
    }

    public function test_unauthenticated_visitors_cannot_save_addresses(): void
    {
        $this->postJson('/profile/addresses', ['address' => 'My home'])->assertRedirect(route('login'));
    }

    public function test_checkout_lists_the_accounts_saved_locations(): void
    {
        $addresses = [['label' => 'Home', 'address' => 'My home'], ['label' => 'Work', 'address' => 'My office']];
        $this->mock(SupabaseAuthService::class, function ($mock) use ($addresses) {
            $mock->shouldReceive('fetchUser')->with('token-1')->once()->andReturn([
                'id' => 'user-1', 'user_metadata' => ['addresses' => $addresses],
            ]);
        });
        $this->withSession($this->customerSession())->get('/checkout')->assertOk()
            ->assertViewHas('savedAddresses', $addresses)
            ->assertSee('Add a new location')->assertSee('Save location')
            ->assertDontSee('<textarea name="address"', false);
    }

    public function test_deleting_a_location_preserves_the_other_saved_addresses(): void
    {
        $home = ['label' => 'Home', 'address' => 'My home'];
        $work = ['label' => 'Work', 'address' => 'My office'];
        $this->mock(SupabaseAuthService::class, function ($mock) use ($home, $work) {
            $mock->shouldReceive('fetchUser')->with('token-1')->once()->andReturn([
                'id' => 'user-1', 'user_metadata' => ['addresses' => [$home, $work]],
            ]);
            $mock->shouldReceive('updateUser')->with('token-1', ['data' => ['addresses' => [$work], 'address' => null]])
                ->once()->andReturn([['id' => 'user-1'], null]);
        });
        $this->withSession($this->customerSession())->deleteJson('/profile/addresses', ['index' => 0] + $home)
            ->assertOk()->assertJsonPath('addresses.0.address', 'My office')->assertJsonCount(1, 'addresses');
    }

    public function test_deleting_the_last_legacy_address_clears_the_legacy_fallback(): void
    {
        $this->mock(SupabaseAuthService::class, function ($mock) {
            $mock->shouldReceive('fetchUser')->once()->andReturn(['id' => 'user-1', 'user_metadata' => ['address' => 'Old home']]);
            $mock->shouldReceive('updateUser')->with('token-1', ['data' => ['addresses' => [], 'address' => null]])
                ->once()->andReturn([['id' => 'user-1'], null]);
        });
        $this->withSession($this->customerSession())->deleteJson('/profile/addresses', ['index' => 0, 'address' => 'Old home'])
            ->assertOk()->assertJsonCount(0, 'addresses');
    }

    public function test_a_stale_address_index_cannot_delete_a_different_location(): void
    {
        $this->mock(SupabaseAuthService::class, function ($mock) {
            $mock->shouldReceive('fetchUser')->once()->andReturn(['id' => 'user-1', 'user_metadata' => ['address' => 'Another address']]);
            $mock->shouldNotReceive('updateUser');
        });
        $this->withSession($this->customerSession())->deleteJson('/profile/addresses', ['index' => 0, 'address' => 'Old home'])->assertStatus(409);
    }
}
