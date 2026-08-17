<?php

namespace Tests\Feature;

use App\Http\Controllers\LandingController;
use App\Models\Product;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

// Exercises LandingController directly (rather than via the "/" route) so this
// test doesn't depend on how/when routes/web.php wires the route in.
class LandingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_with_defaults_when_no_site_content_saved(): void
    {
        $view = app(LandingController::class)->index(Request::create('/'));

        $this->assertSame('landing', $view->name());
        $this->assertFalse($view->getData()['content']['maintenance']['enabled']);
        $this->assertSame(
            '🚚 Free delivery on orders over ₱1,000  •  Freshly baked every morning  •  Order now and taste the love!',
            $view->getData()['content']['announcement'],
        );
    }

    public function test_nav_menu_and_order_now_open_straight_to_whats_new(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $expectedHref = '/menu?category=' . urlencode("What's New");
        // Desktop nav "Menu", desktop "Order Now", mobile nav "Menu", mobile
        // "Order Now" — all four, not just one of them.
        $this->assertSame(4, substr_count($html, 'href="'.$expectedHref.'"'));
    }

    public function test_legacy_footer_placeholders_link_to_their_real_pages(): void
    {
        SiteContent::create(['id' => 1, 'data' => [
            'footer' => [
                'columns' => [[
                    'title' => 'Company',
                    'links' => [
                        ['label' => 'About Us', 'url' => '/#'],
                        ['label' => 'Contact', 'url' => '/#'],
                    ],
                ]],
            ],
        ]]);
        Cache::forget('site-content');

        $this->get('/')
            ->assertOk()
            ->assertSee('<a data-editable="footer.columns.0.links.0.label" href="/about"', false)
            ->assertSee('<a data-editable="footer.columns.0.links.1.label" href="/contact"', false);
    }

    public function test_store_locator_search_submits_to_the_real_stores_search(): void
    {
        // The teaser used to be a plain link to /stores that ignored whatever
        // was typed — it must now be a real GET form so the search box
        // actually filters the /stores page it lands on.
        $this->get('/')
            ->assertOk()
            ->assertSee('<form action="/stores" method="GET"', false)
            ->assertSee('name="q"', false);
    }

    public function test_store_locator_search_is_disabled_when_the_button_is_off(): void
    {
        SiteContent::create(['id' => 1, 'data' => [
            'buttons' => ['storeLocatorFind' => 'off'],
        ]]);

        $this->get('/')->assertOk()->assertDontSee('name="q"', false);
    }

    public function test_best_sellers_section_is_hidden_when_nothing_is_flagged(): void
    {
        Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Plain Pandesal',
            'price' => 60,
            'status' => null,
        ]);

        // No product is flagged best_seller — the section (heading, empty
        // grid, and "See Best Sellers" button) must not render at all.
        $this->get('/')->assertOk()->assertDontSee('Our Best Sellers');
    }

    public function test_best_sellers_section_renders_when_a_product_is_flagged(): void
    {
        Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mocha Cake',
            'price' => 650,
            'status' => 'best_seller',
        ]);

        $this->get('/')->assertOk()->assertSee('Our Best Sellers')->assertSee('Mocha Cake');
    }

    public function test_best_sellers_and_whats_new_are_capped(): void
    {
        // The grid is md:grid-cols-4 — flagging more than the cap used to
        // render every single one, unbounded. Best Sellers caps at two full
        // rows; What's New caps at one.
        Product::factory()->count(12)->create(['status' => 'best_seller']);
        Product::factory()->count(9)->create(['status' => 'new']);

        $view = app(LandingController::class)->index(Request::create('/'));
        $data = $view->getData();

        $this->assertCount(8, $data['bestSellers']);
        $this->assertCount(4, $data['whatsNewProducts']);
    }

    public function test_best_sellers_and_categories_come_from_the_products_table(): void
    {
        Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mocha Cake',
            'category' => 'Cake',
            'price' => 650,
            'status' => 'best_seller',
            'features' => ['Gluten', 'Eggs'],
        ]);
        Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pandesal',
            'category' => 'Bread',
            'price' => 60,
            'status' => null,
        ]);
        Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Archived Croissant',
            'category' => 'Pastry',
            'price' => 85,
            'status' => 'best_seller',
            'archived_at' => now(),
        ]);

        $view = app(LandingController::class)->index(Request::create('/'));
        $data = $view->getData();

        $this->assertCount(1, $data['bestSellers']);
        $this->assertSame('Mocha Cake', $data['bestSellers'][0]['name']);
        $this->assertSame('Best Seller', $data['bestSellers'][0]['tag']);
        $this->assertSame('₱650', $data['bestSellers'][0]['price']);
        $this->assertSame(['Gluten', 'Eggs'], $data['bestSellers'][0]['allergens']);

        // Archived products are excluded from both sections.
        $categoryNames = array_column($data['categories'], 'name');
        $this->assertEqualsCanonicalizing(['Bread', 'Cake'], $categoryNames);
    }

    public function test_maintenance_mode_skips_the_product_query_and_hides_sections(): void
    {
        SiteContent::create(['id' => 1, 'data' => ['maintenance' => ['enabled' => true]]]);
        Product::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mocha Cake',
            'category' => 'Cake',
            'price' => 650,
            'status' => 'best_seller',
        ]);

        $view = app(LandingController::class)->index(Request::create('/'));
        $data = $view->getData();

        $this->assertTrue($data['content']['maintenance']['enabled']);
        $this->assertSame([], $data['bestSellers']);
        $this->assertSame([], $data['categories']);
    }

    public function test_homepage_has_canonical_og_tags_and_valid_json_ld_with_store_departments(): void
    {
        \App\Models\Store::create([
            'name' => 'BW Superbakeshop — Makati',
            'region' => 'Luzon',
            'fulfillment' => 'both',
            'address' => '88 Ayala Ave, Makati City',
            'hours' => '7:00 AM – 9:00 PM',
            'phone' => '0917 000 0000',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('<meta property="og:title"', $html);
        $this->assertMatchesRegularExpression('/<h1[^>]*>.*bw Superbakeshop.*<\/h1>/i', $html);

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $jsonLd = json_decode($m[1], true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertContains('Organization', $jsonLd['@graph'][0]['@type']);
        $this->assertCount(1, $jsonLd['@graph'][0]['department']);
    }

    public function test_button_state_helper_matches_the_ported_js_semantics(): void
    {
        $this->assertSame('on', SiteContent::buttonState([], 'navOrder'));
        $this->assertSame('on', SiteContent::buttonState(['navOrder' => true], 'navOrder'));
        $this->assertSame('off', SiteContent::buttonState(['navOrder' => false], 'navOrder'));
        $this->assertSame('off', SiteContent::buttonState(['navOrder' => 'off'], 'navOrder'));
        $this->assertSame('disabled', SiteContent::buttonState(['navOrder' => 'disabled'], 'navOrder'));
    }

    protected function tearDown(): void
    {
        Cache::forget('products.index');
        parent::tearDown();
    }
}
