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
