<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminStoreControllerTest extends TestCase
{
    use RefreshDatabase;

    private function asUser(string $email): array
    {
        return [
            'supabase_user' => ['id' => 'test-id', 'email' => $email, 'name' => 'Test User'],
            'supabase_token_expires_at' => now()->addHour()->timestamp,
        ];
    }

    private function makeStore(array $overrides = []): Store
    {
        return Store::create(array_merge([
            'name' => 'QC Branch',
            'region' => 'Metro Manila',
            'fulfillment' => 'both',
            'address' => '123 Katipunan Ave, Quezon City',
            'hours' => '7:00 AM – 9:00 PM',
            'phone' => '0917 000 0000',
            'latitude' => 14.6394,
            'longitude' => 121.0790,
        ], $overrides));
    }

    /** The card fields the editor submits for an existing store row. */
    private function rowFor(Store $store, array $overrides = []): array
    {
        return array_merge([
            'id' => $store->id,
            'name' => $store->name,
            'region' => $store->region,
            'fulfillment' => $store->fulfillment,
            'address' => $store->address,
            'hours' => $store->hours,
            'phone' => $store->phone,
            'latitude' => $store->latitude,
            'longitude' => $store->longitude,
        ], $overrides);
    }

    public function test_non_editor_is_forbidden(): void
    {
        $this->withSession($this->asUser('customer@example.com'))
            ->get(route('admin.stores.index'))
            ->assertForbidden();
    }

    public function test_editor_sees_the_card_editor_with_branches(): void
    {
        $this->makeStore();

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->get(route('admin.stores.index'))
            ->assertOk()
            ->assertSee('Find a Store')
            ->assertSee('QC Branch')
            ->assertSee('Save changes');
    }

    public function test_bulk_save_updates_creates_and_busts_the_locator_cache(): void
    {
        $store = $this->makeStore();
        Cache::put('stores.index', 'stale', 600);

        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.stores.sync'), [
                'stores' => [
                    $this->rowFor($store, ['name' => 'Katipunan Branch']),
                    ['id' => '', 'name' => 'Cebu Branch', 'region' => 'Visayas', 'fulfillment' => 'pickup', 'address' => 'Osmeña Blvd', 'latitude' => 10.3157, 'longitude' => 123.8854],
                    ['id' => '', 'name' => '  ', 'address' => ''], // untouched blank card
                ],
                'originalIds' => [$store->id],
            ])
            ->assertRedirect(route('admin.stores.index'));

        $this->assertSame(2, Store::count());
        $this->assertSame('Katipunan Branch', $store->fresh()->name);
        $this->assertDatabaseHas('stores', ['name' => 'Cebu Branch', 'region' => 'Visayas']);
        $this->assertNull(Cache::get('stores.index'));
    }

    public function test_a_store_removed_from_the_grid_is_deleted(): void
    {
        $kept = $this->makeStore();
        $removed = $this->makeStore(['name' => 'Old Branch']);

        $this->withSession($this->asUser('bw.redeem@gmail.com'))
            ->post(route('admin.stores.sync'), [
                'stores' => [$this->rowFor($kept)],
                'originalIds' => [$kept->id, $removed->id],
            ])
            ->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseMissing('stores', ['id' => $removed->id]);
        $this->assertDatabaseHas('stores', ['id' => $kept->id]);
    }

    public function test_out_of_range_coordinates_are_rejected(): void
    {
        $this->withSession($this->asUser('editor@bwsuperbakeshop.com'))
            ->post(route('admin.stores.sync'), [
                'stores' => [['id' => '', 'name' => 'Mars Branch', 'address' => 'Olympus Mons', 'latitude' => 999, 'longitude' => 0]],
            ])
            ->assertSessionHasErrors('stores.0.latitude');

        $this->assertDatabaseCount('stores', 0);
    }
}
