<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPromoTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_promo_data_is_passed_to_the_view(): void
    {
        SiteContent::create([
            'id' => 1,
            'data' => [
                'menuPromo' => [
                    'enabled' => true,
                    'slides' => [
                        ['badge' => '✨ Just Launched', 'title' => 'Ube Chiffon Cake', 'buttonLabel' => 'Add to cart'],
                    ],
                ],
                'menuCategoryImages' => ['Cakes' => '/images/cakes-badge.png'],
            ],
        ]);
        Product::factory()->create(['category' => 'Cakes']);

        $response = $this->get(route('menu'));

        $response->assertOk()
            ->assertSee('Ube Chiffon Cake')
            ->assertSee('cakes-badge.png');
    }

    public function test_menu_still_renders_when_no_site_content_row_exists(): void
    {
        Product::factory()->create();

        $this->get(route('menu'))->assertOk();
    }
}
