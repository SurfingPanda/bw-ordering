<?php

namespace Tests\Feature;

use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FranchisePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_with_defaults_when_nothing_saved(): void
    {
        $this->get('/franchise')
            ->assertOk()
            ->assertSee('Partner with us')
            ->assertSee('Franchise packages')
            ->assertSee('Kiosk')
            ->assertSee('franchise@bwsuperbakeshop.com');
    }

    public function test_renders_saved_franchise_content(): void
    {
        SiteContent::create(['id' => 1, 'data' => ['franchise' => [
            'hero' => ['eyebrow' => 'Grow with us', 'title' => 'Own a BW branch', 'subtitle' => 'Join us.'],
            'email' => 'invest@bw.test',
            'packages' => [['name' => 'Food Cart', 'price' => '₱500K', 'blurb' => 'Small start', 'features' => ['10 sqm'], 'featured' => true]],
        ]]]);

        $this->get('/franchise')
            ->assertOk()
            ->assertSee('Own a BW branch')
            ->assertSee('Food Cart')
            ->assertSee('invest@bw.test');
    }
}
