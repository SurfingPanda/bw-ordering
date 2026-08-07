<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoresPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(array $overrides = []): Store
    {
        return Store::create(array_merge([
            'name' => 'BW Superbakeshop — Makati',
            'region' => 'Luzon',
            'fulfillment' => 'both',
            'address' => '88 Ayala Ave, Makati City',
            'hours' => '7:00 AM – 9:00 PM',
            'phone' => '0917 000 0000',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
        ], $overrides));
    }

    public function test_guest_sees_the_store_locator_with_branches_and_map_data(): void
    {
        $this->makeStore();
        $this->makeStore(['name' => 'BW Superbakeshop — Cebu', 'region' => 'Visayas', 'address' => 'Osmeña Blvd, Cebu City']);

        $this->get('/stores')
            ->assertOk()
            ->assertSee('Find a')
            ->assertSee('BW Superbakeshop — Makati')
            ->assertSee('BW Superbakeshop — Cebu')
            ->assertSee('2 stores found')
            // The MapLibre script reads this JSON blob (coordinates included).
            ->assertSee('stores-data', false)
            ->assertSee('121.0244', false)
            ->assertSee('id="store-map"', false);
    }

    public function test_renders_the_empty_state_without_stores(): void
    {
        $this->get('/stores')
            ->assertOk()
            ->assertSee('0 stores found');
    }
}
