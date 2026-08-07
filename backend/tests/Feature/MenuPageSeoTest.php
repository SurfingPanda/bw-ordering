<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_filter_produces_a_category_aware_title(): void
    {
        $this->get('/menu?category=Cake')
            ->assertOk()
            ->assertSee('<title>Cake — Order Online | BW Superbakeshop</title>', false);

        $this->get('/menu')
            ->assertOk()
            ->assertSee('<title>Order Online — BW Superbakeshop</title>', false);
    }

    public function test_emits_valid_product_item_list_json_ld(): void
    {
        Product::factory()->create(['name' => 'Pullman Loaf', 'price' => 74, 'category' => 'Bread']);
        Product::factory()->create(['name' => 'Jumbo Loaf', 'price' => 85, 'category' => 'Bread']);

        $html = $this->get('/menu')->assertOk()->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $jsonLd = json_decode($m[1], true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('ItemList', $jsonLd['@type']);
        $this->assertCount(2, $jsonLd['itemListElement']);
        // ProductController::cachedList() orders by category then name, so
        // "Jumbo Loaf" sorts before "Pullman Loaf" within the same category.
        $this->assertSame('Jumbo Loaf', $jsonLd['itemListElement'][0]['item']['name']);
        $this->assertSame(85, $jsonLd['itemListElement'][0]['item']['offers']['price']);
        $this->assertSame('PHP', $jsonLd['itemListElement'][0]['item']['offers']['priceCurrency']);
    }
}
