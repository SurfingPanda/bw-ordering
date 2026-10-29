<?php

namespace Tests\Feature;

use App\Models\CustomerAddressBook;
use App\Services\DeliveryAddressService;
use App\Services\SupabaseAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutAddressesTest extends TestCase
{
    use RefreshDatabase;

    private function customerSession(): array
    {
        return ['supabase_user' => ['id' => 'user-1', 'email' => 'customer@example.com'], 'supabase_access_token' => 'token-1'];
    }

    private function seedAddresses(array $rows, string $userId = 'user-1'): CustomerAddressBook
    {
        return CustomerAddressBook::create(['user_id' => $userId, 'addresses' => $rows, 'imported_at' => now()]);
    }

    public function test_adding_an_address_persists_it_locally_for_only_the_signed_in_user(): void
    {
        Http::preventStrayRequests();
        $this->seedAddresses([['label' => 'Home', 'address' => 'My home']]);
        $this->withSession($this->customerSession())->postJson('/profile/addresses', [
            'label' => 'Work', 'address' => ' My office ', 'user_id' => 'another-user',
        ])->assertOk()->assertJsonPath('selected', 1);
        $book = CustomerAddressBook::findOrFail('user-1');
        $this->assertSame('My office', $book->addresses[1]['address']);
        $this->assertNull(CustomerAddressBook::find('another-user'));
        Http::assertNothingSent();
    }

    public function test_duplicate_addresses_are_reused(): void
    {
        $this->seedAddresses([['label' => 'Home', 'address' => 'My home']]);
        $this->withSession($this->customerSession())->postJson('/profile/addresses', ['address' => 'my home'])
            ->assertOk()->assertJsonPath('selected', 0)->assertJsonCount(1, 'addresses');
    }

    public function test_unauthenticated_visitors_cannot_save_or_delete_addresses(): void
    {
        $this->postJson('/profile/addresses', ['address' => 'My home'])->assertRedirect(route('login'));
        $this->deleteJson('/profile/addresses', ['index' => 0, 'address' => 'My home'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('customer_address_books', 0);
    }

    public function test_checkout_uses_mysql_addresses_instead_of_old_supabase_metadata(): void
    {
        $addresses = [['label' => 'Home', 'address' => 'My home'], ['label' => 'Work', 'address' => 'My office']];
        $this->seedAddresses($addresses);
        $this->mock(SupabaseAuthService::class, function ($mock) {
            $mock->shouldReceive('fetchCachedUser')->with('token-1')->once()->andReturn([
                'id' => 'user-1', 'user_metadata' => ['address' => 'Outdated remote address'],
            ]);
        });
        $this->withSession($this->customerSession())->get('/checkout')->assertOk()
            ->assertViewHas('savedAddresses', $addresses)->assertSee('Save location');
    }

    public function test_deleting_a_location_preserves_the_other_saved_address(): void
    {
        Http::preventStrayRequests();
        $home = ['label' => 'Home', 'address' => 'My home'];
        $work = ['label' => 'Work', 'address' => 'My office'];
        $book = $this->seedAddresses([$home, $work]);
        $this->withSession($this->customerSession())->deleteJson('/profile/addresses', ['index' => 0] + $home)
            ->assertOk()->assertJsonPath('addresses.0.address', 'My office')->assertJsonCount(1, 'addresses');
        $this->assertSame([$work], $book->fresh()->addresses);
        Http::assertNothingSent();
    }

    public function test_legacy_import_is_once_only_and_cannot_restore_a_deleted_address(): void
    {
        $service = app(DeliveryAddressService::class);
        $legacy = ['address' => 'Old home'];
        $this->assertSame([['label' => '', 'address' => 'Old home']], $service->read('user-1', $legacy));
        $this->withSession($this->customerSession())->deleteJson('/profile/addresses', ['index' => 0, 'address' => 'Old home'])
            ->assertOk()->assertJsonCount(0, 'addresses');
        $this->assertSame([], $service->read('user-1', $legacy));
    }

    public function test_another_users_location_cannot_be_deleted(): void
    {
        $this->seedAddresses([['label' => '', 'address' => 'My home']]);
        $other = $this->seedAddresses([['label' => '', 'address' => 'Other home']], 'user-2');
        $this->withSession($this->customerSession())->deleteJson('/profile/addresses', [
            'index' => 0, 'address' => 'Other home', 'user_id' => 'user-2',
        ])->assertStatus(409);
        $this->assertSame('Other home', $other->fresh()->addresses[0]['address']);
    }

    public function test_a_third_saved_address_is_rejected(): void
    {
        $this->seedAddresses([['label' => 'Home', 'address' => 'My home'], ['label' => 'Work', 'address' => 'My office']]);
        $this->withSession($this->customerSession())->postJson('/profile/addresses', ['address' => 'Third location'])
            ->assertStatus(422)->assertJsonPath('message', 'You can save up to 2 addresses. Delete a location before adding another.');
        $this->assertCount(2, CustomerAddressBook::findOrFail('user-1')->addresses);
    }

    public function test_my_profile_also_saves_addresses_in_mysql(): void
    {
        $this->mock(SupabaseAuthService::class, function ($mock) {
            $mock->shouldReceive('claimContactNumber')->once()->andReturn(null);
            $mock->shouldReceive('updateUser')->with('token-1', ['data' => [
                'full_name' => 'Customer', 'contact_number' => '09171234567',
            ]])->once()->andReturn([['id' => 'user-1'], null]);
        });
        $this->withSession($this->customerSession())->post('/profile/info', [
            'name' => 'Customer', 'contact_number' => '09171234567', 'addresses' => [['label' => 'Home', 'address' => 'My home']],
        ])->assertRedirect()->assertSessionHas('info_success');
        $this->assertSame('My home', CustomerAddressBook::findOrFail('user-1')->addresses[0]['address']);
    }
}
